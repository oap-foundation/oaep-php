# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Planned for v0.2.0
- StatusList2021 for credential revocation
- TLS 1.3 enforcement in API layer
- Comprehensive unit tests (PHPUnit)
- Key rotation mechanism
- Mnemonic seed recovery (BIP39)

## [0.1.0] - 2025-11-20

### Added

#### DID Methods
- DID:Key implementation with Ed25519 cryptography
- DID:Web implementation with HTTPS resolution
- Multibase encoding (base58btc) support
- DID document generation (W3C compliant)
- Service endpoint configuration

#### Verifiable Credentials
- AgentProfile VC structure (W3C VC standard)
- JSON-LD context handling
- Three agent types: PersonalAgent, BusinessAgent, ServiceProviderAgent
- Ed25519Signature2020 signing
- Signature verification
- Expiration date support
- Self-issued credentials

#### OAEP Handshake Protocol
- ConnectionRequest message
- ConnectionChallenge with cryptographic nonce
- ConnectionResponse with signature
- Mutual authentication flow
- Session management
- Replay protection (single-use challenges)
- Challenge expiration (5 minutes)
- Automatic session cleanup

#### Documentation
- Comprehensive API reference (API.md)
- Implementation guide (IMPLEMENTATION.md)
- Security best practices (SECURITY.md)
- Project README with quick start
- GitHub publication guide

#### Examples & Demos
- CLI example: simple-handshake.php
- CLI example: did-examples.php
- CLI example: vc-examples.php
- Interactive web demo (index.html)
- Demo API backend (api.php)

#### Infrastructure
- Composer configuration
- PSR-4 autoloading
- .gitignore for security
- Project structure documentation

### Security
- Ed25519 cryptography throughout
- Secure random number generation
- Private key protection
- Input validation
- Challenge-response authentication
- Timestamp validation

### Standards Compliance
- W3C DID Core 1.0
- W3C Verifiable Credentials 1.1
- Ed25519Signature2020
- JSON-LD 1.1
- Multibase encoding

## [0.0.1] - 2025-11-20

### Initial Development
- Project setup
- Core architecture design
- Proof of concept

---

## Version History Summary

- **0.1.0** - First public release, core OAEP v0.1 implementation
- **0.0.1** - Internal development version

## Links

- [OAEP Specification](https://github.com/oap-foundation/oaep-spec/blob/main/specification/v0.1.md)
- [OAP Framework](https://github.com/oap-foundation/oap-framework)
- [Repository](https://github.com/oap-foundation/oaep-php)
