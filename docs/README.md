---
home: true
title: Home
heroText: Perry PHP
tagline: Define UI once in PHP. Generate native code for 11 platforms.
actions:
  - text: Get Started
    link: /guide/getting-started.html
    type: primary
  - text: View on GitHub
    link: https://github.com/mikonos/perry-php
    type: secondary
features:
  - title: One DSL, 11 Platforms
    details: SwiftUI (macOS/iOS), HTML/JS (Web), Jetpack Compose (Android), Android XML, GTK4 (Linux), WinUI (Windows), ArkTS (HarmonyOS), Glance, Wear Tiles, Flutter.
  - title: Closure → Cross-Platform Code
    details: Write PHP closures with full logic (if/else, loops, math). The AST-based transpiler cross-compiles to Swift, JavaScript, Kotlin, Dart, and C#.
  - title: Full Style System
    details: 29 style properties — colors, fonts, padding, shadows, transforms, animations. Each backend maps to native styling APIs.
  - title: Reactive State
    details: Binding objects for two-way reactive data flow. Auto-collected by AppContainer, emitted as @State, useState, or mutableStateOf.
  - title: 978 Tests, All Passing
    details: 3689 assertions across 11 backends, 5 generators, 54 IR node types, and 97+ PHP function mappings.
  - title: No Runtime
    details: Code generation only — no PHP runtime on target devices. Your PHP code becomes pure native code.
footer: MIT Licensed | Copyright © 2024 Perry PHP
---

## Quick Example

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

// Generate for any platform
echo $app->generateCode('swiftui');   // macOS/iOS → SwiftUI Swift
echo $app->generateCode('html');      // Web → HTML/CSS/JS
echo $app->generateCode('compose');   // Android → Jetpack Compose
```
