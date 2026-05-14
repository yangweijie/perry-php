# 扩展 Perry

Perry 在每个层面都可扩展——添加自定义微件、后端、生成器和 PHP 函数映射。

---

## 1. 添加自定义微件

### 步骤 1：创建微件类

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

    public function min(): float { return $this->min; }
    public function max(): float { return $this->max; }
    public function step(): float { return $this->step; }
    public function getValue(): ?\Perry\UI\Binding { return $this->value; }
}
```

### 步骤 2：在 WidgetKind 中添加枚举值

```php
// src/UI/WidgetKind.php
enum WidgetKind: int
{
    case Slider = 9;
    // ...
}
```

### 步骤 3：更新每个后端

```php
// 在 SwiftUIBackend.php 中
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

对 `HtmlBackend`、`WinUIBackend`、`ComposeBackend`、`Gtk4Backend`、`ArkTsBackend`、`GlanceBackend`、`WearTilesBackend`、`FlutterBackend` 和 `WasmBackend` 重复此步骤。

---

## 2. 添加自定义后端

### 步骤 1：创建后端类

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

### 步骤 2：在 CodegenFactory 中注册

```php
// src/Codegen/CodegenFactory.php
public function __construct()
{
    $this->register(new SwiftUIBackend());
    $this->register(new HtmlBackend());
    // ... 现有后端
    $this->register(new FlutterBackend());  // ← 在此添加
}
```

### 步骤 3：使用它

```php
$app = new App();
$app->setRoot($widgetTree);
echo $app->generateCode('flutter');
```

---

## 3. 添加自定义生成器

### 步骤 1：创建生成器类

```php
<?php

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

    // ... 实现 Generator 接口的所有方法
}
```

### 步骤 2：在后端中使用

```php
$gen = new RustGenerator(stateVars: ['display', 'count']);
$action = Action::fromClosure(function () use ($display) {
    $display = 'Hello';
});
echo $action->generate($gen);
// 输出：*display.borrow_mut() = "Hello"
```

---

## 4. 添加 PHP 函数映射

每个生成器将 PHP 内置函数映射到目标语言等价物。

### 示例：Swift 中的 `array_map()`

```php
// 在 SwiftGenerator.php 的 generateFunctionCall() 中添加：

case 'array_map':
    // PHP：array_map(fn($x) => ..., $array)
    // Swift：array.map { x in ... }
    $callback = $args[0] ?? null;
    $array = $args[1]->accept($this) ?? '[]';
    if ($callback instanceof \Perry\IR\Closure) {
        $param = $callback->params[0] ?? 'item';
        $body = $callback->body->accept($this);
        return "{$array}.map {{ {$param} in {$body} }}";
    }
    return "{$array}.map {{ $0 }}";

case 'array_filter':
    // PHP：array_filter($array, fn($x) => ...)
    // Swift：array.filter { x in ... }
    $array = $args[0]->accept($this) ?? '[]';
    $callback = $args[1] ?? null;
    if ($callback instanceof \Perry\IR\Closure) {
        $param = $callback->params[0] ?? 'item';
        $body = $callback->body->accept($this);
        return "{$array}.filter {{ {$param} in {$body} }}";
    }
    return "{$array}.filter {{ $0 }}";
```

### 在所有生成器中添加

1. 在 `SwiftGenerator.php` 中添加映射
2. 在 `JavaScriptGenerator.php` 中添加映射
3. 在 `KotlinGenerator.php` 中添加映射
4. 在 `DartGenerator.php` 中添加映射
5. 在 `CSharpGenerator.php` 中添加映射
6. 在 `tests/Generator/` 中添加测试
