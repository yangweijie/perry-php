# Contributing

Thank you for your interest in contributing to Perry PHP! We welcome contributions of all kinds — bug fixes, new features, documentation improvements, and more.

---

## Code of Conduct

Be respectful, constructive, and inclusive. Harassment and discriminatory behavior will not be tolerated.

---

## Getting Started

### Prerequisites

- PHP 8.2+
- Composer
- Node.js 18+ (for docs)

### Setup

```bash
# Clone the repository
git clone https://github.com/yangweijie/perry-php.git
cd perry-php

# Install PHP dependencies
composer install

# Install docs dependencies (optional, for docs development)
npm install
```

### Run Tests

```bash
# Run all tests
composer test

# Or with Pest directly (for more output)
./vendor/bin/pest

# Run a specific test file
./vendor/bin/pest tests/Codegen/SwiftUIBackendTest.php
```

---

## Project Structure

```
src/
├── App.php              # Entry point
├── Build/               # Build pipeline
├── Codegen/             # 11 platform code generators
├── Generator/           # 5 language generators
├── IR/                  # 54 IR node types
└── UI/                  # DSL: 16 widgets, styling, actions
    ├── Widget/          # Widget classes
    ├── Styling/         # Style system
    └── Platform/        # Platform drivers
tests/                   # Pest test files
docs/                    # VuePress documentation
examples/                # Example applications
```

---

## How to Contribute

### 1. Adding a New Widget

See [Extending Perry — Custom Widget](/guide/extending.html#1-adding-a-custom-widget) for the full guide.

Checklist:
- [ ] Create the widget class in `src/UI/Widget/`
- [ ] Add enum case to `src/UI/WidgetKind.php`
- [ ] Add test in `tests/Codegen/` (at minimum a smoke test)
- [ ] Update all 11 backends to handle the new widget kind
- [ ] Update docs in `docs/guide/ui-components.md`
- [ ] Update `docs/guide/api-reference.md`

### 2. Adding a New Backend

See [Extending Perry — Custom Backend](/guide/extending.html#2-adding-a-custom-backend).

Checklist:
- [ ] Create backend class in `src/Codegen/`
- [ ] Register in `src/Codegen/CodegenFactory.php`
- [ ] Implement `supportedStyleProperties()`
- [ ] Create test file in `tests/Codegen/`
- [ ] Add docs in `docs/guide/code-generation.md`

### 3. Adding PHP Function Mappings

See [Extending Perry — Function Mappings](/guide/extending.html#4-adding-php-function-mappings).

Checklist:
- [ ] Add to all 5 generators (`Swift`, `JavaScript`, `Kotlin`, `Dart`, `CSharp`)
- [ ] Add tests in `tests/Generator/`
- [ ] Update mapping table in `docs/guide/actions.md`

### 4. Improving Documentation

Documentation lives in `docs/` and uses VuePress.

```bash
# Start dev server
npm run docs:dev

# Build for production
npm run docs:build
```

Documentation contributions are especially welcome:
- Fix typos or unclear explanations
- Add more code examples
- Improve the API reference
- Add Chinese translations (see `docs/zh/`)

### 5. Reporting Bugs

Open a [GitHub Issue](https://github.com/yangweijie/perry-php/issues) with:

- **Description**: What happened vs what was expected
- **Reproduction**: Minimal PHP code that demonstrates the issue
- **Environment**: PHP version, OS, target platform
- **Test output**: Include relevant test results or error messages

---

## Coding Standards

### PHP

- **Strict types**: All files must start with `declare(strict_types=1);`
- **PSR-4**: Autoloading follows PSR-4
- **PSR-12**: Code style follows PSR-12
- **Type hints**: Use typed properties and return types
- **Docblocks**: Use PHPDoc for public APIs

```php
<?php

declare(strict_types=1);

namespace Perry\UI\Widget;

use Perry\UI\Widget;
use Perry\UI\WidgetKind;

final class Slider extends Widget
{
    public function __construct(
        private float $min = 0.0,
        private float $max = 1.0,
        private float $step = 0.1,
        private ?\Perry\UI\Binding $value = null,
    ) {
        parent::__construct();
    }

    public function kind(): WidgetKind
    {
        return WidgetKind::Slider;
    }
}
```

### Tests

- Use **Pest PHP** framework
- Tests go in `tests/` mirroring `src/` structure
- Name test files with `*Test.php` suffix
- Test at minimum: widget generates non-empty output, no crashes

```php
<?php

use Perry\UI\Widget\Text;

it('generates text widget', function () {
    $widget = new Text('Hello');
    // ... assert generated output
});
```

### Documentation

- Use **British English** spelling
- Code examples must be **testable** (copy-paste runnable)
- Use fenced code blocks with language tags: ` ```php `
- Tables with consistent alignment

---

## Pull Request Process

1. **Fork** the repository
2. **Create a branch**: `git checkout -b feature/your-feature`
3. **Make changes** following the coding standards
4. **Add tests** for new functionality
5. **Run tests**: `composer test` — all must pass
6. **Update docs** if API or behavior changed
7. **Commit** with clear, descriptive messages
8. **Push** to your fork
9. **Open a Pull Request** against `main`

### PR Checklist

- [ ] Tests pass (`composer test`)
- [ ] New tests added for new code
- [ ] Documentation updated
- [ ] Code follows PSR-12 standards
- [ ] No dead code or commented-out code
- [ ] Single purpose per PR

---

## Docs Development

```bash
# Install
npm install

# Start dev server (hot-reload at http://localhost:8080/perry-php/)
npm run docs:dev

# Build
npm run docs:build

# Preview production build
npx serve docs/.vuepress/dist
```

The docs are automatically deployed to GitHub Pages when changes are pushed to `main`.

---

## Release Process

1. Update version in `composer.json` (semantic versioning)
2. Update `CHANGELOG.md`
3. Tag the release: `git tag v1.x.x && git push --tags`
4. GitHub Actions builds and publishes to Packagist

---

## Questions?

Open a [Discussion](https://github.com/yangweijie/perry-php/discussions) or [Issue](https://github.com/yangweijie/perry-php/issues) on GitHub.
