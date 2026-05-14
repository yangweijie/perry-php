# API 参考

Perry PHP 所有公开 API 的完整参考。

---

## App

Perry 框架的入口点。

```php
use Perry\App;

$app = new App(?Target $target = null);
```

### 方法

| 方法 | 返回值 | 说明 |
|--------|---------|-------------|
| `setRoot(Widget $root)` | `void` | 设置根微件树 |
| `generateCode(string $backend)` | `string` | 为指定后端生成代码 |
| `generateForTarget()` | `string` | 为配置的目标平台生成代码 |
| `codegen()` | `CodegenFactory` | 获取代码生成工厂实例 |
| `target()` | `?Target` | 获取配置的目标 |

---

## Widget

所有 UI 微件的基类。

```php
use Perry\UI\Widget;

abstract class Widget
```

### 方法

| 方法 | 返回值 | 说明 |
|--------|---------|-------------|
| `kind()` | `WidgetKind` | 微件类型枚举 |
| `handle()` | `WidgetHandle` | 唯一微件标识 |
| `style(?Style $style)` | `static` | 应用样式（fluent） |
| `getStyle()` | `Style` | 获取已应用的样式 |
| `children()` | `Widget[]` | 获取子微件 |
| `parent()` | `?Widget` | 获取父微件 |

---

## Binding

声明式双向数据绑定。

```php
use Perry\UI\Binding;

$binding = new Binding(string $name, string|int|float|bool $initialValue);
```

### 方法

| 方法 | 返回值 | 说明 |
|--------|---------|-------------|
| `name()` | `string` | 生成代码中的变量名 |
| `initialValue()` | `string|int|float|bool` | 默认值 |

---

## Action

定义用户交互处理器。

```php
use Perry\UI\Action;
use Perry\UI\Binding;
```

### 静态工厂方法

| 方法 | 说明 |
|--------|-------------|
| `Action::set(Binding\|string $target, mixed $value)` | 给绑定赋值 |
| `Action::append(Binding $target, string $value)` | 拼接字符串 |
| `Action::clear(Binding $target)` | 重置为初始值 |
| `Action::custom(string $code)` | 原始平台特定代码 |
| `Action::fromClosure(Closure $fn, array $bindings = [])` | PHP 闭包 → 跨平台代码 |

---

## Style

Fluent 样式构建器，包含 29+ 个属性。

```php
use Perry\UI\Styling\Style;

$style = Style::make();
```

### 方法

| 方法 | 参数 | 说明 |
|--------|-----------|-------------|
| `make()` | — | 创建新的 Style |
| `set(StyleProperty, mixed)` | enum, value | 设置任意属性 |
| `get(StyleProperty)` | enum | 获取属性值 |
| `has(StyleProperty)` | enum | 检查是否已设置 |
| `all()` | — | 获取所有属性 |
| `merge(Style)` | Style | 合并（右侧优先） |

完整属性列表和 fluent setter 方法请参阅[样式](/zh/guide/styling.html)页面。

---

## Target

平台目标枚举和辅助函数。

```php
use Perry\Build\Target;

// 工厂方法
$target = Target::detect();             // 自动检测
$target = Target::fromString('macos');  // 从字符串
```

### 可用目标

| 目标字符串 | 平台 |
|--------------|----------|
| `macos` | macOS |
| `ios` | iOS |
| `android` | Android |
| `web` | Web |
| `windows` | Windows |
| `flutter` | Flutter |
| `harmonyos` | HarmonyOS |
| 等 | 共 15 个目标 |

---

## CodegenBackend

代码生成后端的抽象基类。所有 11 个后端都继承此类。

```php
use Perry\Codegen\CodegenBackend;

abstract class CodegenBackend
{
    abstract public function name(): string;
    abstract public function supports(Target $target): bool;
    abstract public function generate(Widget $root): string;
    public function supportedStyleProperties(): array;
}
```

### 可用后端

| 后端名 | 类名 | 输出 |
|-------------|-------|--------|
| `swiftui` | `SwiftUIBackend` | SwiftUI Swift |
| `html` | `HtmlBackend` | HTML/CSS/JavaScript |
| `compose` | `ComposeBackend` | Jetpack Compose Kotlin |
| `android-xml` | `AndroidXmlBackend` | Android XML 布局 |
| `winui` | `WinUIBackend` | WPF/WinUI XAML |
| `gtk4` | `Gtk4Backend` | GTK4 XML UI |
| `arkts` | `ArkTsBackend` | ArkUI TypeScript |
| `glance` | `GlanceBackend` | Kotlin Glance |
| `wear-tiles` | `WearTilesBackend` | Kotlin Wear TileService |
| `flutter` | `FlutterBackend` | Flutter Dart |
| `wasm` | `WasmBackend` | HTML + JS 桥接 |
