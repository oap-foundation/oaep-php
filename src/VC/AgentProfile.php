<?php

declare(strict_types=1);

namespace OAP\OAEP\VC;

use OAP\OAEP\DID\DIDInterface;
use InvalidArgumentException;

/**
 * AgentProfile Verifiable Credential
 * 
 * The central object in OAEP - represents an agent's identity and capabilities
 * as a W3C Verifiable Credential.
 */
class AgentProfile
{
    private const CONTEXT_W3C_VC = 'https://www.w3.org/2018/credentials/v1';
    private const CONTEXT_OAEP = 'https://openagentprotocol.org/contexts/oaep/v1';
    private const TYPE_VC = 'VerifiableCredential';
    private const TYPE_AGENT_PROFILE = 'AgentProfile';

    public const AGENT_TYPE_PERSONAL = 'PersonalAgent';
    public const AGENT_TYPE_BUSINESS = 'BusinessAgent';
    public const AGENT_TYPE_SERVICE = 'ServiceProviderAgent';

    private array $credential;

    /**
     * @param array $credential The complete credential data
     */
    private function __construct(array $credential)
    {
        $this->credential = $credential;
    }

    /**
     * Create a new AgentProfile credential
     *
     * @param array $params Parameters for the agent profile
     *   - did: DIDInterface - The DID of the agent
     *   - name: string - Human-readable name of the agent
     *   - type: string - Agent type (PersonalAgent, BusinessAgent, ServiceProviderAgent)
     *   - supportedProtocols: array - List of supported protocols
     *   - issuer: DIDInterface|string - The issuer DID (defaults to self-issued)
     *   - issuanceDate: string|null - ISO 8601 date (defaults to now)
     *   - expirationDate: string|null - Optional expiration date
     *   - credentialId: string|null - Optional credential ID (auto-generated if not provided)
     * @return self
     */
    public static function create(array $params): self
    {
        // Validate required parameters
        if (!isset($params['did'], $params['name'], $params['type'])) {
            throw new InvalidArgumentException('Missing required parameters: did, name, type');
        }

        $did = $params['did'];
        if (!$did instanceof DIDInterface) {
            throw new InvalidArgumentException('did must be an instance of DIDInterface');
        }

        // Validate agent type
        $validTypes = [self::AGENT_TYPE_PERSONAL, self::AGENT_TYPE_BUSINESS, self::AGENT_TYPE_SERVICE];
        if (!in_array($params['type'], $validTypes, true)) {
            throw new InvalidArgumentException('Invalid agent type');
        }

        // Build the credential
        $issuer = $params['issuer'] ?? $did;
        if ($issuer instanceof DIDInterface) {
            $issuer = $issuer->toString();
        }

        $credentialId = $params['credentialId'] ?? 'urn:uuid:' . self::generateUuid();
        $issuanceDate = $params['issuanceDate'] ?? gmdate('Y-m-d\TH:i:s\Z');

        $credential = [
            '@context' => [
                self::CONTEXT_W3C_VC,
                self::CONTEXT_OAEP
            ],
            'id' => $credentialId,
            'type' => [self::TYPE_VC, self::TYPE_AGENT_PROFILE],
            'issuer' => $issuer,
            'issuanceDate' => $issuanceDate,
            'credentialSubject' => [
                'id' => $did->toString(),
                'agent' => [
                    'type' => $params['type'],
                    'name' => $params['name'],
                    'supportedProtocols' => $params['supportedProtocols'] ?? []
                ]
            ]
        ];

        // Add optional expiration date
        if (isset($params['expirationDate'])) {
            $credential['expirationDate'] = $params['expirationDate'];
        }

        // Add optional description
        if (isset($params['description'])) {
            $credential['credentialSubject']['agent']['description'] = $params['description'];
        }

        return new self($credential);
    }

    /**
     * Create from existing credential data
     *
     * @param array $credential
     * @return self
     */
    public static function fromArray(array $credential): self
    {
        // Validate credential structure
        if (!isset($credential['@context'], $credential['type'], $credential['credentialSubject'])) {
            throw new InvalidArgumentException('Invalid credential structure');
        }

        if (!in_array(self::TYPE_AGENT_PROFILE, $credential['type'], true)) {
            throw new InvalidArgumentException('Not an AgentProfile credential');
        }

        return new self($credential);
    }

    /**
     * Create from JSON string
     *
     * @param string $json
     * @return self
     */
    public static function fromJson(string $json): self
    {
        $data = json_decode($json, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new InvalidArgumentException('Invalid JSON: ' . json_last_error_msg());
        }

        return self::fromArray($data);
    }

    /**
     * Sign the credential with a DID's private key
     *
     * @param DIDInterface $issuerDid The DID to sign with (must have private key)
     * @return self Returns a new instance with the proof added
     */
    public function sign(DIDInterface $issuerDid): self
    {
        // Create the credential without proof
        $credentialCopy = $this->credential;
        unset($credentialCopy['proof']);

        // Create canonical representation for signing
        $canonicalData = json_encode($credentialCopy, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        // Sign the data
        $signature = $issuerDid->sign($canonicalData);

        // Create the proof object
        $proof = [
            'type' => 'Ed25519Signature2020',
            'created' => gmdate('Y-m-d\TH:i:s\Z'),
            'verificationMethod' => $issuerDid->toString() . '#key-1',
            'proofPurpose' => 'assertionMethod',
            'proofValue' => base64_encode($signature)
        ];

        // Add proof to credential
        $credentialCopy['proof'] = $proof;

        return new self($credentialCopy);
    }

    /**
     * Verify the credential's signature
     *
     * @param DIDInterface $issuerDid The DID to verify against
     * @return bool True if signature is valid
     */
    public function verify(DIDInterface $issuerDid): bool
    {
        if (!isset($this->credential['proof'])) {
            return false;
        }

        $proof = $this->credential['proof'];

        // Create credential without proof for verification
        $credentialCopy = $this->credential;
        unset($credentialCopy['proof']);

        $canonicalData = json_encode($credentialCopy, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        // Decode the signature
        $signature = base64_decode($proof['proofValue']);

        // Verify the signature
        return $issuerDid->verify($canonicalData, $signature);
    }

    /**
     * Check if the credential is expired
     *
     * @return bool
     */
    public function isExpired(): bool
    {
        if (!isset($this->credential['expirationDate'])) {
            return false;
        }

        $expirationTimestamp = strtotime($this->credential['expirationDate']);
        return $expirationTimestamp < time();
    }

    /**
     * Get the agent's DID
     *
     * @return string
     */
    public function getAgentDid(): string
    {
        return $this->credential['credentialSubject']['id'];
    }

    /**
     * Get the agent's name
     *
     * @return string
     */
    public function getAgentName(): string
    {
        return $this->credential['credentialSubject']['agent']['name'];
    }

    /**
     * Get the agent type
     *
     * @return string
     */
    public function getAgentType(): string
    {
        return $this->credential['credentialSubject']['agent']['type'];
    }

    /**
     * Get supported protocols
     *
     * @return array
     */
    public function getSupportedProtocols(): array
    {
        return $this->credential['credentialSubject']['agent']['supportedProtocols'] ?? [];
    }

    /**
     * Check if agent supports a specific protocol
     *
     * @param string $protocol Protocol name (e.g., 'OACP')
     * @param string|null $version Optional version to check
     * @return bool
     */
    public function supportsProtocol(string $protocol, ?string $version = null): bool
    {
        $protocols = $this->getSupportedProtocols();

        foreach ($protocols as $p) {
            if ($p['protocol'] === $protocol) {
                if ($version === null || $p['version'] === $version) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Get the credential ID
     *
     * @return string
     */
    public function getId(): string
    {
        return $this->credential['id'];
    }

    /**
     * Get the issuer DID
     *
     * @return string
     */
    public function getIssuer(): string
    {
        return $this->credential['issuer'];
    }

    /**
     * Get issuance date
     *
     * @return string
     */
    public function getIssuanceDate(): string
    {
        return $this->credential['issuanceDate'];
    }

    /**
     * Get the complete credential as an array
     *
     * @return array
     */
    public function toArray(): array
    {
        return $this->credential;
    }

    /**
     * Convert to JSON string
     *
     * @param int $options JSON encoding options
     * @return string
     */
    public function toJson(int $options = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT): string
    {
        return json_encode($this->credential, $options);
    }

    /**
     * Generate a UUID v4
     *
     * @return string
     */
    private static function generateUuid(): string
    {
        $data = random_bytes(16);

        // Set version (0100) and variant (10)
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}
