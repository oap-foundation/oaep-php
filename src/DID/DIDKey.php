<?php

declare(strict_types=1);

namespace OAP\OAEP\DID;

use InvalidArgumentException;
use RuntimeException;

/**
 * Implementation of the did:key method
 * 
 * The did:key method creates a DID directly from a cryptographic public key.
 * The DID is self-contained and doesn't require external registration.
 * 
 * Specification: https://w3c-ccg.github.io/did-method-key/
 */
class DIDKey implements DIDInterface
{
    private const METHOD = 'key';
    private const MULTICODEC_ED25519_PUB = 0xed;
    private const BASE58_ALPHABET = '123456789ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz';

    private string $publicKey;
    private ?string $privateKey;
    private string $multibaseEncoded;

    /**
     * @param string $publicKey Raw binary public key (32 bytes for Ed25519)
     * @param string|null $privateKey Raw binary private key (64 bytes for Ed25519)
     * @param string $multibaseEncoded The multibase-encoded public key
     */
    private function __construct(string $publicKey, ?string $privateKey, string $multibaseEncoded)
    {
        $this->publicKey = $publicKey;
        $this->privateKey = $privateKey;
        $this->multibaseEncoded = $multibaseEncoded;
    }

    /**
     * Generate a new did:key with a fresh Ed25519 key pair
     *
     * @return self
     */
    public static function generate(): self
    {
        // Generate Ed25519 key pair using libsodium
        $keyPair = sodium_crypto_sign_keypair();
        $publicKey = sodium_crypto_sign_publickey($keyPair);
        $privateKey = sodium_crypto_sign_secretkey($keyPair);

        $multibaseEncoded = self::encodeMultibase($publicKey);

        return new self($publicKey, $privateKey, $multibaseEncoded);
    }

    /**
     * Create did:key from existing key material
     *
     * @param array $keyMaterial Must contain 'publicKey' and optionally 'privateKey'
     * @return self
     */
    public static function fromKeyMaterial(array $keyMaterial): self
    {
        if (!isset($keyMaterial['publicKey'])) {
            throw new InvalidArgumentException('Key material must contain publicKey');
        }

        $publicKey = $keyMaterial['publicKey'];
        $privateKey = $keyMaterial['privateKey'] ?? null;

        $multibaseEncoded = self::encodeMultibase($publicKey);

        return new self($publicKey, $privateKey, $multibaseEncoded);
    }

    /**
     * Parse a did:key string
     *
     * @param string $didString E.g., "did:key:z6MkhaXgBZDvotDkL5257faiztiGiC2QtKLGpbnnEGta2doK"
     * @return self
     */
    public static function fromString(string $didString): self
    {
        if (!str_starts_with($didString, 'did:key:')) {
            throw new InvalidArgumentException('Invalid did:key format');
        }

        $multibaseEncoded = substr($didString, 8); // Remove "did:key:" prefix

        if (!str_starts_with($multibaseEncoded, 'z')) {
            throw new InvalidArgumentException('did:key must use base58btc encoding (z prefix)');
        }

        $publicKey = self::decodeMultibase($multibaseEncoded);

        return new self($publicKey, null, $multibaseEncoded);
    }

    /**
     * Get the complete DID string
     *
     * @return string
     */
    public function toString(): string
    {
        return 'did:key:' . $this->multibaseEncoded;
    }

    /**
     * Get the DID method
     *
     * @return string
     */
    public function getMethod(): string
    {
        return self::METHOD;
    }

    /**
     * Get the method-specific identifier
     *
     * @return string
     */
    public function getMethodSpecificId(): string
    {
        return $this->multibaseEncoded;
    }

    /**
     * Resolve to a DID Document
     *
     * @return array
     */
    public function resolve(): array
    {
        $did = $this->toString();
        $verificationMethod = $did . '#' . $this->multibaseEncoded;

        return [
            '@context' => [
                'https://www.w3.org/ns/did/v1',
                'https://w3id.org/security/suites/ed25519-2020/v1'
            ],
            'id' => $did,
            'verificationMethod' => [
                [
                    'id' => $verificationMethod,
                    'type' => 'Ed25519VerificationKey2020',
                    'controller' => $did,
                    'publicKeyMultibase' => $this->multibaseEncoded
                ]
            ],
            'authentication' => [$verificationMethod],
            'assertionMethod' => [$verificationMethod],
            'capabilityDelegation' => [$verificationMethod],
            'capabilityInvocation' => [$verificationMethod]
        ];
    }

    /**
     * Get the public key
     *
     * @return string
     */
    public function getPublicKey(): string
    {
        return $this->publicKey;
    }

    /**
     * Get the private key
     *
     * @return string|null
     */
    public function getPrivateKey(): ?string
    {
        return $this->privateKey;
    }

    /**
     * Sign data with the private key
     *
     * @param string $data
     * @return string
     */
    public function sign(string $data): string
    {
        if ($this->privateKey === null) {
            throw new RuntimeException('Private key not available for signing');
        }

        // Reconstruct keypair for sodium
        $keyPair = $this->privateKey . $this->publicKey;

        return sodium_crypto_sign_detached($data, $keyPair);
    }

    /**
     * Verify a signature
     *
     * @param string $data
     * @param string $signature
     * @return bool
     */
    public function verify(string $data, string $signature): bool
    {
        return sodium_crypto_sign_verify_detached($signature, $data, $this->publicKey);
    }

    /**
     * Encode public key as multibase (base58btc with 'z' prefix)
     *
     * @param string $publicKey
     * @return string
     */
    private static function encodeMultibase(string $publicKey): string
    {
        // Create multicodec prefix for Ed25519 public key
        $multicodec = pack('C*', self::MULTICODEC_ED25519_PUB, 0x01);
        $multicodecKey = $multicodec . $publicKey;

        // Encode to base58btc
        $base58 = self::base58Encode($multicodecKey);

        // Add multibase prefix 'z' for base58btc
        return 'z' . $base58;
    }

    /**
     * Decode multibase string to public key
     *
     * @param string $multibaseEncoded
     * @return string
     */
    private static function decodeMultibase(string $multibaseEncoded): string
    {
        if (!str_starts_with($multibaseEncoded, 'z')) {
            throw new InvalidArgumentException('Invalid multibase encoding');
        }

        // Remove 'z' prefix
        $base58 = substr($multibaseEncoded, 1);

        // Decode base58
        $multicodecKey = self::base58Decode($base58);

        // Remove multicodec prefix (first 2 bytes)
        if (strlen($multicodecKey) < 3) {
            throw new InvalidArgumentException('Invalid multicodec key');
        }

        return substr($multicodecKey, 2);
    }

    /**
     * Encode binary data to base58
     *
     * @param string $data
     * @return string
     */
    private static function base58Encode(string $data): string
    {
        $alphabet = self::BASE58_ALPHABET;
        $base = strlen($alphabet);

        // Convert binary to decimal
        $decimal = gmp_init(bin2hex($data), 16);

        // Convert decimal to base58
        $result = '';
        while (gmp_cmp($decimal, 0) > 0) {
            [$decimal, $remainder] = gmp_div_qr($decimal, $base);
            $result = $alphabet[gmp_intval($remainder)] . $result;
        }

        // Add leading zeros
        for ($i = 0; $i < strlen($data) && $data[$i] === "\0"; $i++) {
            $result = $alphabet[0] . $result;
        }

        return $result;
    }

    /**
     * Decode base58 to binary
     *
     * @param string $base58
     * @return string
     */
    private static function base58Decode(string $base58): string
    {
        $alphabet = self::BASE58_ALPHABET;
        $base = strlen($alphabet);

        // Convert base58 to decimal
        $decimal = gmp_init(0);
        for ($i = 0; $i < strlen($base58); $i++) {
            $pos = strpos($alphabet, $base58[$i]);
            if ($pos === false) {
                throw new InvalidArgumentException('Invalid base58 character');
            }
            $decimal = gmp_add(gmp_mul($decimal, $base), $pos);
        }

        // Convert decimal to binary
        $hex = gmp_strval($decimal, 16);
        if (strlen($hex) % 2 !== 0) {
            $hex = '0' . $hex;
        }
        $binary = hex2bin($hex);

        // Add leading zeros
        for ($i = 0; $i < strlen($base58) && $base58[$i] === $alphabet[0]; $i++) {
            $binary = "\0" . $binary;
        }

        return $binary;
    }
}
