# OAEP - Open Agent Exchange Protocol

![License](https://img.shields.io/badge/license-CC%20BY--SA%204.0-blue.svg)
![PHP Version](https://img.shields.io/badge/php-%3E%3D8.0-8892BF.svg)

**PHP Reference Implementation of the Open Agent Exchange Protocol (OAEP) v0.1**

OAEP is the foundational Layer 0 protocol of the [Open Agent Protocol (OAP) Framework](https://github.com/oap-foundation/oap-framework), providing a decentralized identity and trust layer for agent-to-agent communication in the AI-to-AI economy.

## 🎯 What is OAEP?

OAEP solves the fundamental problem of establishing **secure, trustworthy, and sovereign** connections between autonomous agents (AI, humans, organizations) without relying on centralized authorities.

### Core Features

- ✅ **Decentralized Identity** - Based on W3C DID (Decentralized Identifiers) standard
- ✅ **Verifiable Credentials** - W3C VC standard for tamper-proof digital claims
- ✅ **Secure Handshake** - Cryptographic challenge-response authentication
- ✅ **Trust Management** - StatusList2021 for credential revocation
- ✅ **Sovereign Key Management** - User-controlled cryptographic keys
- ✅ **Agent Discovery** - Standardized service endpoint resolution

## 🚀 Quick Start

### Prerequisites

- PHP >= 8.0
- Composer
- Sodium extension (usually included in PHP 7.2+)
- OpenSSL extension

### Installation

```bash
# Clone the repository
git clone https://github.com/oap-foundation/oaep-php.git oaep
cd oaep

# Install dependencies
composer install
```

### Basic Usage

```php
<?php
require_once 'vendor/autoload.php';

use OAP\OAEP\DID\DIDKey;
use OAP\OAEP\VC\AgentProfile;
use OAP\OAEP\Handshake\HandshakeManager;

// Create a new agent identity
$did = DIDKey::generate();
echo "Agent DID: " . $did->toString() . "\n";

// Create an AgentProfile credential
$profile = AgentProfile::create([
    'did' => $did,
    'name' => 'My Personal AI',
    'type' => 'PersonalAgent',
    'supportedProtocols' => [
        ['protocol' => 'OACP', 'version' => '1.0']
    ]
]);

// Initiate a connection with another agent
$handshake = new HandshakeManager($did);
$connection = $handshake->connect('did:web:example.com');
```

## 📖 Documentation

- [API Reference](docs/API.md)
- [Implementation Guide](docs/IMPLEMENTATION.md)
- [Security Best Practices](docs/SECURITY.md)
- [OAEP Specification v0.1](https://github.com/oap-foundation/oaep-spec/blob/main/specification/v0.1.md)

## 🏗️ Architecture

```
src/
├── DID/              # Decentralized Identifier implementations
│   ├── DIDKey.php    # did:key method
│   └── DIDWeb.php    # did:web method
├── VC/               # Verifiable Credentials
│   ├── AgentProfile.php
│   ├── VCIssuer.php
│   └── StatusList.php
├── Handshake/        # OAEP Handshake Protocol
│   └── HandshakeManager.php
├── Keys/             # Key Management
│   ├── KeyGenerator.php
│   └── Keystore.php
└── API/              # REST API Layer
    └── Server.php
```

## 🧪 Testing

```bash
# Run all tests
composer test

# Run with coverage
composer test -- --coverage-html coverage

# Static analysis
composer analyse

# Code style check
composer cs-check
```

## 🔐 Security

OAEP implements multiple layers of security:

1. **TLS 1.3+** - All communication must be encrypted
2. **Ed25519** - Modern elliptic curve cryptography
3. **Challenge-Response** - Prevents replay attacks
4. **Key Isolation** - Private keys never leave user control
5. **StatusList2021** - Dynamic credential revocation

**⚠️ Security Notice:** This is a reference implementation. For production use, please conduct a thorough security audit and follow the [Security Best Practices](docs/SECURITY.md).

## 🤝 Contributing

We welcome contributions! Please see our [Contributing Guidelines](CONTRIBUTING.md).

### Development Setup

```bash
# Install dev dependencies
composer install

# Run tests in watch mode
composer test -- --watch

# Fix code style issues
composer cs-fix
```

## 📜 License

This project is licensed under the **Creative Commons Attribution-ShareAlike 4.0 International License (CC BY-SA 4.0)**.

This "ShareAlike" license legally prevents the standard from being captured and closed by a proprietary fork.

See [LICENSE](LICENSE) for details.

## 🌐 Related Projects

- [OAP Framework](https://github.com/oap-foundation/oap-framework) - Vision and manifesto
- [OAEP Specification](https://github.com/oap-foundation/oaep-spec) - Formal specification
- [OACP Specification](https://github.com/oap-foundation/oacp-spec) - Commerce protocol (Layer 1)

## 📞 Support

- GitHub Issues: [Report a bug or request a feature](https://github.com/oap-foundation/oaep-php/issues)
- Discussions: [Join the community](https://github.com/oap-foundation/oap-framework/discussions)
- Security: See [SECURITY.md](SECURITY.md) for reporting vulnerabilities

## 🙏 Acknowledgments

Built on the shoulders of giants:

- [W3C DID Core](https://www.w3.org/TR/did-core/)
- [W3C Verifiable Credentials](https://www.w3.org/TR/vc-data-model/)
- [EdDSA/Ed25519](https://ed25519.cr.yp.to/)
- [StatusList2021](https://w3c-ccg.github.io/vc-status-list-2021/)

---

**Built with ❤️ for a sovereign digital future**
