<?php

declare(strict_types=1);

namespace OAP\OAEP\DID;

/**
 * Interface for all DID method implementations
 */
interface DIDInterface
{
    /**
     * Generate a new DID
     *
     * @return self
     */
    public static function generate(): self;

    /**
     * Create DID from existing key material
     *
     * @param array $keyMaterial Key material (implementation-specific)
     * @return self
     */
    public static function fromKeyMaterial(array $keyMaterial): self;

    /**
     * Parse a DID string into a DID object
     *
     * @param string $didString The DID string (e.g., "did:key:z6Mkf...")
     * @return self
     * @throws \InvalidArgumentException if DID string is invalid
     */
    public static function fromString(string $didString): self;

    /**
     * Get the DID as a string
     *
     * @return string The complete DID string
     */
    public function toString(): string;

    /**
     * Get the DID method
     *
     * @return string The method name (e.g., "key", "web")
     */
    public function getMethod(): string;

    /**
     * Get the DID method-specific identifier
     *
     * @return string The method-specific ID
     */
    public function getMethodSpecificId(): string;

    /**
     * Resolve the DID to a DID Document
     *
     * @return array The DID Document as an associative array
     * @throws \RuntimeException if resolution fails
     */
    public function resolve(): array;

    /**
     * Get the public key from the DID
     *
     * @return string The public key in raw binary format
     */
    public function getPublicKey(): string;

    /**
     * Get the private key (if available)
     *
     * @return string|null The private key in raw binary format, or null if not available
     */
    public function getPrivateKey(): ?string;

    /**
     * Sign data with this DID's private key
     *
     * @param string $data The data to sign
     * @return string The signature
     * @throws \RuntimeException if private key is not available
     */
    public function sign(string $data): string;

    /**
     * Verify a signature against this DID's public key
     *
     * @param string $data The original data
     * @param string $signature The signature to verify
     * @return bool True if signature is valid
     */
    public function verify(string $data, string $signature): bool;
}
