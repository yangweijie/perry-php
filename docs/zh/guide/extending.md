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

在每个后端的 `generateWidget()` 方法中添加新的 case。

---

## 2. 添加自定义后端

### 步骤 1：创建后端类

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
        // 实现微件树 → 目标代码的转换
    }
}
```

### 步骤 2：在 CodegenFactory 中注册

```php
// src/Codegen/CodegenFactory.php
$this->register(new FlutterBackend());
```

---

## 3. 添加自定义生成器

实现 `Perry\IR\Generator` 接口（50+ 个方法），将 IR 节点转换为目标语言代码。

```php
<?php

namespace Perry\Generator;

use Perry\IR\Generator as GeneratorInterface;

final class RustGenerator implements GeneratorInterface
{
    // 实现所有生成方法...
}
```

---

## 4. 添加 PHP 函数映射

每个生成器将 PHP 内置函数映射到目标语言等价物。

```php
// 在 SwiftGenerator.php 的 generateFunctionCall() 中添加：
case 'array_map':
    // PHP：array_map(fn($x) => ..., $array)
    // Swift：array.map { x in ... }
    $callback = $args[0] ?? null;
    $array = $args[1]->accept($this) ?? '[]';
    return "{$array}.map {{ ... }}";
```

完整的扩展指南请参阅[英文版扩展页面](/guide/extending.html)。
