# OAEP Security Best Practices

## Overview

Security is paramount in the OAEP protocol. This document outlines critical security considerations and best practices for implementing and deploying OAEP-based systems.

## Key Management

###  Private Key Storage

**Critical Rules:**

1. **Never Transmit Private Keys**: Private keys must NEVER leave the device where they were generated
2. **Hardware-Backed Storage** (Tier 1 - Recommended):
   - Use OS-provided secure enclaves when available
   - iOS: Secure Enclave
   - Android: StrongBox Keymaster
   - Desktop: TPM 2.0
3. **Software-Backed Storage** (Tier 2 - Fallback):
   - Encrypt private keys with strong password-derived keys
   - Use Argon2id for password hashing (preferred) or PBKDF2 with high iteration count
   - Store encrypted keys in OS-protected storage

### Key Rotation

- Rotate keys periodically (recommended: every 12 months)
- Implement key rotation before expiration
- Maintain backward compatibility during rotation period
- Keep old keys for signature verification only

### Recovery Mechanisms

**Mnemonic Seeds:**
- Generate from cryptographically secure random source
- Display only once during setup
- Never store digitally - user must write it down
- Verify user has recorded it correctly

**Social Recovery:**
- Minimum 3 guardians recommended
- Quorum: require 3 of 5 or similar
- Guardians should be trusted entities or other devices
- Implement time delays for recovery to prevent attacks

## Cryptography

### Algorithms

**✅ Approved:**
- **Signing**: Ed25519 (EdDSA)
- **Hashing**: SHA-256, SHA-512, BLAKE2b
- **Symmetric Encryption**: ChaCha20-Poly1305, AES-256-GCM
- **Password Hashing**: Argon2id

**❌ Deprecated/Unsafe:**
- RSA < 2048 bits
- MD5, SHA-1
- DES, 3DES
- ECB mode for any cipher

### Random Number Generation

- Always use cryptographically secure RNG
- PHP: `random_bytes()` or `sodium_crypto_*` functions
- Never use `rand()`, `mt_rand()`, or `uniqid()`

## Network Security

### TLS Requirements

**Mandatory:**
- TLS 1.3 or higher only
- Certificate validation MUST be enforced
- No self-signed certificates in production (except testing)
- Use valid certificates from trusted CAs

**Recommended Cipher Suites (TLS 1.3):**
```
TLS_AES_256_GCM_SHA384
TLS_CHACHA20_POLY1305_SHA256
TLS_AES_128_GCM_SHA256
```

### API Endpoints

1. **Always use HTTPS** - Never plain HTTP
2. **Implement rate limiting**:
   - Connection requests: 10/minute per IP
   - Challenge responses: 20/minute per IP
3. **Validate all inputs** - Never trust client data
4. **Implement CORS properly** - Whitelist specific origins
5. **Use security headers**:
   ```
   Strict-Transport-Security: max-age=31536000; includeSubDomains
   X-Content-Type-Options: nosniff
   X-Frame-Options: DENY
   Content-Security-Policy: default-src 'self'
   ```

## Authentication & Authorization

### Challenge-Response Protocol

**Security Properties:**
1. **Freshness**: Each challenge must be unique (cryptographic nonce)
2. **Expiration**: Challenges expire after 5 minutes
3. **Single-Use**: Each challenge can only be used once
4. **Replay Protection**: Store used challenges with timestamps

**Implementation:**
```php
// Generate challenge
$challenge = bin2hex(random_bytes(32)); // 256 bits

// Store with expiration
$expires = time() + 300; // 5 minutes
$sessionStore[$sessionId] = [
    'challenge' => $challenge,
    'expires' => $expires,
    'used' => false
];
```

### DID Verification

**Steps for Every Request:**
1. Parse the DID from the request
2. Resolve the DID document (with caching)
3. Extract the public key
4. Verify the signature
5. Check credential status (not revoked)
6. Validate timestamps

**Never Skip:**
- Signature verification
- Timestamp validation
- Credential status checking

## Verifiable Credentials

### Issuance

1. **Validate Claims**: Verify all data before signing
2. **Include Expiration**: Set reasonable expiration dates
3. **Add Status Information**: Include `credentialStatus` for revocation
4. **Sign Immediately**: Generate and sign in one operation

### Verification

**Verification Checklist:**
- ✅ Signature is valid
- ✅ Issuer is trusted
- ✅ Credential is not expired
- ✅ Credential is not revoked (check status list)
- ✅ Subject matches expected DID
- ✅ Types are correct

### Revocation (StatusList2021)

1. **Publish Lists Over HTTPS**: Always use TLS
2. **Update Frequency**: Update at least daily, more for critical systems
3. **Cache Wisely**: Balance freshness vs. performance
4. **Monitor Access**: Log all status checks for anomalies

## Data Protection

### Sensitive Data Handling

**Private Keys:**
- Never log private keys
- Clear from memory after use (if possible)
- Never include in error messages or stack traces

**Challenges/Nonces:**
- Log only hashes, never plaintext
- Clear after verification
- Implement request ID for debugging instead

**User Data:**
- Minimize data collection
- Encrypt at rest
- Implement proper data retention policies

### Logging

**DO Log:**
- Connection attempts (with timestamps)
- Authentication failures
- Invalid signature attempts
- DID resolution requests
- Session creation/termination

**DON'T Log:**
- Private keys
- Challenges (use hashes)
- Full DIDs in sensitive contexts (use truncated versions)
- Passwords or secrets

## Deployment Security

### Environment Variables

Never hardcode:
- Private keys
- API keys
- Database credentials
- Domain names

Use environment-specific configuration files (not in version control).

### Production Checklist

- [ ] TLS 1.3 enforced
- [ ] Certificate validation enabled
- [ ] Rate limiting active
- [ ] Error messages don't leak information
- [ ] Debug mode disabled
- [ ] Logging configured correctly
- [ ] Private keys in hardware storage or encrypted
- [ ] Regular security audits scheduled
- [ ] Incident response plan documented
- [ ] Backup and recovery tested

## Attack Mitigation

### Replay Attacks

**Prevention:**
- Use fresh nonces for each challenge
- Include timestamps in all messages
- Reject messages older than 5 minutes
- Track used challenges

### Man-in-the-Middle (MITM)

**Prevention:**
- Enforce TLS 1.3
- Validate certificates
- Use certificate pinning for high-security applications

### DID Spoofing

**Prevention:**
- Always resolve DIDs from authoritative sources
- Verify signatures against resolved public keys
- Check credential status

### Denial of Service (DoS)

**Mitigation:**
- Implement rate limiting
- Use connection pooling
- Set timeout limits
- Monitor for unusual patterns

## Compliance & Auditing

### Security Audits

Recommended:
-  Annual third-party security audit
- Penetration testing before major releases
- Code review by security experts
- Automated security scanning (SAST/DAST)

### Incident Response

1. **Detection**: Monitor for security anomalies
2. **Containment**: Isolate affected systems
3. **Investigation**: Determine scope and impact
4. **Recovery**: Restore from clean backups
5. **Post-Mortem**: Document lessons learned

### Vulnerability Disclosure

- Provide security contact email
- Have a clear disclosure policy
- Respond to reports within 24 hours
- Fix critical vulnerabilities within 7 days

## Testing Security

### Security Test Cases

```php
// Test: Reject expired challenges
// Test: Reject reused challenges
// Test: Reject invalid signatures
// Test: Reject expired credentials
// Test: Reject revoked credentials
// Test: Enforce TLS
// Test: Rate limiting works
// Test: Session timeout enforcement
```

### Fuzzing

- Fuzz all input parsers
- Test with malformed DIDs
- Test with invalid JSON-LD
- Test with extreme values

## Resources

- [W3C DID Core Security Considerations](https://www.w3.org/TR/did-core/#security-considerations)
- [W3C VC Security Considerations](https://www.w3.org/TR/vc-data-model/#security-considerations)
- [OWASP Top 10](https://owasp.org/www-project-top-ten/)
- [libsodium Documentation](https://doc.libsodium.org/)

---

**Remember**: Security is not a feature, it's a requirement. When in doubt, choose the more secure option.
