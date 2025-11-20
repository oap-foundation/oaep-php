# Contributing to OAEP-PHP

Thank you for your interest in contributing to the OAEP-PHP implementation!

## 🤝 How to Contribute

We welcome contributions in many forms:

- 🐛 Bug reports and fixes
- 📝 Documentation improvements
- ✨ New features (aligned with OAEP spec)
- 🧪 Tests and test coverage improvements
- 💡 Examples and tutorials
- 🌐 Translations

## 📋 Development Process

### 1. Fork & Clone

```bash
git clone https://github.com/YOUR-USERNAME/oaep-php.git
cd oaep-php
composer install
```

### 2. Create a Branch

```bash
git checkout -b feature/your-feature-name
# or
git checkout -b fix/issue-number-description
```

### 3. Make Changes

- Follow PSR-12 coding standards
- Add PHPDoc comments
- Use strict type declarations
- Write tests for new features

### 4. Test Your Changes

```bash
# Run examples
php examples/simple-handshake.php
php examples/did-examples.php
php examples/vc-examples.php

# Code style check
composer cs-check

# Static analysis
composer analyse
```

### 5. Commit

Follow [Conventional Commits](https://www.conventionalcommits.org/):

```bash
git commit -m "feat: add StatusList2021 support"
git commit -m "fix: correct signature verification edge case"
git commit -m "docs: update API documentation"
```

### 6. Push & Pull Request

```bash
git push origin feature/your-feature-name
```

Then create a Pull Request on GitHub.

## 🎯 Contribution Guidelines

### Code Style

- **PSR-12** coding standard
- **Strict types** (`declare(strict_types=1);`)
- **Type hints** for all parameters and return types
- **PHPDoc** for all public methods
- **Descriptive variable names**

### Security

- Never commit private keys or secrets
- Follow security best practices from [SECURITY.md](SECURITY.md)
- Report security vulnerabilities privately (see SECURITY.md)

### Documentation

- Update README.md if adding features
- Add PHPDoc comments
- Include code examples
- Update CHANGELOG.md

### Testing

- Manual testing required for now
- Automated tests coming in v0.2
- Test all examples before submitting

## 🐛 Reporting Bugs

Use GitHub Issues with:

1. **Clear title**
2. **Steps to reproduce**
3. **Expected behavior**
4. **Actual behavior**
5. **Environment** (PHP version, OS)
6. **Code sample** (if applicable)

## 💡 Proposing Features

For major changes:

1. Open an **issue first** to discuss
2. Reference the **OAEP specification**
3. Explain **use case and rationale**
4. Consider **backward compatibility**

## 📜 License

By contributing, you agree that your contributions will be licensed under the CC BY-SA 4.0 license.

## 🙏 Code of Conduct

Be respectful, constructive, and professional. We're building technology for a better digital future together.

## 📞 Questions?

- GitHub Discussions: https://github.com/oap-foundation/oap-framework/discussions
- Issues: https://github.com/oap-foundation/oaep-php/issues

Thank you for contributing to OAEP! 🚀
