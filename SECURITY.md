# Security Policy

## Supported Versions

| Version | Supported          |
| ------- | ------------------ |
| 0.1.x   | :white_check_mark: |

## Reporting a Vulnerability

**Please do NOT report security vulnerabilities through public GitHub issues.**

Instead, please report security vulnerabilities by email to:

**security@openagentprotocol.org**

You should receive a response within 48 hours. If for some reason you do not, please follow up via email to ensure we received your original message.

Please include the following information:

- Type of issue (e.g., buffer overflow, SQL injection, cross-site scripting, etc.)
- Full paths of source file(s) related to the manifestation of the issue
- The location of the affected source code (tag/branch/commit or direct URL)
- Any special configuration required to reproduce the issue
- Step-by-step instructions to reproduce the issue
- Proof-of-concept or exploit code (if possible)
- Impact of the issue, including how an attacker might exploit it

## Security Response Process

1. **Acknowledgment** - We will acknowledge receipt of your vulnerability report within 48 hours
2. **Assessment** - We will assess the vulnerability and determine its severity
3. **Development** - We will develop a fix
4. **Testing** - We will test the fix thoroughly
5. **Release** - We will release a security patch
6. **Disclosure** - We will publicly disclose the vulnerability after the patch is released

## Security Update Policy

- **Critical vulnerabilities**: Patched within 7 days
- **High severity**: Patched within 30 days
- **Medium severity**: Patched in next minor release
- **Low severity**: Patched in next major release

## Known Security Considerations

### Current Implementation (v0.1.0)

#### ✅ Implemented Security Features

- Ed25519 cryptography for signatures
- Challenge-response authentication
- Replay protection via single-use nonces
- Challenge expiration (5 minutes)
- Session timeout (1 hour)
- Input validation for DIDs and credentials
- Timestamp validation

#### ⚠️ Security Limitations

**v0.1.0 is a reference implementation. For production use:**

1. **Key Storage**: Currently uses software-based key storage
   - **Recommendation**: Implement hardware-backed storage (Secure Enclave, TPM)
   - **Status**: Planned for v0.3.0

2. **TLS Enforcement**: Not enforced at code level
   - **Recommendation**: Configure web server to require TLS 1.3+
   - **Status**: Code-level checks planned for v0.2.0

3. **Credential Revocation**: StatusList2021 not yet implemented
   - **Recommendation**: Implement short credential lifetimes
   - **Status**: Planned for v0.2.0

4. **Rate Limiting**: Not implemented at code level
   - **Recommendation**: Implement at web server or reverse proxy level
   - **Status**: Planned for v0.2.0

5. **Unit Tests**: Limited test coverage
   - **Recommendation**: Review and test code thoroughly
   - **Status**: Comprehensive tests planned for v0.2.0

### Best Practices

When using this library in production:

1. **Always use HTTPS** with TLS 1.3 or higher
2. **Implement rate limiting** at infrastructure level
3. **Use hardware key storage** when available
4. **Set short credential lifetimes** (until StatusList2021 is implemented)
5. **Monitor for unusual patterns** in authentication attempts
6. **Keep dependencies updated** regularly
7. **Follow the security guidelines** in [SECURITY.md](docs/SECURITY.md)

## Cryptographic Algorithms

### Approved Algorithms

- **Signatures**: Ed25519 (EdDSA)
- **Hashing**: SHA-256, SHA-512
- **Random**: PHP's `random_bytes()` (cryptographically secure)

### Deprecated Algorithms

This library does NOT use:
- RSA < 2048 bits
- MD5 or SHA-1
- DES or 3DES
- Any unsafe random number generators

## Security Audit Status

- **v0.1.0**: No formal security audit yet
- **Future**: Security audit planned before v1.0.0 release

## Dependencies Security

Keep these dependencies updated:

```bash
composer update
```

Monitor for security advisories:
- https://github.com/advisories
- https://packagist.org/

## Bug Bounty Program

Currently, we do not have a bug bounty program. However, we greatly appreciate responsible disclosure and will acknowledge security researchers who report vulnerabilities.

## Contact

For security-related questions: **security@openagentprotocol.org**

For general questions: https://github.com/oap-foundation/oaep-php/issues

---

## Security Researchers Hall of Fame

We thank the following researchers for responsibly disclosing security issues:

_(No vulnerabilities reported yet)_

---

Last updated: 2025-11-20
