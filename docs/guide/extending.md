# Extending Perry

Perry is designed to be extensible at every layer — add custom widgets, backends, generators, and PHP function mappings.

---

## 1. Adding a Custom Widget

### Step 1: Create the Widget Class

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
        return WidgetKind::Slider;  // add this enum case first
    }

    public function min(): float { return $this->min; }
    public function max(): float { return $this->max; }
    public function step(): float { return $this->step; }
    public function getValue(): ?\Perry\UI\Binding { return $this->value; }
}
```

### Step 2: Add Enum Case to WidgetKind

```php
// src/UI/WidgetKind.php
enum WidgetKind: int
{
    case Slider = 9;
    // ...
}
```

### Step 3: Update Each Backend

```php
// In SwiftUIBackend.php
WidgetKind::Slider => $this->generateSlider($widget),

private function generateSlider(Slider $widget): string
{
    $min = $widget->min();
    $max = $widget->max();
    $step = $widget->step();
    $binding = $widget->getValue();
    $value = $binding ? $binding->name : '0.0';
    return "Slider(value: \${$value}, in: {$min}...{$max}, step: {$step})";
}
```

Repeat for `HtmlBackend`, `WinUIBackend`, `ComposeBackend`, `Gtk4Backend`, `ArkTsBackend`, `GlanceBackend`, `WearTilesBackend`, `FlutterBackend`, and `WasmBackend`.

---

## 2. Adding a Custom Backend

### Step 1: Create the Backend Class

```php
<?php

declare(strict_types=1);

namespace Perry\Codegen;

use Perry\Build\Target;
use Perry\UI\Widget;
use Perry\UI\WidgetKind;

final class FlutterBackend extends CodegenBackend
{
    public function name(): string
    {
        return 'flutter';
    }

    public function supports(Target $target): bool
    {
        return $target === Target::Android;
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

    private function generateVStack(\Perry\UI\Widget\VStack $widget): string
    {
        $children = array_map(
            fn($c) => $this->generateWidget($c),
            $widget->children()
        );
        $body = implode(",\n        ", $children);
        return "Column(\n    children: [\n        {$body}\n    ]\n)";
    }
}
```

### Step 2: Register in CodegenFactory

```php
// src/Codegen/CodegenFactory.php
public function __construct()
{
    $this->register(new SwiftUIBackend());
    $this->register(new HtmlBackend());
    // ... existing backends
    $this->register(new FlutterBackend());  // ← add here
}
```

### Step 3: Use It

```php
$app = new App();
$app->setRoot($widgetTree);
echo $app->generateCode('flutter');
```

---

## 3. Adding a Custom Generator

### Step 1: Create the Generator Class

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

        return "{$name} = {$value}";
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

    // ... implement all methods from Generator interface
}
```

### Step 2: Use It with a Backend

```php
$gen = new RustGenerator(stateVars: ['display', 'count']);
$action = Action::fromClosure(function () use ($display) {
    $display = 'Hello';
});
echo $action->generate($gen);
// Output: *display.borrow_mut() = "Hello"
```

---

## 4. Adding PHP Function Mappings

Each generator maps PHP built-in functions to target language equivalents.

### Example: `array_map()` in Swift

```php
// In SwiftGenerator.php — add case in generateFunctionCall()

case 'array_map':
    $callback = $args[0] ?? null;
    $array = $args[1]->accept($this) ?? '[]';
    if ($callback instanceof \Perry\IR\Closure) {
        $param = $callback->params[0] ?? 'item';
        $body = $callback->body->accept($this);
        return "{$array}.map {{ {$param} in {$body} }}";
    }
    return "{$array}.map {{ $0 }}";

case 'array_filter':
    $array = $args[0]->accept($this) ?? '[]';
    $callback = $args[1] ?? null;
    if ($callback instanceof \Perry\IR\Closure) {
        $param = $callback->params[0] ?? 'item';
        $body = $callback->body->accept($this);
        return "{$array}.filter {{ {$param} in {$body} }}";
    }
    return "{$array}.filter {{ $0 }}";
```

### To Add Support Across All Generators

1. Add the mapping in `SwiftGenerator.php`
2. Add the mapping in `JavaScriptGenerator.php`
3. Add the mapping in `KotlinGenerator.php`
4. Add the mapping in `DartGenerator.php`
5. Add the mapping in `CSharpGenerator.php`
6. Add tests in `tests/Generator/`
