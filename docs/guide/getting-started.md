# Getting Started

## Installation

```bash
composer require perry/perry
```

**Requirements:** PHP 8.2+

## Your First App

Create a simple counter app:

```php
<?php

use Perry\App;
use Perry\UI\Binding;
use Perry\UI\Styling\Style;
use Perry\UI\Widget\Button;
use Perry\UI\Widget\HStack;
use Perry\UI\Widget\Text;
use Perry\UI\Widget\VStack;

$count = new Binding('count', 0);

$app = new App();
$app->setRoot(
    new VStack(
        (new Text($count))->style(Style::make()->fontSize(48)),
        new HStack(
            (new Button('-', function () use ($count) {
                $count -= 1;
            }))->style(Style::make()->fontSize(24)->padding(16)),
            (new Button('+', function () use ($count) {
                $count += 1;
            }))->style(Style::make()->fontSize(24)->padding(16)),
        ),
    )
);

// Generate for web
echo $app->generateCode('html');
```

## Generate Code

```bash
# Generate SwiftUI code (macOS/iOS)
php your-app.php > App.swift

# Generate HTML
php your-app.php > index.html

# Generate Jetpack Compose
php your-app.php > MainActivity.kt

# Generate WPF/XAML (Windows)
php your-app.php > MainWindow.xaml

# Generate GTK4 XML (Linux)
php your-app.php > app.ui

# Generate ArkTS (HarmonyOS)
php your-app.php > pages/index.ets

# Generate Flutter Dart
php your-app.php > main.dart
```

## Using the CLI

```bash
./bin/perry info                   # Platform info
./bin/perry demo --target=macos    # Generate demo code
./bin/perry codegen --target=web   # Generate for backend
./bin/perry compile --target=macos # Compile to executable
./bin/perry targets                # List all 15 targets
./bin/perry backends               # List codegen backends
```

## File Structure

```
src/
├── App.php              # Entry point: setRoot, generateCode, generateForTarget
├── Build/               # Build pipeline: Target, Compiler, Linker
├── Codegen/             # 11 platform code generators
├── Generator/           # 5 language generators (Swift, Kotlin, Dart, JS, C#)
├── IR/                  # 54 IR node types for Closure transpilation
└── UI/                  # DSL: 16 widgets, 29 style properties, platform drivers
```
