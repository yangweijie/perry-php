# Perry Feature Demo

Comprehensive example demonstrating all Perry PHP features: all 16 widget types, all action types (set, append, clear, closure), style properties, and state management.

```bash
# Generate HTML
php examples/perry-demo.php web > perry-demo.html

# Generate SwiftUI
php examples/perry-demo.php macos > ContentView.swift

# Generate Jetpack Compose
php examples/perry-demo.php compose > MainActivity.kt
```

## Features Demonstrated

| Feature | Widgets Used |
|---------|-------------|
| **Text & Styling** | Text with font size, weight, color, alignment, padding, borders, shadows, corner radius, opacity |
| **Counter** | Button with increment/decrement closures and Action::set reset |
| **Text Input** | TextInput with StateId, Action::fromClosure with conditional logic |
| **Toggle** | Toggle with Binding and closure action for dark mode |
| **Checkbox & Radio** | Checkbox and RadioButton with group selection |
| **Slider & Progress** | Slider for opacity control, Progress bar with random value |
| **Tab View** | TabView with 3 tabs |
| **List Widget** | ListWidget for item display |
| **Scroll View** | Scrollable container |
| **Image** | Image widget with styled placeholder |
| **Binding** | 7 Binding objects for reactive state |
| **AppContainer** | Root container with extra bindings |
| **Spacer** | Flexible space in layouts |

## Code

```php
<?php

use Perry\App;
use Perry\Build\Target;
use Perry\UI\Action;
use Perry\UI\Binding;
use Perry\UI\State;
use Perry\UI\Styling\Style;
use Perry\UI\Widget\AppContainer;
use Perry\UI\Widget\Button;
use Perry\UI\Widget\Checkbox;
use Perry\UI\Widget\HStack;
use Perry\UI\Widget\Image;
use Perry\UI\Widget\ListWidget;
use Perry\UI\Widget\Progress;
use Perry\UI\Widget\RadioButton;
use Perry\UI\Widget\ScrollView;
use Perry\UI\Widget\Slider;
use Perry\UI\Widget\Spacer;
use Perry\UI\Widget\TabView;
use Perry\UI\Widget\Text;
use Perry\UI\Widget\TextInput;
use Perry\UI\Widget\Toggle;
use Perry\UI\Widget\VStack;

// State bindings
$state = new State();
$nameInput = $state->create('');
$greeting  = new Binding('greeting', 'Hello, Perry!');
$counter   = new Binding('counter', 0);
$isDark    = new Binding('isDark', false);
$opacity   = new Binding('opacity', 1.0);
$progress  = new Binding('progress', 0.5);
$color     = new Binding('color', 'red');
$checked   = new Binding('checked', false);
$items     = new Binding('items', 'Perry PHP');

// ... (full source in examples/perry-demo.php)
```
