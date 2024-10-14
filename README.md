# KaririCode Framework: PropertyInspector Component

[![en](https://img.shields.io/badge/lang-en-red.svg)](README.md) [![pt-br](https://img.shields.io/badge/lang-pt--br-green.svg)](README.pt-br.md)

![PHP](https://img.shields.io/badge/PHP-777BB4?style=for-the-badge&logo=php&logoColor=white) ![Docker](https://img.shields.io/badge/Docker-2496ED?style=for-the-badge&logo=docker&logoColor=white) ![PHPUnit](https://img.shields.io/badge/PHPUnit-3776AB?style=for-the-badge&logo=php&logoColor=white)

A powerful and flexible component for inspecting and processing object properties based on custom attributes in the KaririCode Framework, providing advanced features for property validation, sanitization, and analysis in PHP applications.

## Table of Contents

- [Features](#features)
- [Installation](#installation)
- [Usage](#usage)
  - [Basic Usage](#basic-usage)
  - [Advanced Usage](#advanced-usage)
- [Integration with Other KaririCode Components](#integration-with-other-kariricode-components)
- [Development and Testing](#development-and-testing)
- [License](#license)
- [Support and Community](#support-and-community)
- [Acknowledgements](#acknowledgements)

## Features

- Easy inspection and processing of object properties based on custom attributes
- Support for both validation and sanitization of property values
- Flexible attribute handling through custom attribute handlers
- Seamless integration with other KaririCode components (Serializer, Validator, Normalizer)
- Extensible architecture allowing custom attributes and handlers
- Built on top of the KaririCode\Contract interfaces for maximum flexibility

## Installation

The PropertyInspector component can be easily installed via Composer, which is the recommended dependency manager for PHP projects.

To install the PropertyInspector component in your project, run the following command in your terminal:

```bash
composer require kariricode/property-inspector
```

This command will automatically add PropertyInspector to your project and install all necessary dependencies.

### Requirements

- PHP 8.1 or higher
- Composer

### Manual Installation

If you prefer not to use Composer, you can download the source code directly from the [GitHub repository](https://github.com/KaririCode-Framework/kariricode-property-inspector) and include it manually in your project. However, we strongly recommend using Composer for easier dependency management and updates.

After installation, you can start using PropertyInspector in your PHP project immediately. Make sure to include the Composer autoloader in your script:

```php
require_once 'vendor/autoload.php';
```

## Usage

### Basic Usage

1. Define your custom attributes and entity:

```php
use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
class Validate
{
    public function __construct(public readonly array $rules) {}
}

#[Attribute(Attribute::TARGET_PROPERTY)]
class Sanitize
{
    public function __construct(public readonly string $method) {}
}

class User
{
    public function __construct(
        #[Validate(['required', 'string', 'min:3'])]
        #[Sanitize('trim')]
        public string $name,
        #[Validate(['required', 'email'])]
        #[Sanitize('lowercase')]
        public string $email,
        #[Validate(['required', 'integer', 'min:18'])]
        public int $age
    ) {}
}
```

2. Create a custom attribute handler:

```php
use KaririCode\PropertyInspector\Contract\PropertyAttributeHandler;

class CustomAttributeHandler implements PropertyAttributeHandler
{
    public function handleAttribute(object $object, string $propertyName, object $attribute, mixed $value): ?string
    {
        if ($attribute instanceof Validate) {
            return $this->validate($propertyName, $value, $attribute->rules);
        }
        if ($attribute instanceof Sanitize) {
            return $this->sanitize($value, $attribute->method);
        }
        return null;
    }

    // Implement validate and sanitize methods...
}
```

3. Use the PropertyInspector:

```php
use KaririCode\PropertyInspector\AttributeAnalyzer;
use KaririCode\PropertyInspector\PropertyInspector;

$attributeAnalyzer = new AttributeAnalyzer(Validate::class);
$propertyInspector = new PropertyInspector($attributeAnalyzer);
$handler = new CustomAttributeHandler();

$user = new User('Walmir Silva', 'walmir@example.com', 25);

$results = $propertyInspector->inspect($user, $handler);
```

### Advanced Usage

You can create more complex validation and sanitization rules, and even combine the PropertyInspector with other components like the ProcessorPipeline for more advanced processing flows.

## Integration with Other KaririCode Components

The PropertyInspector component is designed to work seamlessly with other KaririCode components:

- **KaririCode\Serializer**: Use PropertyInspector to validate and sanitize data before serialization.
- **KaririCode\Validator**: Integrate custom validation logic with PropertyInspector attributes.
- **KaririCode\Normalizer**: Use PropertyInspector to normalize object properties based on attributes.

## Development and Testing

For development and testing purposes, this package uses Docker and Docker Compose to ensure consistency across different environments. A Makefile is provided for convenience.

### Prerequisites

- Docker
- Docker Compose
- Make (optional, but recommended for easier command execution)

### Development Setup

1. Clone the repository:

   ```bash
   git clone https://github.com/KaririCode-Framework/kariricode-property-inspector.git
   cd kariricode-property-inspector
   ```

2. Set up the environment:

   ```bash
   make setup-env
   ```

3. Start the Docker containers:

   ```bash
   make up
   ```

4. Install dependencies:
   ```bash
   make composer-install
   ```

### Available Make Commands

- `make up`: Start all services in the background
- `make down`: Stop and remove all containers
- `make build`: Build Docker images
- `make shell`: Access the PHP container shell
- `make test`: Run tests
- `make coverage`: Run test coverage with visual formatting
- `make cs-fix`: Run PHP CS Fixer to fix code style
- `make quality`: Run all quality commands (cs-check, test, security-check)

For a full list of available commands, run:

```bash
make help
```

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## Support and Community

- **Documentation**: [https://kariricode.org/docs/property-inspector](https://kariricode.org/docs/property-inspector)
- **Issue Tracker**: [GitHub Issues](https://github.com/KaririCode-Framework/kariricode-property-inspector/issues)
- **Community**: [KaririCode Club Community](https://kariricode.club)

## Acknowledgements

- The KaririCode Framework team and contributors.
- Inspired by attribute-based programming and reflection patterns in modern PHP applications.

---

Built with ❤️ by the KaririCode team. Empowering developers to create more robust and flexible PHP applications.
