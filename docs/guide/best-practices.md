# Best Practices

Guidelines for writing clean, maintainable, and efficient Perry PHP code.

---

## 1. Prefer `Binding` Over `State`/`StateId`

Use `Binding` for all new code. It's declarative, auto-collected by `AppContainer`, and works with all widgets.

```php
// ✅ Good — declarative, auto-collected
$count = new Binding('count', 0);
$label = new Binding('label', 'Clicks: 0');

// ❌ Avoid — manual State management (legacy)
$state = new State();
$count = $state->create(0);
```

**Why:** `Binding` objects are automatically discovered when you pass them to `Text` widgets. `AppContainer` walks the widget tree and collects them, so backends know exactly which `@State` / `const state` variables to declare.

---

## 2. Use the Nested Closure Pattern for Parameterized Buttons

When creating multiple buttons with similar behavior (like a calculator keypad), use a factory function with `compact()`:

```php
// ✅ Good — reusable, no code duplication
function numBtn(string $digit, Binding $display): Button {
    return new Button($digit, Action::fromClosure(
        function () use ($digit, $display) {
            $display .= $digit;
        },
        compact('digit')  // replace $digit with literal in generated code
    ));
}

$row = new HStack(
    numBtn('1', $display),
    numBtn('2', $display),
    numBtn('3', $display),
);
```

**Why:** Without `compact('digit')`, the PHP closure captures the variable by reference. The transpiler needs explicit binding substitution to inline the literal value in the generated code.

---

## 3. Organize Bindings by Concern

Group related bindings and give them descriptive names:

```php
// ✅ Good — clear naming, grouped by feature
// Calculator state
$display = new Binding('display', '0');
$result = new Binding('result', '');
$operand1 = new Binding('operand1', 0.0);
$operation = new Binding('operation', '');

// UI state
$isTyping = new Binding('isTyping', false);

// ❌ Avoid — cryptic names
$a = new Binding('a', 0);
$b = new Binding('b', '');
```

**Why:** Binding names become variable names in the generated code. Clear names make the generated output readable and debuggable.

---

## 4. Always Pass Extra Bindings to `AppContainer`

Any `Binding` used in a closure action but not attached to a `Text` widget must be explicitly passed to `AppContainer`:

```php
$operand1 = new Binding('operand1', 0.0);
$operation = new Binding('operation', '');

$app = new AppContainer(
    $contentWidget,
    320, 480,
    // ✅ Pass non-Text bindings here
    $operand1,
    $operation,
);
```

**Why:** `AppContainer` only auto-collects bindings from `Text` widgets. Bindings used solely in closure actions would be missed without explicit inclusion.

---

## 5. Use `Style::make()` with Method Chaining

Build styles fluently — never construct raw `Style` objects:

```php
// ✅ Good — fluent, readable
$btnStyle = Style::make()
    ->backgroundColor('#007AFF')
    ->foregroundColor('#ffffff')
    ->fontSize(16)
    ->padding(12)
    ->cornerRadius(8);

// ❌ Avoid — manual property setting
$style = Style::make();
$style->set(StyleProperty::BackgroundColor, '#007AFF');
$style->set(StyleProperty::FontSize, 16);
```

---

## 6. Merge Base Styles for Consistency

Define base styles and merge per-instance overrides:

```php
// Base style for all buttons
$baseBtn = Style::make()->fontSize(14)->padding(8)->cornerRadius(4);

// Override for specific buttons
$primaryBtn = $baseBtn->merge(
    Style::make()->backgroundColor('#007AFF')->foregroundColor('#fff')
);
$dangerBtn = $baseBtn->merge(
    Style::make()->backgroundColor('#ff3b30')->foregroundColor('#fff')
);
```

---

## 7. Use `Action::fromClosure()` Over `Action::set()` for Complex Logic

Simple assignments can use `Action::set()`, but anything with conditionals or multiple steps should use a closure:

```php
// ✅ Simple → Action::set
$btn = new Button('Reset', Action::set($display, '0'));

// ✅ Complex → closure
$btn = new Button('±', Action::fromClosure(function () use ($display) {
    if (str_starts_with((string) $display, '-')) {
        $display = substr((string) $display, 1);
    } else {
        $display = '-' . $display;
    }
}));
```

---

## 8. Test on Multiple Backends Early

Different backends have different style property support. Verify your UI on at least two backends during development:

```bash
# Check HTML output first (fastest iteration)
php your-app.php > test.html && open test.html

# Then verify on a native platform
php your-app.php > App.swift
```

Use `supportedStyleProperties()` to check what's available:

```php
$backend = $app->codegen()->get('swiftui');
$supported = $backend->supportedStyleProperties(); // StyleProperty[]
```

---

## 9. Structure Widget Trees for Readability

Keep nesting manageable — use variables for intermediate trees:

```php
// ✅ Good — readable, well-structured
$header = new Text('Calculator');
$display_area = (new Text($display))->style(Style::make()->fontSize(32));
$button_row = new HStack(
    numBtn('1', $display),
    numBtn('2', $display),
    numBtn('3', $display),
);
$root = new VStack($header, $display_area, $button_row);

// ❌ Avoid — deeply nested inline code
$root = new VStack(
    new Text('Calculator'),
    (new Text($display))->style(Style::make()->fontSize(32)),
    new HStack(numBtn('1',...), numBtn('2',...), numBtn('3',...)),
);
```

---

## 10. Use `WebView` for Complex UI, Native Widgets for Simple UI

| Scenario | Approach |
|----------|----------|
| Simple forms, counters, toggles | Native widgets (Text, Button, Toggle) |
| Complex data visualization | `WebView` with HTML/JS |
| Hybrid: native chrome + web content | `WebView` inside `AppContainer` |
| Maximum performance | Native widgets |

The Pry JSON viewer is a good example of the hybrid approach: native window chrome with an embedded web UI.

---

## 11. Optimize for Generated Code Readability

The code you write in PHP directly maps to generated output. Write PHP that produces clean native code:

```php
// ✅ Good — produces { display = "42" }
$action = Action::set($display, '42');

// ❌ Avoid — produces { display = strval(42) } (unnecessary function call)
$action = Action::set($display, strval(42));
```

---

## 12. Version Your Bindings Carefully

Binding names must be unique within an app. They become variable names in the generated code and cannot be duplicated:

```php
// ✅ Good — unique, descriptive
$userName = new Binding('userName', '');
$productCount = new Binding('productCount', 0);

// ❌ Avoid — duplicate or generic names
$data = new Binding('data', '');  // too generic
$value = new Binding('value', ''); // too generic
```
