<?php

declare(strict_types=1);

namespace OAP\OAEP\DID;

use InvalidArgumentException;
use RuntimeException;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

/**
 * Implementation of the did:web method
 * 
 * The did:web method binds a DID to a DNS domain name.
 * The DID document is hosted at a well-known HTTPS location.
 * 
 * Specification: https://w3c-ccg.github.io/did-method-web/
 */
class DIDWeb implements DIDInterface
{
    private const METHOD = 'web';
    private const WELL_KNOWN_PATH = '/.well-known/did.json';

    private string $domain;
    private ?string $path;
    private string $publicKey;
    private ?string $privateKey;
    private ?array $didDocument;
    private ?Client $httpClient;

    /**
     * @param string $domain The domain name (e.g., "example.com")
     * @param string|null $path Optional path component
     * @param string $publicKey Raw binary public key
     * @param string|null $privateKey Raw binary private key
     * @param array|null $didDocument Optional cached DID document
     */
    private function __construct(
        string $domain,
        ?string $path,
        string $publicKey,
        ?string $privateKey,
        ?array $didDocument = null
    ) {
        $this->domain = $domain;
        $this->path = $path;
        $this->publicKey = $publicKey;
        $this->privateKey = $privateKey;
        $this->didDocument = $didDocument;
        $this->httpClient = null;
    }

    /**
     * Generate a new did:web
     *
     * @param string $domain The domain name
     * @param string|null $path Optional path component
     * @return self
     */
    public static function generate(string $domain = 'localhost', ?string $path = null): self
    {
        // Generate Ed25519 key pair
        $keyPair = sodium_crypto_sign_keypair();
        $publicKey = sodium_crypto_sign_publickey($keyPair);
        $privateKey = sodium_crypto_sign_secretkey($keyPair);

        return new self($domain, $path, $publicKey, $privateKey);
    }

    /**
     * Create did:web from existing key material
     *
     * @param array $keyMaterial Must contain 'domain', 'publicKey', and optionally 'path', 'privateKey'
     * @return self
     */
    public static function fromKeyMaterial(array $keyMaterial): self
    {
        if (!isset($keyMaterial['domain'], $keyMaterial['publicKey'])) {
            throw new InvalidArgumentException('Key material must contain domain and publicKey');
        }

        return new self(
            $keyMaterial['domain'],
            $keyMaterial['path'] ?? null,
            $keyMaterial['publicKey'],
            $keyMaterial['privateKey'] ?? null
        );
    }

    /**
     * Parse a did:web string
     *
     * @param string $didString E.g., "did:web:example.com" or "did:web:example.com:user:alice"
     * @return self
     */
    public static function fromString(string $didString): self
    {
        if (!str_starts_with($didString, 'did:web:')) {
            throw new InvalidArgumentException('Invalid did:web format');
        }

        $parts = explode(':', substr($didString, 8));
        $domain = array_shift($parts);

        // Decode percent-encoded domain
        $domain = urldecode($domain);

        $path = !empty($parts) ? '/' . implode('/', $parts) : null;

        // We don't have the key material from just the DID string
        // It will be fetched when resolve() is called
        return new self($domain, $path, '', null);
    }

    /**
     * Get the complete DID string
     *
     * @return string
     */
    public function toString(): string
    {
        $did = 'did:web:' . urlencode($this->domain);

        if ($this->path !== null) {
            $pathParts = explode('/', trim($this->path, '/'));
            $did .= ':' . implode(':', array_map('urlencode', $pathParts));
        }

        return $did;
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
        $id = $this->domain;
        if ($this->path !== null) {
            $id .= ':' . str_replace('/', ':', trim($this->path, '/'));
        }
        return $id;
    }

    /**
     * Resolve to a DID Document by fetching from the web
     *
     * @return array
     * @throws RuntimeException
     */
    public function resolve(): array
    {
        // Return cached document if available
        if ($this->didDocument !== null) {
            return $this->didDocument;
        }

        // Build the URL for the DID document
        $url = $this->buildDocumentUrl();

        try {
            $client = $this->getHttpClient();
            $response = $client->get($url, [
                'verify' => true, // Enforce SSL verification
                'timeout' => 10,
                'headers' => [
                    'Accept' => 'application/json'
                ]
            ]);

            $body = $response->getBody()->getContents();
            $didDocument = json_decode($body, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new RuntimeException('Invalid JSON in DID document: ' . json_last_error_msg());
            }

            // Validate that the DID in the document matches
            if (!isset($didDocument['id']) || $didDocument['id'] !== $this->toString()) {
                throw new RuntimeException('DID mismatch in document');
            }

            // Cache the document
            $this->didDocument = $didDocument;

            // Extract public key if not already set
            if (empty($this->publicKey) && isset($didDocument['verificationMethod'][0])) {
                $this->extractPublicKeyFromDocument($didDocument);
            }

            return $didDocument;

        } catch (GuzzleException $e) {
            throw new RuntimeException('Failed to resolve did:web: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Get the public key
     *
     * @return string
     */
    public function getPublicKey(): string
    {
        // If we don't have the public key yet, try to resolve the document
        if (empty($this->publicKey)) {
            $this->resolve();
        }

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
        if (empty($this->publicKey)) {
            $this->resolve();
        }

        return sodium_crypto_sign_verify_detached($signature, $data, $this->publicKey);
    }

    /**
     * Create a DID document for this did:web
     *
     * @param array $serviceEndpoints Optional array of service endpoints
     * @return array
     */
    public function createDocument(array $serviceEndpoints = []): array
    {
        $did = $this->toString();
        $publicKeyMultibase = 'z' . DIDKey::base58Encode(
            pack('C*', DIDKey::MULTICODEC_ED25519_PUB, 0x01) . $this->publicKey
        );

        $verificationMethod = $did . '#key-1';

        $document = [
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
                    'publicKeyMultibase' => $publicKeyMultibase
                ]
            ],
            'authentication' => [$verificationMethod],
            'assertionMethod' => [$verificationMethod]
        ];

        // Add service endpoints if provided
        if (!empty($serviceEndpoints)) {
            $document['service'] = $serviceEndpoints;
        }

        $this->didDocument = $document;
        return $document;
    }

    /**
     * Get the domain
     *
     * @return string
     */
    public function getDomain(): string
    {
        return $this->domain;
    }

    /**
     * Get the path
     *
     * @return string|null
     */
    public function getPath(): ?string
    {
        return $this->path;
    }

    /**
     * Build the URL for fetching the DID document
     *
     * @return string
     */
    private function buildDocumentUrl(): string
    {
        $url = 'https://' . $this->domain;

        if ($this->path !== null) {
            $url .= $this->path . '/did.json';
        } else {
            $url .= self::WELL_KNOWN_PATH;
        }

        return $url;
    }

    /**
     * Extract public key from DID document
     *
     * @param array $didDocument
     * @return void
     */
    private function extractPublicKeyFromDocument(array $didDocument): void
    {
        if (!isset($didDocument['verificationMethod'][0]['publicKeyMultibase'])) {
            throw new RuntimeException('No public key found in DID document');
        }

        $multibase = $didDocument['verificationMethod'][0]['publicKeyMultibase'];

        // Decode the multibase public key (similar to did:key)
        if (str_starts_with($multibase, 'z')) {
            $base58 = substr($multibase, 1);
            $decoded = DIDKey::base58Decode($base58);
            // Remove multicodec prefix (2 bytes)
            $this->publicKey = substr($decoded, 2);
        } else {
            throw new RuntimeException('Unsupported multibase encoding in DID document');
        }
    }

    /**
     * Get HTTP client (lazy initialization)
     *
     * @return Client
     */
    private function getHttpClient(): Client
    {
        if ($this->httpClient === null) {
            $this->httpClient = new Client();
        }
        return $this->httpClient;
    }

    /**
     * Set custom HTTP client (for testing)
     *
     * @param Client $client
     * @return void
     */
    public function setHttpClient(Client $client): void
    {
        $this->httpClient = $client;
    }
}
