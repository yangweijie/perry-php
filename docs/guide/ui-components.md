# UI Components

All widgets extend `Perry\UI\Widget`. Each widget has:
- A **constructor** that accepts its specific parameters
- A **`kind()`** method returning the `WidgetKind` enum
- A **`style()`** method inherited from `Widget` for fluent styling
- A unique **`handle()`** (auto-generated `WidgetHandle`)

---

## Text

Displays static text or reactive bound data.

```php
use Perry\UI\Binding;
use Perry\UI\Widget\Text;

// Static text
$title = new Text('Hello, World!');

// Reactive text — auto-updates when state changes
$display = new Binding('display', '0');
$counter = new Text($display);
```

**Constructor:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$content` | `string\|Binding` | Static string or Binding for reactive display |

### How Bindings Work

When a `Text` widget receives a `Binding`, `AppContainer::bindings()` auto-collects it. The backend generates `@State` (Swift), `const state = {}` (JS), or `mutableStateOf` (Kotlin) for it.

**Generated code:**
```swift
// SwiftUI — static
Text("Hello, World!")

// SwiftUI — reactive (binding becomes a @State variable)
Text(display)
```

---

## Button

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

// 3. Append action
$addDigit = new Button('1', Action::append($display, '1'));

// 4. Closure action — full PHP logic → cross-platform code
$toggleSign = new Button('±', Action::fromClosure(function () use ($display) {
    if ($display[0] === '-') {
        $display = substr($display, 1);
    } else {
        $display = '-' . $display;
    }
}));

// Styled button
$styled = (new Button('Submit'))
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

---

## VStack

Vertical layout — arranges children top to bottom.

```php
use Perry\UI\Widget\VStack;
use Perry\UI\Widget\Text;

$layout = new VStack(
    new Text('Header'),
    new Text('Body'),
    new Text('Footer'),
);
```

Spacing: controlled via `Style::padding()` — the `padding` value becomes `spacing` in SwiftUI:

```php
$spaced = (new VStack(
    new Text('A'),
    new Text('B'),
))->style(Style::make()->padding(16));
```

**Generated:**
```swift
// SwiftUI
VStack(spacing: 16) {
    Text("A")
    Text("B")
}
```

```html
<!-- HTML -->
<div class="vstack" style="padding: 16px">
    <span>A</span>
    <span>B</span>
</div>
```

---

## HStack

Horizontal layout — arranges children left to right.

```php
use Perry\UI\Widget\HStack;
use Perry\UI\Widget\Text;
use Perry\UI\Widget\Spacer;

$navbar = new HStack(
    new Text('Logo'),
    new Spacer(),  // pushes "Menu" to the right
    new Text('Menu'),
);
```

**Generated:**
```swift
HStack(spacing: 8) {
    Text("Logo")
    Spacer()
    Text("Menu")
}
```

---

## Spacer

Flexible space that expands to fill available area.

```php
use Perry\UI\Widget\Spacer;

// Pushes elements apart
$row = new HStack(
    new Text('Left'),
    new Spacer(),
    new Text('Right'),
);
```

**Constructor:** No parameters.

---

## Image

Displays an image from a path or resource name.

```php
use Perry\UI\Widget\Image;

$logo = new Image('logo.png');
$avatar = new Image('avatar');
```

**Constructor:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$source` | `string` | Image path or asset name |

---

## ScrollView

Scrollable container for overflow content.

```php
use Perry\UI\Widget\ScrollView;
use Perry\UI\Widget\VStack;
use Perry\UI\Widget\Text;

$list = new ScrollView(
    new VStack(
        new Text('Item 1'),
        new Text('Item 2'),
        new Text('Item 3'),
    )
);
```

---

## TextInput

Text input field with placeholder and optional onChange action.

```php
use Perry\UI\Binding;
use Perry\UI\Widget\TextInput;

$name = new Binding('name', '');
$input = new TextInput($name, 'Enter your name...');
```

---

## Toggle

Toggle switch with label.

```php
use Perry\UI\Binding;
use Perry\UI\Widget\Toggle;

$darkMode = new Binding('darkMode', false);
$toggle = new Toggle($darkMode, 'Dark Mode');
```

---

## AppContainer

Root application container. Wraps your widget tree, sets window dimensions, and auto-collects all `Binding` objects.

```php
use Perry\UI\AppContainer;
use Perry\UI\Binding;
use Perry\UI\Widget\VStack;
use Perry\UI\Widget\Text;
use Perry\UI\Widget\Button;

$count = new Binding('count', 0);
$label = new Binding('label', 'Clicks: 0');

$app = new AppContainer(
    new VStack(
        (new Text($label))->style(Style::make()->fontSize(24)),
        (new Button('Increment', function () use ($count, $label) {
            $count += 1;
            $label = 'Clicks: ' . strval($count);
        })),
    ),
    320,                    // window width
    480,                    // window height
    $count,                 // extra bindings
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

---

## WebView

Embeds a full HTML page inside the native app. Uses WKWebView (macOS/iOS), WebView2 (Windows), GtkWebView (Linux), AndroidView (Android), or iframe (Web).

```php
use Perry\UI\Widget\WebView;

// The HTML is generated by HtmlBackend and embedded at build time
$webview = new WebView($fullHtmlContent);
```

**Usage pattern (from Pry example):**
```php
// 1. Generate full HTML via HtmlBackend
$webApp = new App(Target::fromString('web'));
$webApp->setRoot($widgetTree);
$webHtml = $webApp->generateForTarget();

// 2. Wrap in WebView and compile
$root = new AppContainer(
    new WebView($webHtml),
    800, 700,
);
$compiler = new Compiler(Target::fromString('windows'));
$result = $compiler->compile($root, 'pry');
```

**Windows note:** Requires [WebView2 Runtime](/guide/build-system.html#windows-requirements).

---

## Slider

Slider control with min, max, step, and optional onChange action.

```php
use Perry\UI\Binding;
use Perry\UI\Widget\Slider;

$value = new Binding('value', 50.0);
$slider = new Slider(0, 100, $value, step: 1);
```

---

## Checkbox

Checkbox with label and optional onChange action.

```php
use Perry\UI\Binding;
use Perry\UI\Widget\Checkbox;

$checked = new Binding('checked', false);
$checkbox = new Checkbox('Enable feature', $checked);
```

---

## RadioButton

Radio button with group and value selection.

```php
use Perry\UI\Binding;
use Perry\UI\Widget\RadioButton;

$selected = new Binding('color', 'red');
$radio = new RadioButton('Red', 'colors', 'red', $selected);
```

---

## Progress

Progress bar with optional binding.

```php
use Perry\UI\Binding;
use Perry\UI\Widget\Progress;

$progress = new Binding('progress', 0.5);
$bar = new Progress($progress);
```

---

## TabView

Tab-based navigation container.

```php
use Perry\UI\Widget\TabView;
use Perry\UI\Widget\VStack;
use Perry\UI\Widget\Text;

$tabs = new TabView(
    new VStack(new Text('Tab 1 Content')),
    new VStack(new Text('Tab 2 Content')),
);
```
