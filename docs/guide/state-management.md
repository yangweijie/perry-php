# State Management

Perry offers two approaches to state management: `Binding` (declarative, preferred) and `State`/`StateId` (low-level).

---

## Binding

Declarative, two-way data binding. The primary way to manage state.

```php
use Perry\UI\Binding;

$count = new Binding('count', 0);         // int
$display = new Binding('display', '0');   // string
$visible = new Binding('visible', true);  // bool
$opacity = new Binding('opacity', 1.0);   // float
```

### How It Works

1. Pass a `Binding` to a `Text` widget: `new Text($display)`
2. `AppContainer` auto-collects it from the widget tree
3. Backends generate state declarations:

| Backend | Generated Code |
|---------|---------------|
| SwiftUI | `@State private var display = "0"` |
| JavaScript | `const state = { display: "0" }` |
| Kotlin | `var display = mutableStateOf("0")` |
| Dart | `var display = ValueNotifier("0")` |
| C# | `var display = "0";` |

4. When a button action modifies `$display`, the generated code assigns to the state variable
5. Re-render updates all bound `Text` widgets

### Using Bindings in Closure Actions

```php
$count = new Binding('count', 0);

$action = Action::fromClosure(function () use ($count) {
    $count += 1;
});

// Generated Swift: count = count + 1
// Generated JS:    state.count = state.count + 1
// Generated Kotlin: count.value = count.value + 1
```

### Constructor

| Parameter | Type | Description |
|-----------|------|-------------|
| `$name` | `string` | Variable name in generated code |
| `$initialValue` | `mixed` | Default value (`string`, `int`, `float`, `bool`) |

---

## State / StateId

Lower-level state management for `TextInput` and `Toggle` widgets (for legacy support; prefer `Binding` for new code).

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

// Subscribe to changes
$state->subscribe($name, function (mixed $newValue) {
    echo "Name changed to: $newValue\n";
});
```

### When to Use

| Approach | When to Use |
|----------|-------------|
| `Binding` | **Most cases.** Declarative, auto-collected by `AppContainer`, works with all widgets. |
| `State`/`StateId` | Legacy `TextInput` and `Toggle` widgets that require `StateId`. |

> **Tip:** Always prefer `Binding` for new code. It's simpler, more powerful, and Auto-collected.
