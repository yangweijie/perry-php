# Perry PHP

Cross-platform UI abstraction and code generation framework. Define UI once in PHP, generate native code for Apple, Android, Windows, Linux, and Web platforms.

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

---

## Table of Contents

- [Installation](#installation)
- [Quick Start](#quick-start)
- [UI Components](#ui-components)
  - [Text — Text Display](#text)
  - [Button — Clickable Button](#button)
  - [VStack — Vertical Layout](#vstack)
  - [HStack — Horizontal Layout](#hstack)
  - [Spacer — Flexible Space](#spacer)
  - [Image — Image Display](#image)
  - [ScrollView — Scrollable Container](#scrollview)
  - [TextInput — Text Input Field](#textinput)
  - [Toggle — Toggle Switch](#toggle)
  - [AppContainer — Root App Container](#appcontainer)
- [State Management](#state-management)
  - [Binding — Reactive Data Binding](#binding)
  - [State / StateId — Low-level State](#state--stateid)
- [Actions](#actions)
  - [Simple Actions](#simple-actions)
  - [Closure Actions (AST-based)](#closure-actions)
  - [Supported PHP Features](#supported-php-features)
  - [PHP Function Mappings](#php-function-mappings)
- [Styling](#styling)
  - [Style Builder](#style-builder)
  - [Style Properties Reference](#style-properties-reference)
  - [Platform Support Matrix](#platform-support-matrix)
- [Code Generation](#code-generation)
  - [Backends](#backends)
  - [Generators](#generators)
  - [IR System](#ir-system)
- [Platform Support](#platform-support)
- [Build System](#build-system)
- [CLI Usage](#cli-usage)
- [Examples](#examples)
- [Extending Perry](#extending-perry)
  - [Adding a Custom Widget](#1-adding-a-custom-widget)
  - [Adding a Custom Backend](#2-adding-a-custom-backend)
  - [Adding a Custom Generator](#3-adding-a-custom-generator)
  - [Adding PHP Function Mappings](#4-adding-php-function-mappings)
- [Architecture](#architecture)

---

## Installation

```bash
composer require perry/perry
```

**Requirements**: PHP 8.2+

---

## Quick Start

```php
<?php

use Perry\App;
use Perry\UI\Binding;
use Perry\UI\Styling\Style;
use Perry\UI\Widget\Button;
use Perry\UI\Widget\HStack;
use Perry\UI\Widget\Text;
use Perry\UI\Widget\VStack;

$app = new App();

$root = new VStack(
    (new Text('Hello, Perry!'))->style(Style::make()->fontSize(24)),
    new HStack(
        (new Button('Click Me'))
            ->style(Style::make()->backgroundColor('#007AFF')->foregroundColor('#fff')),
        (new Button('Cancel'))
            ->style(Style::make()->backgroundColor('#f0f0f0')),
    ),
);

$app->setRoot($root);

echo $app->generateCode('swiftui');
```

---

## UI Components

All components extend `Perry\UI\Widget`. Each widget has:
- A **constructor** that accepts its specific parameters
- A **`kind()`** method returning the `WidgetKind` enum
- A **`style()`** method inherited from `Widget` for fluent styling
- A unique **`handle()`** (auto-generated `WidgetHandle`)

---

### Text

Displays static text or reactive bound data.

```php
use Perry\UI\Binding;
use Perry\UI\Widget\Text;

// Static text — renders literal string
$title = new Text('Hello, World!');

// Reactive text — renders binding value, auto-updates when state changes
$display = new Binding('display', '0');
$counter = new Text($display);
```

**Constructor:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$content` | `string\|Binding` | Static string or a `Binding` for reactive display |

**Methods:**

| Method | Returns | Description |
|--------|---------|-------------|
| `content()` | `string` | Text content (empty string if bound) |
| `getBinding()` | `?Binding` | The bound `Binding`, or `null` for static text |

**How bindings work:** When a `Text` widget receives a `Binding`, `AppContainer::bindings()` auto-collects it. The backend generates `@State` (Swift), `const state = {}` (JS), or `mutableStateOf` (Kotlin) for it.

**Generated code:**

```swift
// SwiftUI — static
Text("Hello, World!")

// SwiftUI — reactive (binding becomes a @State variable)
Text(display)

// HTML
<span id="display"></span>  <!-- JS updates via render() -->
```

**Full example — a live clock display:**

```php
use Perry\App;
use Perry\UI\AppContainer;
use Perry\UI\Binding;
use Perry\UI\Widget\Text;
use Perry\UI\Widget\VStack;

$time = new Binding('time', '00:00:00');
$date = new Binding('date', '2024-01-01');

$app = new App();
$app->setRoot(
    new AppContainer(
        new VStack(
            (new Text($time))->style(
                \Perry\UI\Styling\Style::make()->fontSize(32)->textAlignment('center')
            ),
            (new Text($date))->style(
                \Perry\UI\Styling\Style::make()->fontSize(16)->foregroundColor('#888')
            ),
        ),
        320, 200,  // window size
        $date,     // extra binding (not attached to a Text widget)
    )
);

echo $app->generateCode('html');
// Generates: const state = { time: "00:00:00", date: "2024-01-01" };
//            function render() { el_time.textContent = state.time; ... }
```

---

### Button

Clickable button with label and optional action.

```php
use Perry\UI\Action;
use Perry\UI\Binding;
use Perry\UI\Widget\Button;
use Perry\UI\Styling\Style;

$display = new Binding('display', '0');

// 1. Static button — no action
$ok = new Button('OK');

// 2. Simple action — set binding to value
$setZero = new Button('Reset', Action::set($display, '0'));

// 3. Append action — append string to binding
$addDigit = new Button('1', Action::append($display, '1'));

// 4. Closure action — full PHP logic → cross-platform code
$toggleSign = new Button('±', Action::fromClosure(function () use ($display) {
    if ($display[0] === '-') {
        $display = substr($display, 1);
    } else {
        $display = '-' . $display;
    }
}));

// 5. Closure with bindings — pass external values into closure
$button = new Button('×', Action::fromClosure(
    function () use ($display, $operand1, $operation) {
        $operand1 = floatval($display);
        $operation = '×';
        $display .= '×';
    },
    compact('operand1', 'operation')  // external bindings for replacement
));

// Styled button
$styled = (new Button('Submit', $toggleSign))
    ->style(Style::make()
        ->backgroundColor('#007AFF')
        ->foregroundColor('#ffffff')
        ->fontSize(18)
        ->padding(12)
        ->cornerRadius(8)
    );
```

**Constructor:**

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `$label` | `string` | — | Button text |
| `$action` | `Action\|\Closure\|null` | `null` | Click handler |

**Methods:**

| Method | Returns | Description |
|--------|---------|-------------|
| `label()` | `string` | Button label text |
| `getAction()` | `?Action` | Action object |

**Generated code (Swift):**

```swift
// Static button
Button(action: {}) {
    Text("OK")
}

// With closure action
Button(action: { display = "0" }) {
    Text("Reset")
}
```

**Generated code (HTML):**

```html
<!-- Static -->
<button>OK</button>

<!-- With action -->
<button onclick="action_0()">1</button>
<script>
function action_0() {
    state.display = state.display + "1"
    render();
}
</script>
```

---

### VStack

Vertical layout — arranges children top to bottom.

```php
use Perry\UI\Widget\VStack;
use Perry\UI\Widget\Text;
use Perry\UI\Widget\Button;

// Pass children as constructor arguments
$layout = new VStack(
    new Text('Header'),
    new Text('Body content goes here'),
    new Text('Footer'),
);
```

**Constructor:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `...$children` | `Widget` | Child widgets (variadic) |

**Spacing:** Controlled via `Style::padding()` on the VStack — the `padding` value becomes `spacing` in SwiftUI:

```php
use Perry\UI\Styling\Style;

$spaced = (new VStack(
    new Text('A'),
    new Text('B'),
    new Text('C'),
))->style(Style::make()->padding(16));  // 16px spacing between children
```

**Generated code:**

```swift
// SwiftUI
VStack(spacing: 16) {
    Text("A")
    Text("B")
    Text("C")
}
```

```html
<!-- HTML -->
<div class="vstack" style="padding: 16px">
    <span>A</span>
    <span>B</span>
    <span>C</span>
</div>
```

---

### HStack

Horizontal layout — arranges children left to right.

```php
use Perry\UI\Widget\HStack;
use Perry\UI\Widget\Button;
use Perry\UI\Widget\Spacer;

// Button row
$toolbar = new HStack(
    (new Button('Bold'))->style(Style::make()->fontSize(14)),
    (new Button('Italic'))->style(Style::make()->fontSize(14)),
    (new Button('Underline'))->style(Style::make()->fontSize(14)),
);

// Left-right layout with Spacer
$navbar = new HStack(
    new Text('Logo'),
    new Spacer(),  // pushes "Menu" to the right
    new Text('Menu'),
);
```

**Constructor:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `...$children` | `Widget` | Child widgets (variadic) |

**Generated code:**

```swift
// SwiftUI
HStack(spacing: 8) {
    Text("Logo")
    Spacer()
    Text("Menu")
}
```

---

### Spacer

Flexible space that expands to fill available area. Use inside `HStack` or `VStack` to push elements apart.

```php
use Perry\UI\Widget\HStack;
use Perry\UI\Widget\Text;
use Perry\UI\Widget\Spacer;

// "Left" is on the left, "Right" is on the right, Spacer fills the gap
$row = new HStack(
    new Text('Left'),
    new Spacer(),
    new Text('Right'),
);

// Vertical: pushes "Top" and "Bottom" apart
$column = new VStack(
    new Text('Top'),
    new Spacer(),
    new Text('Bottom'),
);
```

**Constructor:** No parameters.

**Generated code:**

```swift
// SwiftUI
HStack {
    Text("Left")
    Spacer()
    Text("Right")
}
```

```html
<!-- HTML -->
<div class="hstack">
    <span>Left</span>
    <div class="spacer"></div>
    <span>Right</span>
</div>
```

---

### Image

Displays an image from a path or resource name.

```php
use Perry\UI\Widget\Image;

// Local file
$logo = new Image('logo.png');

// Named asset
$avatar = new Image('avatar');
```

**Constructor:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$source` | `string` | Image path or asset name |

**Methods:**

| Method | Returns | Description |
|--------|---------|-------------|
| `source()` | `string` | Image source path |

**Generated code:**

```swift
// SwiftUI
Image("logo.png")
```

```html
<!-- HTML -->
<img src="logo.png" alt="">
```

---

### ScrollView

Scrollable container for content that exceeds the viewport.

```php
use Perry\UI\Widget\ScrollView;
use Perry\UI\Widget\VStack;
use Perry\UI\Widget\Text;

$list = new ScrollView(
    new VStack(
        new Text('Item 1'),
        new Text('Item 2'),
        new Text('Item 3'),
        // ... many items
    )
);
```

**Constructor:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `...$children` | `Widget` | Child widgets inside scroll area |

**Generated code:**

```swift
// SwiftUI
ScrollView {
    VStack(spacing: 8) {
        Text("Item 1")
        Text("Item 2")
        Text("Item 3")
    }
}
```

```html
<!-- HTML -->
<div style="overflow:auto;max-height:100vh">
    <div class="vstack">...</div>
</div>
```

---

### TextInput

Text input field with placeholder.

```php
use Perry\UI\State;
use Perry\UI\Widget\TextInput;

$state = new State();
$name = $state->create('');  // initial value: empty string

$input = new TextInput($name, 'Enter your name...');
```

**Constructor:**

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `$value` | `StateId` | — | State variable bound to input |
| `$placeholder` | `string` | `''` | Placeholder text |

**Methods:**

| Method | Returns | Description |
|--------|---------|-------------|
| `value()` | `StateId` | Bound state ID |
| `placeholder()` | `string` | Placeholder text |

**Generated code:**

```swift
// SwiftUI
TextField("Enter your name...", text: .constant(""))
```

```html
<!-- HTML -->
<input type="text" placeholder="Enter your name...">
```

---

### Toggle

Toggle switch with label.

```php
use Perry\UI\State;
use Perry\UI\Widget\Toggle;

$state = new State();
$darkMode = $state->create(false);

$toggle = new Toggle($darkMode, 'Dark Mode');
```

**Constructor:**

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `$isOn` | `StateId` | — | State variable bound to toggle |
| `$label` | `string` | `''` | Toggle label |

**Methods:**

| Method | Returns | Description |
|--------|---------|-------------|
| `isOn()` | `StateId` | Bound state ID |
| `label()` | `string` | Label text |

**Generated code:**

```swift
// SwiftUI
Toggle("Dark Mode", isOn: .constant(false))
```

```html
<!-- HTML -->
<div class="toggle">
    <input type="checkbox">
    <span>Dark Mode</span>
</div>
```

---

### AppContainer

Root application container. Wraps your widget tree, sets window dimensions, and auto-collects all `Binding` objects.

```php
use Perry\UI\AppContainer;
use Perry\UI\Binding;
use Perry\UI\Widget\VStack;
use Perry\UI\Widget\Text;
use Perry\UI\Widget\Button;
use Perry\UI\Action;
use Perry\UI\Styling\Style;

$count = new Binding('count', 0);
$label = new Binding('label', 'Clicks: 0');

$app = new AppContainer(
    // 1. Content widget tree
    new VStack(
        (new Text($label))->style(Style::make()->fontSize(24)),
        (new Button('Increment', function () use ($count, $label) {
            $count += 1;
            $label = 'Clicks: ' . strval($count);
        }))->style(Style::make()->backgroundColor('#007AFF')->foregroundColor('#fff')),
    ),
    // 2. Window size (optional)
    320,
    480,
    // 3. Extra bindings not attached to any Text widget
    $count,
);

$app2 = new App();
$app2->setRoot($app);
echo $app2->generateCode('html');
```

**Constructor:**

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `$content` | `Widget` | — | Root widget tree |
| `$windowWidth` | `?int` | `null` | Window width in pixels |
| `$windowHeight` | `?int` | `null` | Window height in pixels |
| `...$extraBindings` | `Binding` | — | Additional state bindings |

**Methods:**

| Method | Returns | Description |
|--------|---------|-------------|
| `content()` | `Widget` | Root widget |
| `windowWidth()` | `?int` | Window width |
| `windowHeight()` | `?int` | Window height |
| `bindings()` | `Binding[]` | All collected bindings |

**Binding collection logic:** `AppContainer` walks the entire widget tree and collects every `Binding` from `Text` widgets. Bindings passed as `...$extraBindings` are also included. This is how the backends know which `@State` / `const state` variables to declare.

---

## State Management

### Binding

Declarative, two-way data binding. The primary way to manage state.

```php
use Perry\UI\Binding;

$count = new Binding('count', 0);         // int
$display = new Binding('display', '0');    // string
$visible = new Binding('visible', true);   // bool
$opacity = new Binding('opacity', 1.0);    // float
```

**Constructor:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$name` | `string` | Variable name in generated code |
| `$initialValue` | `mixed` | Default value (`string`, `int`, `float`, `bool`) |

**How it works:**

1. Pass a `Binding` to a `Text` widget: `new Text($display)`
2. `AppContainer` auto-collects it
3. Backends generate state declarations:
   - **Swift**: `@State private var display = "0"`
   - **JavaScript**: `const state = { display: "0" }`
   - **Kotlin**: `var display = mutableStateOf("0")`
   - **Dart**: `var display = ValueNotifier("0")`
   - **C#**: `var display = "0";`
4. When a button action modifies `$display`, the generated code assigns to the state variable
5. Re-render updates all bound `Text` widgets

**Using `$count` in actions (closure):**

```php
$count = new Binding('count', 0);

// In a closure action, $count refers to the binding name in generated code
// The closure gets parsed → IR → target language assignment
$action = Action::fromClosure(function () use ($count) {
    $count += 1;
});

// Generated Swift: count = count + 1
// Generated JS:    state.count = state.count + 1
// Generated Kotlin: count.value = count.value + 1
```

---

### State / StateId

Lower-level state management for `TextInput` and `Toggle` widgets.

```php
use Perry\UI\State;

$state = new State();

// Create state entries
$name = $state->create('');           // StateId
$darkMode = $state->create(false);    // StateId
$speed = $state->create(1.0);         // StateId

// Read values
$currentName = $state->get($name);    // ''

// Update values
$state->set($name, 'Alice');

// Subscribe to changes (runtime only, not code-generated)
$state->subscribe($name, function (mixed $newValue) {
    echo "Name changed to: $newValue\n";
});
```

**When to use:**
- `Binding` — Most cases. Declarative, auto-collected by `AppContainer`.
- `State`/`StateId` — `TextInput` and `Toggle` widgets require `StateId`.

---

## Actions

### Simple Actions

Pre-built action types for common operations:

```php
use Perry\UI\Action;
use Perry\UI\Binding;

$display = new Binding('display', '0');

// SetValue — assign a value
$action = Action::set($display, '42');
$action = Action::set($display, true);     // bool
$action = Action::set($display, 3.14);    // float

// Append — concatenate a string
$action = Action::append($display, '1');   // display += "1"

// Clear — reset to initial value
$action = Action::clear($display);         // display = "0"

// Custom — raw platform-specific code
$action = Action::custom('display.text = ""');  // passed through as-is
```

### Widget Actions

Interactive widgets support `Action` for event handling:

```php
use Perry\UI\Action;
use Perry\UI\Binding;
use Perry\UI\Widget\Button;
use Perry\UI\Widget\Slider;
use Perry\UI\Widget\TextInput;
use Perry\UI\Widget\Toggle;

$display = new Binding('display', '0');
$operand1 = new Binding('operand1', 0.0);
$operation = new Binding('operation', '');

// Button — action on click
$btn = new Button('7', Action::append($display, '7'));

// Slider — action on value change
$slider = new Slider(0, 100, $operand1, onChange: Action::set($operand1, 50));

// TextInput — action on text change
$input = new TextInput($display, onChange: Action::set($display, ''));

// Toggle — action on toggle
$toggle = new Toggle(true, onToggle: Action::set($display, 'toggled'));
```

**Supported event properties:**

| Widget | Event Property | Description |
|-------|----------------|-------------|
| `Button` | `action` (constructor) | Fires on click/tap |
| `Slider` | `onChange` | Fires when value changes |
| `TextInput` | `onChange` | Fires when text changes |
| `Toggle` | `onToggle` | Fires when checked state changes |

**Action types work across all widgets:**

| ActionType | Button | Slider | TextInput | Toggle |
|------------|--------|--------|-----------|--------|
| `SetValue` | ✅ | ✅ | ✅ | ✅ |
| `Append` | ✅ | — | — | — |
| `Clear` | ✅ | — | — | — |
| `Custom` | ✅ | ✅ | ✅ | ✅ |
| `Closure` | ✅ | ✅ | ✅ | ✅ |

---

### Closure Actions

The most powerful action type. Write PHP closures that get parsed into an AST and cross-compiled to any target language.

```php
use Perry\UI\Action;
use Perry\UI\Binding;

$display = new Binding('display', '0');
$operand1 = new Binding('operand1', 0.0);
$operation = new Binding('operation', '');

$action = Action::fromClosure(
    function () use ($display, $operand1, $operation) {
        $operand1 = floatval($display);
        $operation = '+';
        $display .= '+';
    }
);
```

**How it works:**

```
PHP closure
    ↓ nikic/php-parser
PHP AST
    ↓ Perry\IR\AstToIrVisitor
Perry IR (54 node types)
    ↓ Perry\Generator\{Swift,JavaScript,Kotlin,Dart,CSharp}Generator
Target language code
```

**Closure bindings (parameter substitution):**

```php
// Pass external values into the closure at definition time
$action = Action::fromClosure(
    function () use ($display, $digit) {
        $display .= $digit;
    },
    ['digit' => '5']  // $digit is replaced with "5" in generated code
);

// Generated Swift: display = display + "5"
// Generated JS:    state.display = state.display + "5"
```

**Nested closure pattern (for parameterized buttons):**

```php
function numBtn(string $digit, Binding $display): Button {
    return new Button($digit, Action::fromClosure(
        function () use ($digit, $display) {
            $display .= $digit;
        },
        compact('digit')
    ));
}

$row = new HStack(
    numBtn('1', $display),
    numBtn('2', $display),
    numBtn('3', $display),
);
```

---

### Supported PHP Features

| Feature | Swift | JavaScript | Kotlin | Dart | C# |
|---------|-------|------------|--------|------|-----|
| Variables | `var x` | `let x` | `var x` | `var x` | `var x` |
| State vars | `x = ...` | `state.x = ...` | `x.value = ...` | `x.value = ...` | `x = ...` |
| If/else | `if {} else {}` | `if {} else {}` | `if {} else {}` | `if {} else {}` | `if {} else {}` |
| While | `while {}` | `while {}` | `while {}` | `while {}` | `while {}` |
| For | `for {}` | `for {}` | `for {}` | `for {}` | `for {}` |
| Foreach | `for x in y` | `for x of y` | `for x in y` | `for x in y` | `foreach x in y` |
| Ternary | `c ? a : b` | `c ? a : b` | `if (c) a else b` | `c ? a : b` | `c ? a : b` |
| Switch | `switch {}` | `switch {}` | `when {}` | `switch {}` | `switch {}` |
| Match | `match {}` | `switch+return` | `when->` | `switch+IIFE` | `switch expr` |
| Try/catch | `do{}catch{}` | `try{}catch{}` | `try{}catch{}` | `try{}catch{}` | `try{}catch{}` |
| Throw | `throw` | `throw` | `throw` | `throw` | `throw` |
| Type cast | `Int()`, `Double()` | `parseInt()`, `parseFloat()` | `.toInt()`, `.toDouble()` | `int.parse()`, `double.parse()` | `(int)`, `(double)` |
| Increment | `+= 1` | `x++` | `x++` | `x++` | `x++` |
| Compound assign | `+=`, `-=`, `*=`, `/=` | `+=`, `-=`, `*=`, `/=` | `+=`, `-=`, `*=`, `/=` | `+=`, `-=`, `*=`, `/=` | `+=`, `-=`, `*=`, `/=` |
| Nullsafe | `?.method()` | `?.method()` | `?.method()` | `?.method()` | `?.method()` |
| Static call | `Class.method()` | `Class.method()` | `Class.method()` | `Class.method()` | `Class.method()` |

---

### PHP Function Mappings

| PHP | Swift | JavaScript | Kotlin | Dart | C# |
|-----|-------|------------|--------|------|-----|
| `substr($s, -1)` | `String(s.last!)` | `s.slice(-1)` | `s.last().toString()` | `s[s.length-1]` | `s[s.Length-1]` |
| `substr($s, 0, -1)` | `String(s.dropLast(1))` | `s.slice(0,-1)` | `s.dropLast(1)` | `s.substring(0,s.length-1)` | `s.Substring(0,s.Length-1)` |
| `substr($s, 1)` | `String(s.dropFirst(1))` | `s.slice(1)` | `s.dropFirst(1)` | `s.substring(1)` | `s.Substring(1)` |
| `strlen($s)` | `s.count` | `s.length` | `s.length` | `s.length` | `s.Length` |
| `floatval($x)` | `Double(x) ?? 0` | `parseFloat(x)` | `x.toDoubleOrNull() ?: 0.0` | `double.parse(x.toString())` | `Convert.ToDouble(x)` |
| `intval($x)` | `Int(x)` | `parseInt(x)` | `x.toIntOrNull() ?: 0` | `int.parse(x.toString())` | `Convert.ToInt32(x)` |
| `strval($x)` | `String(x)` | `String(x)` | `x.toString()` | `x.toString()` | `x.ToString()` |
| `in_array($x, $a)` | `a.contains(x)` | `a.includes(x)` | `a.contains(x)` | `a.contains(x)` | `a.Contains(x)` |
| `strpos($s, $n)` | `s.firstIndex(of: n)` | IIFE with `indexOf` | `s.indexOf(n)` | `s.indexOf(n)` | `s.IndexOf(n)` |
| `end($a)` | `a.last!` | `a[a.length-1]` | `a.last()` | `a.last` | `a[a.Length-1]` |
| `number_format($n,$d)` | `String(format: "%.$df", $n)` | `n.toFixed(d)` | `String.format("%.$df",n)` | `n.toStringAsFixed(d)` | `$n.ToString("F$d")` |
| `floor($x)` | `floor(x)` | `Math.floor(x)` | `Math.floor(x).toInt()` | `x.floor()` | `Math.Floor(x)` |
| `empty($x)` | `x.isEmpty` | `!x` | `x.isEmpty()` | `x.isEmpty` | `string.IsNullOrEmpty(x)` |
| `count($a)` | `a.count` | `a.length` | `a.size` | `a.length` | `a.Length` |
| `preg_split('/[...]/', $s)` | `s.components(separatedBy:)` | `s.split(/regex/)` | `s.split().toRegex()` | `s.split(RegExp(...))` | `Regex.Split(s,...)` |

**`=== false` optimization:** All generators detect `expr === false` and emit `!expr` instead.

---

## Styling

### Style Builder

Fluent API — chain methods to compose styles:

```php
use Perry\UI\Styling\Style;

$cardStyle = Style::make()
    ->backgroundColor('#1a1a1a')
    ->foregroundColor('#ffffff')
    ->fontSize(16)
    ->fontWeight('bold')
    ->padding(16)
    ->paddingAll(8, 8, 12, 12)    // top, bottom, leading, trailing
    ->width(300)
    ->height(200)
    ->cornerRadius(12)
    ->border(1, '#333333')
    ->shadow('#000000', 4, 2, 2)
    ->opacity(0.9)
    ->textAlignment('center');

// Merge styles
$base = Style::make()->fontSize(14)->foregroundColor('#333');
$highlight = Style::make()->backgroundColor('#ffff00');
$merged = $base->merge($highlight);
```

**Methods:**

| Method | Parameters | Description |
|--------|-----------|-------------|
| `make()` | — | Create new Style |
| `set(StyleProperty, $value)` | enum, mixed | Set any property |
| `get(StyleProperty)` | enum | Get property value |
| `has(StyleProperty)` | enum | Check if property is set |
| `all()` | — | Get all properties |
| `merge(Style)` | Style | Merge another style (right wins) |
| `backgroundColor(hex)` | string | Background color |
| `foregroundColor(hex)` | string | Text/icon color |
| `fontSize(float)` | float | Font size in points |
| `fontWeight(string)` | string | Font weight (`'bold'`, `'light'`, etc.) |
| `textAlignment(string)` | string | Text alignment (`'center'`, `'left'`, `'right'`) |
| `padding(float)` | float | Uniform padding |
| `paddingAll(top, bottom, leading, trailing)` | float×4 | Individual padding |
| `width(float)` | float | Fixed width |
| `height(float)` | float | Fixed height |
| `cornerRadius(float)` | float | Corner radius |
| `opacity(float)` | float | Opacity (0.0–1.0) |
| `border(width, color)` | float, string | Border width + color |
| `shadow(color, radius, offsetX, offsetY)` | string, float×3 | Drop shadow |

---

### Style Properties Reference

| Property | Enum | Type | Description |
|----------|------|------|-------------|
| Background Color | `StyleProperty::BackgroundColor` | `string` | Background color (hex) |
| Foreground Color | `StyleProperty::ForegroundColor` | `string` | Text/icon color (hex) |
| Border Color | `StyleProperty::BorderColor` | `string` | Border color (hex) |
| Border Width | `StyleProperty::BorderWidth` | `float` | Border width |
| Corner Radius | `StyleProperty::CornerRadius` | `float` | Corner rounding |
| Opacity | `StyleProperty::Opacity` | `float` | Transparency (0–1) |
| Padding | `StyleProperty::Padding` | `float` | Uniform padding |
| Padding Top | `StyleProperty::PaddingTop` | `float` | Top padding |
| Padding Bottom | `StyleProperty::PaddingBottom` | `float` | Bottom padding |
| Padding Leading | `StyleProperty::PaddingLeading` | `float` | Left padding |
| Padding Trailing | `StyleProperty::PaddingTrailing` | `float` | Right padding |
| Margin | `StyleProperty::Margin` | `float` | Uniform margin |
| Width | `StyleProperty::Width` | `float` | Fixed width |
| Height | `StyleProperty::Height` | `float` | Fixed height |
| Font Size | `StyleProperty::FontSize` | `float` | Font size |
| Font Weight | `StyleProperty::FontWeight` | `string` | Font weight |
| Font Family | `StyleProperty::FontFamily` | `string` | Font family |
| Text Alignment | `StyleProperty::TextAlignment` | `string` | Text alignment |
| Shadow Color | `StyleProperty::ShadowColor` | `string` | Shadow color |
| Shadow Radius | `StyleProperty::ShadowRadius` | `float` | Shadow blur |
| Shadow Offset X | `StyleProperty::ShadowOffsetX` | `float` | Shadow X offset |
| Shadow Offset Y | `StyleProperty::ShadowOffsetY` | `float` | Shadow Y offset |
| Text Decoration | `StyleProperty::TextDecoration` | `string` | Text decoration |
| Line Spacing | `StyleProperty::LineSpacing` | `float` | Line spacing |
| Min Width | `StyleProperty::MinWidth` | `float` | Minimum width |
| Min Height | `StyleProperty::MinHeight` | `float` | Minimum height |
| Max Width | `StyleProperty::MaxWidth` | `float` | Maximum width |
| Max Height | `StyleProperty::MaxHeight` | `float` | Maximum height |

---

### Platform Support Matrix

All 6 backends now support the full set of **28 StyleProperties** and **event system** (Button/Slider/TextInput/Toggle Actions):

| Feature | macOS (SwiftUI) | iOS (SwiftUI) | Android (XML) | Android (Compose) | Web (HTML) | Linux (Gtk4) | Windows (WinUI) |
|---------|------------------|---------------|-----------------|--------------------|--------------|----------------|---------------|
| **StyleProperties** |
| BackgroundColor | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| ForegroundColor | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| BorderWidth/BorderColor | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| CornerRadius | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| Padding (all edges) | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| Margin (all edges) | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| Width / Height | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| FontSize / FontWeight / FontFamily | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| TextAlignment | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| Shadow (color/radius/offset) | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| TextDecoration | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| LineSpacing | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| Min/Max Width/Height | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| Opacity | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| **Event System** |
| Button action (Click) | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| Slider onChange | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| TextInput onChange | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| Toggle onToggle | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| **Widgets** |
| Slider / TextInput / Toggle | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| NavigationView / TabView | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| List | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |

```php
use Perry\UI\Styling\StyleMatrix;
use Perry\UI\Styling\StyleProperty;

$matrix = new StyleMatrix();

// Check a specific property on a platform
$support = $matrix->getSupport('macos', StyleProperty::CornerRadius);
// PlatformSupport::Wired (fully supported)

// Get all supported properties for a platform
$wired = $matrix->getWiredProperties('macos');

// Check if a platform has full support
$full = $matrix->isFullySupported('macos'); // bool

// Get missing properties
$missing = $matrix->getMissingProperties('android');
```

**Support levels:**

| Level | Description |
|-------|-------------|
| `Wired` | Fully supported, generates native code |
| `Stub` | Stub implementation (tvOS, visionOS, watchOS) |
| `Missing` | Not yet implemented |
| `NotApplicable` | Not applicable for this platform |

---

## Code Generation

### Backends

| Backend | Class | Platforms | Output |
|---------|-------|-----------|--------|
| `swiftui` | `SwiftUIBackend` | macOS, iOS, tvOS, visionOS, watchOS | SwiftUI Swift |
| `html` | `HtmlBackend` | Web, WebAssembly | HTML/CSS/JavaScript |
| `compose` | `ComposeBackend` | Android | Jetpack Compose Kotlin |
| `android-xml` | `AndroidXmlBackend` | Android | Android XML layouts |
| `winui` | `WinUIBackend` | Windows | WPF/WinUI XAML |
| `gtk4` | `Gtk4Backend` | Linux | GTK4 XML UI |

```php
use Perry\App;
use Perry\Build\Target;

$app = new App();
$app->setRoot($widgetTree);

// By name
echo $app->generateCode('swiftui');
echo $app->generateCode('html');

// Auto-detect from target
$app = new App(Target::fromString('macos'));
echo $app->generateForTarget();

// Write to file
$backend = $app->codegen()->get('html');
$backend->generateToFile($widgetTree, 'build/output.html');
```

---

### Generators

Generators transform IR into target language code. Each implements `Perry\IR\Generator`.

| Generator | Language | State Var | New Var |
|-----------|----------|-----------|---------|
| `SwiftGenerator` | Swift | `name = ...` | `var name = ...` |
| `JavaScriptGenerator` | JavaScript | `state.name = ...` | `let name = ...` |
| `KotlinGenerator` | Kotlin | `name.value = ...` | `var name = ...` |
| `DartGenerator` | Dart | `name.value = ...` | `var name = ...` |
| `CSharpGenerator` | C# | `name = ...` | `var name = ...` |

```php
use Perry\Generator\SwiftGenerator;
use Perry\IR\Assignment;
use Perry\IR\Literal;

$gen = new SwiftGenerator(stateVars: ['display', 'result']);
$ir = new Assignment('display', new Literal('Hello'));
echo $gen->generateAssignment($ir);
// Output: display = "Hello"

$ir2 = new Assignment('count', new Literal(42));
echo $gen->generateAssignment($ir2);
// Output: var count = 42  (new variable, not a state var)
```

---

### IR System

54 intermediate representation node types:

**Core (14):** Program, Assignment, IfStatement, BinaryOp, UnaryOp, Variable, Literal, FunctionCall, ReturnStatement, ArrayAccess, MethodCall, PropertyAccess, Ternary, ArrayLiteral

**Loops (5):** WhileStatement, ForStatement, ForeachStatement, BreakStatement, ContinueStatement

**Switch/Match (3):** SwitchStatement, CaseNode, MatchExpression

**Output (2):** EchoStatement, PrintStatement

**Type System (1):** Cast

**Inc/Dec (2):** Increment, Decrement

**Compound Assignment (5):** PlusAssign, MinusAssign, MulAssign, DivAssign, ModAssign

**Binary Ops (11):** PowOp, BitwiseAnd, BitwiseOr, BitwiseXor, ShiftLeft, ShiftRight, SpaceshipOp, CoalesceOp, LogicalAnd, LogicalOr, LogicalXor

**Unary Ops (2):** UnaryPlus, BitwiseNot

**Nullsafe (2):** NullsafeMethodCall, NullsafePropertyAccess

**Exceptions (3):** ThrowStatement, TryCatchStatement, CatchClause

**Static (3):** StaticCall, StaticPropertyAccess, ClassConstFetch

**Include (1):** IncludeStatement

---

## Platform Support

| Platform | Target String | Backend |
|----------|--------------|---------|
| macOS | `macos` | `swiftui` |
| iOS | `ios` | `swiftui` |
| iOS Simulator | `ios-simulator` | `swiftui` |
| tvOS | `tvos` | `swiftui` |
| visionOS | `visionos` | `swiftui` |
| watchOS | `watchos` | `swiftui` |
| Android | `android` | `compose` / `android-xml` |
| Linux | `gtk4-linux` | `gtk4` |
| Windows | `windows` | `winui` |
| Web | `web` | `html` |
| WebAssembly | `wasm` | `html` |

---

## Build System

```php
use Perry\Build\Target;

$target = Target::detect();            // auto-detect current platform
$target = Target::fromString('macos'); // from string

$target->isApple();    // true for macOS, iOS, tvOS, visionOS, watchOS
$target->isDesktop();  // true for macOS, Linux, Windows
$target->isMobile();   // true for iOS, Android, watchOS
```

**Compile to native app:**

```bash
php examples/calculator.php macos --build
# Output: build/Calculator.app

php examples/calculator.php web
# Output: build/calculator.html

php examples/pry.php windows --build
# Output: build/pry.exe
```

### Windows Requirements

Apps that use the `WebView` widget (e.g., the Pry JSON viewer) require **WebView2 Runtime** on Windows.

**Install WebView2 Runtime:**

- **Option 1 — Evergreen Bootstrapper** (recommended, auto-updates):  
  Download from [Microsoft Edge WebView2](https://developer.microsoft.com/en-us/microsoft-edge/webview2/):  
  https://go.microsoft.com/fwlink/p/?LinkId=2124703

- **Option 2 — Evergreen Standalone Installer**:  
  https://go.microsoft.com/fwlink/p/?LinkId=2124702

- **Option 3 — Fixed Version** (for offline/restricted environments):  
  https://developer.microsoft.com/en-us/microsoft-edge/webview2/#download-section

- **Check if already installed**:  
  Open `Control Panel → Programs and Features` and look for **WebView2 Runtime**.  
  Or check `C:\Program Files (x86)\Microsoft\EdgeWebView\Application\`.

  WebView2 ships with Microsoft Edge (Chromium-based), so it's often already present on modern Windows systems.

**Build output:** The compiler writes `pry.html` alongside the `.exe` file. The app reads it at runtime via WebView2's `NavigateToString()`.

---

## CLI Usage

```bash
./bin/perry info                   # platform info
./bin/perry demo --target=macos    # generate demo code
./bin/perry codegen --target=web   # generate for backend
./bin/perry compile --target=macos # compile to executable
./bin/perry targets                # list targets
./bin/perry backends               # list backends
```

---

## Examples

### Calculator

Full calculator with 7 actions, state management, and styling. Runs on macOS and web.

```php
<?php

use Perry\App;
use Perry\Build\Target;
use Perry\UI\Action;
use Perry\UI\AppContainer;
use Perry\UI\Binding;
use Perry\UI\Styling\Style;
use Perry\UI\Widget\Button;
use Perry\UI\Widget\HStack;
use Perry\UI\Widget\Text;
use Perry\UI\Widget\VStack;

// State
$display = new Binding('display', '0');
$result = new Binding('result', '');
$operand1 = new Binding('operand1', 0.0);
$operand2 = new Binding('operand2', 0.0);
$operation = new Binding('operation', '');
$isTyping = new Binding('isTyping', false);
$typed = new Binding('typed', '');

// Styles
$numberBtn = Style::make()->fontSize(24)->backgroundColor('#f0f0f0')->padding(20)->cornerRadius(8);
$opBtn = Style::make()->fontSize(24)->backgroundColor('#FF9500')->foregroundColor('#fff')->padding(20)->cornerRadius(8);

// Digit button factory
function numBtn(string $d, Binding $display, Binding $op2, Binding $typing, Binding $typed, Binding $op): Button {
    return (new Button($d, Action::fromClosure(
        function () use ($d, $display, $op2, $typing, $typed, $op) {
            if ($typing) {
                $typed .= $d;
                $display .= $d;
            } else {
                $typed = $d;
                $display = $d;
                $typing = true;
            }
            $op2 = floatval($typed);
        },
        compact('d')
    )))->style(Style::make()->fontSize(24)->backgroundColor('#f0f0f0')->padding(20)->cornerRadius(8));
}

// Build
$app = new App(Target::fromString('macos'));
$app->setRoot(
    new AppContainer(
        new VStack(
            (new Text($result))->style(Style::make()->fontSize(16)->foregroundColor('#888')),
            (new Text($display))->style(Style::make()->fontSize(32)->padding(16)),
            new HStack(
                numBtn('7', $display, $operand2, $isTyping, $typed, $operation),
                numBtn('8', $display, $operand2, $isTyping, $typed, $operation),
                numBtn('9', $display, $operand2, $isTyping, $typed, $operation),
                (new Button('×', Action::fromClosure(function () use ($display, $operand1, $operation, $typed) {
                    $operand1 = floatval($typed);
                    $operation = '×';
                    $display .= '×';
                    $typed = '';
                })))->style($opBtn),
            ),
            // ... more rows
        ),
        320, 480,
        $operand1, $operand2, $operation, $isTyping, $typed
    )
);

echo $app->generateForTarget();
```

### Simple Counter

```php
<?php

use Perry\App;
use Perry\UI\Action;
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
            (new Button('-', Action::fromClosure(function () use ($count) {
                $count -= 1;
            })))->style(Style::make()->fontSize(24)->padding(16)),
            (new Button('+', Action::fromClosure(function () use ($count) {
                $count += 1;
            })))->style(Style::make()->fontSize(24)->padding(16)),
        ),
    )
);

echo $app->generateCode('html');
```

### Todo List

```php
<?php

use Perry\App;
use Perry\UI\Action;
use Perry\UI\Binding;
use Perry\UI\Styling\Style;
use Perry\UI\Widget\Button;
use Perry\UI\Widget\HStack;
use Perry\UI\Widget\ScrollView;
use Perry\UI\Widget\Text;
use Perry\UI\Widget\VStack;
use Perry\UI\Widget\AppContainer;

$items = new Binding('items', 'Buy milk');
$ newItem = new Binding('newItem', '');

$app = new App();
$app->setRoot(
    new AppContainer(
        new VStack(
            (new Text($items))->style(Style::make()->fontSize(16)),
            new HStack(
                (new Button('Add', Action::fromClosure(function () use ($items, $newItem) {
                    $items .= "\n" . $newItem;
                    $newItem = '';
                })))->style(Style::make()->backgroundColor('#007AFF')->foregroundColor('#fff')),
                (new Button('Clear', Action::fromClosure(function () use ($items) {
                    $items = '';
                })))->style(Style::make()->backgroundColor('#ff3b30')->foregroundColor('#fff')),
            ),
        ),
        320, 480,
        $newItem
    )
);

echo $app->generateCode('swiftui');
```

### Pry — JSON Viewer (with WebView2 on Windows)

A native JSON viewer with tree view, search, syntax highlighting, and clipboard support. Uses `WebView` widget to embed a full HTML/JS UI.

```bash
php examples/pry.php windows --build
# Output: build/pry.exe + build/pry.html

# Also works on other platforms:
php examples/pry.php macos --build
php examples/pry.php web
```

**Note for Windows:** Requires [WebView2 Runtime](#windows-requirements). The generated `pry.html` file contains the complete viewer UI (tree rendering, search, context menus) and is loaded at runtime.

---

## Extending Perry

### 1. Adding a Custom Widget

Create a new widget class, add it to `WidgetKind`, and update all backends to handle it.

**Step 1: Create the widget class**

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
        return WidgetKind::Slider;  // must add this enum case first
    }

    public function min(): float { return $this->min; }
    public function max(): float { return $this->max; }
    public function step(): float { return $this->step; }
    public function getValue(): ?\Perry\UI\Binding { return $this->value; }
}
```

**Step 2: Add enum case to WidgetKind**

```php
// src/UI/WidgetKind.php
enum WidgetKind: int
{
    // ... existing cases ...
    case Slider = 9;
    case List = 10;
    // ...
}
```

**Step 3: Update each backend to generate code for Slider**

```php
// In SwiftUIBackend.php — add case to generateWidget()
WidgetKind::Slider => $this->generateSlider($widget),

// Add the method
private function generateSlider(Slider $widget): string
{
    $min = $widget->min();
    $max = $widget->max();
    $step = $widget->step();
    $binding = $widget->getValue();
    $value = $binding ? $binding->name : '0.0';
    $mods = $this->generateModifiers($widget->getStyle());
    return "Slider(value: \${$value}, in: {$min}...{$max}, step: {$step}){$mods}";
}

// In HtmlBackend.php — add case to generateWidget()
WidgetKind::Slider => $this->generateSlider($widget),

private function generateSlider(Slider $widget): string
{
    $min = $widget->min();
    $max = $widget->max();
    $step = $widget->step();
    $id = $widget->handle();
    $style = $this->generateStyle($widget->getStyle());
    return "<input type=\"range\" id=\"{$id}\" min=\"{$min}\" max=\"{$max}\" step=\"{$step}\"{$style}>";
}
```

**Step 4: Repeat for each backend** (KotlinGenerator, DartGenerator, CSharpGenerator, etc.)

---

### 2. Adding a Custom Backend

A backend converts the widget tree into target platform code.

**Step 1: Create the backend class**

```php
<?php

declare(strict_types=1);

namespace Perry\Codegen;

use Perry\Build\Target;
use Perry\UI\Widget;
use Perry\UI\Widget\AppContainer;
use Perry\UI\WidgetKind;

final class FlutterBackend extends CodegenBackend
{
    public function name(): string
    {
        return 'flutter';
    }

    public function supports(Target $target): bool
    {
        return $target === Target::Android;  // or a custom Flutter target
    }

    public function generate(Widget $root): string
    {
        if ($root instanceof AppContainer) {
            return $this->generateApp($root);
        }
        return $this->generateWidget($root);
    }

    private function generateWidget(Widget $widget): string
    {
        return match ($widget->kind()) {
            WidgetKind::Text => $this->generateText($widget),
            WidgetKind::Button => $this->generateButton($widget),
            WidgetKind::VStack => $this->generateVStack($widget),
            // ... handle all widget kinds
            default => 'SizedBox()',
        };
    }

    private function generateText(\Perry\UI\Widget\Text $widget): string
    {
        $binding = $widget->getBinding();
        $content = $binding ? "\${{$binding->name}}" : "'{$widget->content()}'";
        return "Text({$content})";
    }

    private function generateButton(\Perry\UI\Widget\Button $widget): string
    {
        $label = $widget->label();
        $action = $this->generateAction($widget->getAction());
        return "ElevatedButton(onPressed: () {{ {$action} }}, child: Text('{$label}'))";
    }

    private function generateVStack(\Perry\UI\Widget\VStack $widget): string
    {
        $children = array_map(
            fn($c) => $this->generateWidget($c),
            $widget->children()
        );
        $body = implode(",\n        ", $children);
        return "Column(\n    children: [\n        {$body}\n    ]\n)";
    }

    private function generateAction(?\Perry\UI\Action $action): string
    {
        if ($action === null) return '';
        if ($action->type === \Perry\UI\ActionType::Closure) {
            $gen = new \Perry\Generator\DartGenerator();
            return $action->generate($gen);
        }
        return '';
    }
}
```

**Step 2: Register in CodegenFactory**

```php
// src/Codegen/CodegenFactory.php
public function __construct()
{
    $this->register(new SwiftUIBackend());
    $this->register(new HtmlBackend());
    $this->register(new ComposeBackend());
    $this->register(new AndroidXmlBackend());
    $this->register(new WinUIBackend());
    $this->register(new Gtk4Backend());
    $this->register(new FlutterBackend());  // ← add here
}
```

**Step 3: Use it**

```php
$app = new App();
$app->setRoot($widgetTree);
echo $app->generateCode('flutter');
```

---

### 3. Adding a Custom Generator

A generator converts IR nodes into target language code.

**Step 1: Create the generator class**

```php
<?php

declare(strict_types=1);

namespace Perry\Generator;

use Perry\IR\Generator as GeneratorInterface;
use Perry\IR\*;

final class RustGenerator implements GeneratorInterface
{
    private array $stateVars;
    private array $declaredVars = [];

    public function __construct(array $stateVars = [])
    {
        $this->stateVars = array_flip($stateVars);
    }

    // Core
    public function generateProgram(Program $node): string
    {
        $lines = [];
        foreach ($node->statements as $stmt) {
            $lines[] = $stmt->accept($this);
        }
        return implode("\n", $lines);
    }

    public function generateAssignment(Assignment $node): string
    {
        $name = $node->variable;
        $value = $node->value->accept($this);

        if (isset($this->stateVars[$name])) {
            return "*{$name}.borrow_mut() = {$value}";
        }

        if (!in_array($name, $this->declaredVars)) {
            $this->declaredVars[] = $name;
            return "let mut {$name} = {$value}";
        }

        "{$name} = {$value}";
    }

    public function generateVariable(Variable $node): string
    {
        $name = $node->name;
        if (isset($this->stateVars[$name])) {
            return "{$name}.borrow()";
        }
        return $name;
    }

    public function generateLiteral(Literal $node): string
    {
        if (is_string($node->value)) {
            return "\"{$node->value}\"";
        }
        if (is_bool($node->value)) {
            return $node->value ? 'true' : 'false';
        }
        return (string) $node->value;
    }

    public function generateBinaryOp(BinaryOp $node): string
    {
        $left = $node->left->accept($this);
        $right = $node->right->accept($this);
        $op = match ($node->op) {
            '.' => '+',
            '===' => '==',
            '!==' => '!=',
            default => $node->op,
        };
        return "{$left} {$op} {$right}";
    }

    // ... implement all 50+ methods from the Generator interface
    // Copy the pattern from SwiftGenerator.php and adapt to Rust syntax

    public function generateIf(IfStatement $node): string { /* ... */ }
    public function generateWhile(WhileStatement $node): string { /* ... */ }
    public function generateFor(ForStatement $node): string { /* ... */ }
    // ... etc
}
```

**Step 2: Use it with a backend**

```php
use Perry\Generator\RustGenerator;

$gen = new RustGenerator(stateVars: ['display', 'count']);
$action = Action::fromClosure(function () use ($display) {
    $display = 'Hello';
});
echo $action->generate($gen);
// Output: *display.borrow_mut() = "Hello"
```

---

### 4. Adding PHP Function Mappings

Each generator maps PHP built-in functions to target language equivalents.

**Example: Add `array_map()` support to SwiftGenerator**

```php
// In SwiftGenerator.php — add case in generateFunctionCall()

case 'array_map':
    // array_map(fn($x) => ..., $array) → array.map { x in ... }
    $callback = $args[0] ?? null;
    $array = $args[1]->accept($this) ?? '[]';
    if ($callback instanceof \Perry\IR\Closure) {
        $param = $callback->params[0] ?? 'item';
        $body = $callback->body->accept($this);
        return "{$array}.map {{ {$param} in {$body} }}";
    }
    return "{$array}.map {{ $0 }}";

case 'array_filter':
    // array_filter($array, fn($x) => ...) → array.filter { x in ... }
    $array = $args[0]->accept($this) ?? '[]';
    $callback = $args[1] ?? null;
    if ($callback instanceof \Perry\IR\Closure) {
        $param = $callback->params[0] ?? 'item';
        $body = $callback->body->accept($this);
        return "{$array}.filter {{ {$param} in {$body} }}";
    }
    return "{$array}.filter {{ $0 }}";
```

**To add support across all generators:**

1. Add the mapping in `SwiftGenerator.php`
2. Add the mapping in `JavaScriptGenerator.php`
3. Add the mapping in `KotlinGenerator.php`
4. Add the mapping in `DartGenerator.php`
5. Add the mapping in `CSharpGenerator.php`
6. Add tests in `tests/Generator/`

---

## Architecture

```
Perry/
├── App.php                         # Entry point: setRoot(), generateCode(), run()
├── Build/
│   ├── Target.php                  # Platform enum (11 targets)
│   ├── TargetDetector.php          # Auto-detect current platform
│   ├── BuildPipeline.php           # Build orchestration
│   ├── Compiler.php                # Invoke platform toolchains
│   ├── LibraryResolver.php         # Find platform libraries
│   └── Linker.php                  # Platform-specific linking
├── Codegen/
│   ├── CodegenBackend.php          # Abstract backend: name(), supports(), generate()
│   ├── CodegenFactory.php          # Backend registry & factory
│   ├── SwiftUIBackend.php          # SwiftUI → Swift
│   ├── HtmlBackend.php             # Widget tree → HTML/CSS/JS
│   ├── ComposeBackend.php          # Widget tree → Jetpack Compose
│   ├── AndroidXmlBackend.php       # Widget tree → Android XML
│   ├── WinUIBackend.php            # Widget tree → WinUI XAML
│   └── Gtk4Backend.php             # Widget tree → GTK4 XML
├── Generator/
│   ├── SwiftGenerator.php          # IR → Swift code
│   ├── JavaScriptGenerator.php     # IR → JavaScript code
│   ├── KotlinGenerator.php         # IR → Kotlin code
│   ├── DartGenerator.php           # IR → Dart code
│   └── CSharpGenerator.php         # IR → C# code
├── IR/
│   ├── Node.php                    # 54 IR node types
│   ├── Generator.php               # Generator interface (50+ methods)
│   ├── AstToIrVisitor.php          # PHP AST → IR transformer
│   └── Builder.php                 # Closure → IR via nikic/php-parser
├── UI/
│   ├── Widget.php                  # Base class: handle, kind, style, children
│   ├── WidgetHandle.php            # Unique widget ID
│   ├── WidgetKind.php              # Widget type enum (13 cases)
│   ├── Action.php                  # 6 action types + fromClosure()
│   ├── Binding.php                 # Reactive data binding
│   ├── State.php                   # State management (create, get, set, subscribe)
│   ├── StateId.php                 # State identifier
│   ├── Widget/
│   │   ├── Text.php                # Text display (string | Binding)
│   │   ├── Button.php              # Button (label, Action)
│   │   ├── VStack.php              # Vertical layout
│   │   ├── HStack.php              # Horizontal layout
│   │   ├── Spacer.php              # Flexible space
│   │   ├── Image.php               # Image display
│   │   ├── ScrollView.php          # Scrollable container
│   │   ├── TextInput.php           # Text input (StateId)
│   │   ├── Toggle.php              # Toggle switch (StateId)
│   │   └── AppContainer.php        # Root container + binding collector
│   ├── Styling/
│   │   ├── Style.php               # Fluent style builder
│   │   ├── StyleProperty.php       # 28 style properties
│   │   └── StyleMatrix.php         # Platform support matrix
│   └── Platform/
│       ├── PlatformDriver.php      # Platform interface
│       ├── DriverFactory.php       # Create driver for target
│       └── *Driver.php             # Platform-specific drivers
└── bin/
    └── perry                       # CLI entry point
```

---

## License

MIT
