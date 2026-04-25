# INURLBR v3.0 - Modernized Vulnerability Scanner

## 🚀 Quick Start

```bash
# Install dependencies
composer install

# Run scanner
php bin/inurlbr scan example.com

# With options
php bin/inurlbr scan example.com -e google,bing -t 10 -o results.json

# Use TOR
php bin/inurlbr scan example.com --tor --exploit
```

## 📋 Features

- **Modern PHP 8.3+** with strict typing
- **Symfony Console** for professional CLI experience
- **Guzzle HTTP Client** for robust HTTP requests
- **Monolog** for advanced logging
- **ReactPHP** ready for async operations
- **PSR-4 Autoloading** for clean architecture
- **PHPUnit** testing framework integrated

## 🏗️ Architecture

```
src/
├── Core/           # Base classes and interfaces
├── Engines/        # Search engine implementations
├── Validators/     # Vulnerability validators
├── Exploits/       # Exploitation modules
└── Utils/          # Helper utilities
```

## 🛠️ Development

```bash
# Run tests
composer test

# Static analysis
composer analyze

# Code formatting
composer fix

# Refactoring suggestions
composer refactor
```

## 📝 Configuration

Create `config/config.yaml` for custom settings:

```yaml
engines:
  google:
    enabled: true
    timeout: 30
    max_retries: 3
    
scanning:
  threads: 5
  rate_limit: 100
  
proxy:
  enabled: false
  type: http
  host: localhost
  port: 8080
```

## ⚠️ Legal Disclaimer

This tool is for educational and authorized security testing only. 
Always obtain proper authorization before scanning any target.

## 📄 License

GPL-3.0 - See LICENSE file for details

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch
3. Write tests
4. Submit a pull request

---

**Version**: 3.0.0 (Development)  
**Original Author**: Unknown  
**Modernization**: 2026
