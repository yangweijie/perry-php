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
| `setParent(Widget $parent)` | `void` | 设置父微件（内部使用） |

---

## WidgetKind

所有微件类型的枚举。

```php
use Perry\UI\WidgetKind;

enum WidgetKind: int
{
    case Text = 1;
    case Button = 2;
    case VStack = 3;
    case HStack = 4;
    case Spacer = 5;
    case Image = 6;
    case ScrollView = 7;
    case TextInput = 8;
    case Toggle = 9;
    case Slider = 10;
    case ListWidget = 11;
    case NavigationView = 12;
    case TabView = 13;
    case WebView = 14;
    case AppContainer = 15;
    case Checkbox = 16;
    case RadioButton = 17;
    case Progress = 18;
}
```

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

## State / StateId

底层状态管理。

```php
use Perry\UI\State;

$state = new State();
$id = $state->create(mixed $initialValue);
```

### State 方法

| 方法 | 返回值 | 说明 |
|--------|---------|-------------|
| `create(mixed $initial)` | `StateId` | 创建状态变量 |
| `get(StateId $id)` | `mixed` | 获取当前值 |
| `set(StateId $id, mixed $value)` | `void` | 设置值 |
| `subscribe(StateId $id, callable $callback)` | `void` | 订阅变化 |

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
| `Action::custom(string $code)` | 原始平台特定代码（原样传递） |
| `Action::fromClosure(Closure $fn, array $bindings = [])` | PHP 闭包 → 跨平台代码 |

### 属性

| 属性 | 类型 | 说明 |
|----------|------|-------------|
| `type` | `ActionType` | Set, Append, Clear, Custom, Closure |
| `target` | `?string` | 目标绑定名称 |
| `value` | `mixed` | 值 |

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

### Fluent Setter 方法

| 方法 | 类型 | 说明 |
|--------|------|-------------|
| `backgroundColor(string $hex)` | string | 十六进制颜色 |
| `foregroundColor(string $hex)` | string | 文字/图标颜色 |
| `fontSize(float $pt)` | float | 字号（点） |
| `fontWeight(string $w)` | string | `'bold'`, `'normal'`, `'light'` |
| `fontFamily(string $f)` | string | 字体名称 |
| `textAlignment(string $a)` | string | `'left'`, `'center'`, `'right'` |
| `padding(float $p)` | float | 统一内边距 |
| `paddingAll(float $t, float $b, float $l, float $r)` | 4×float | 各边单独内边距 |
| `margin(float $m)` | float | 统一外边距 |
| `width(float $w)` | float | 固定宽度 |
| `height(float $h)` | float | 固定高度 |
| `minWidth(float $w)` | float | 最小宽度 |
| `minHeight(float $h)` | float | 最小高度 |
| `maxWidth(float $w)` | float | 最大宽度 |
| `maxHeight(float $h)` | float | 最大高度 |
| `cornerRadius(float $r)` | float | 圆角半径 |
| `border(float $w, string $color)` | float, string | 边框宽度 + 颜色 |
| `shadow(string $color, float $r, float $ox, float $oy)` | string, 3×float | 投影 |
| `opacity(float $o)` | float | 0.0（透明）– 1.0（不透明） |
| `textDecoration(string $d)` | string | `'underline'`, `'line-through'` |
| `lineSpacing(float $s)` | float | 行高间距 |
| `letterSpacing(float $s)` | float | 字符间距 |
| `rotate(float $deg)` | float | 旋转角度 |
| `scale(float $s)` | float | 统一缩放因子 |
| `translateX(float $x)` | float | X 轴平移 |
| `translateY(float $y)` | float | Y 轴平移 |
| `flexGrow(float $g)` | float | flex 增长因子 |
| `flexShrink(float $s)` | float | flex 收缩因子 |
| `gap(float $g)` | float | 子元素间距 |

---

## StyleProperty

所有样式属性的枚举。

```php
use Perry\UI\Styling\StyleProperty;

enum StyleProperty: string
{
    case BackgroundColor;
    case ForegroundColor;
    case BorderColor;
    case BorderWidth;
    case CornerRadius;
    case Opacity;
    case Padding;
    case PaddingTop;
    case PaddingBottom;
    case PaddingLeading;
    case PaddingTrailing;
    case Margin;
    case Width;
    case Height;
    case MinWidth;
    case MinHeight;
    case MaxWidth;
    case MaxHeight;
    case FontSize;
    case FontWeight;
    case FontFamily;
    case TextAlignment;
    case TextDecoration;
    case LineSpacing;
    case LetterSpacing;
    case ShadowColor;
    case ShadowRadius;
    case ShadowOffsetX;
    case ShadowOffsetY;
    case Rotate;
    case Scale;
    case TranslateX;
    case TranslateY;
    case FlexGrow;
    case FlexShrink;
    case Gap;
}
```

---

## StyleMatrix

查询每个平台支持的样式属性。

```php
use Perry\UI\Styling\StyleMatrix;
use Perry\UI\Styling\StyleProperty;
use Perry\UI\Styling\PlatformSupport;

$matrix = new StyleMatrix();

$support = $matrix->getSupport('macos', StyleProperty::CornerRadius);
// PlatformSupport::Wired, ::Stub, ::Missing, ::NotApplicable

$wired = $matrix->getWiredProperties('macos');   // 完全支持的属性
$missing = $matrix->getMissingProperties('android');
$full = $matrix->isFullySupported('macos');
```

---

## AppContainer

包裹微件树、设置窗口尺寸、自动收集所有 Binding 的根容器。

```php
use Perry\UI\AppContainer;
use Perry\UI\Widget;
use Perry\UI\Binding;

$container = new AppContainer(
    Widget $content,
    ?int $windowWidth = null,
    ?int $windowHeight = null,
    Binding ...$extraBindings
);
```

### 方法

| 方法 | 返回值 | 说明 |
|--------|---------|-------------|
| `content()` | `Widget` | 根微件 |
| `windowWidth()` | `?int` | 窗口宽度（像素） |
| `windowHeight()` | `?int` | 窗口高度（像素） |
| `bindings()` | `Binding[]` | 所有收集的 Binding |

---

## Target

平台目标枚举和辅助函数。

```php
use Perry\Build\Target;

// 工厂方法
$target = Target::detect();             // 自动检测当前平台
$target = Target::fromString('macos');  // 从字符串
$target->isApple();    // macOS, iOS, tvOS, visionOS, watchOS 为 true
$target->isDesktop();  // macOS, Linux, Windows 为 true
$target->isMobile();   // iOS, Android, watchOS 为 true
$target->isWeb();      // web, wasm 为 true
```

### 可用目标

| 目标字符串 | 平台 |
|--------------|----------|
| `macos` | macOS |
| `ios` | iOS |
| `ios-simulator` | iOS 模拟器 |
| `tvos` | tvOS |
| `visionos` | visionOS |
| `watchos` | watchOS |
| `android` | Android |
| `glance` | Android Glance 微件 |
| `wear-tiles` | Wear OS Tiles |
| `gtk4-linux` | Linux GTK4 |
| `windows` | Windows WinUI |
| `web` | Web |
| `wasm` | WebAssembly |
| `harmonyos` | HarmonyOS |
| `flutter` | Flutter |

---

## CodegenBackend

代码生成后端的抽象基类。所有 11 个后端都继承此类。

```php
use Perry\Codegen\CodegenBackend;
use Perry\Build\Target;
use Perry\UI\Widget;

abstract class CodegenBackend
{
    abstract public function name(): string;
    abstract public function supports(Target $target): bool;
    abstract public function generate(Widget $root): string;
    public function supportedStyleProperties(): array;
    public function generateToFile(Widget $root, string $path): void;
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
| `glance` | `GlanceBackend` | Kotlin Glance 可组合项 |
| `wear-tiles` | `WearTilesBackend` | Kotlin Wear TileService |
| `flutter` | `FlutterBackend` | Flutter Dart |
| `wasm` | `WasmBackend` | HTML + JS 桥接 API |

---

## Generator 接口

语言生成器实现此接口（50+ 个方法），将 IR 节点转换为目标语言代码。

```php
use Perry\IR\Generator;

interface Generator
{
    // 核心
    public function generateProgram(Program $node): string;
    public function generateAssignment(Assignment $node): string;
    public function generateVariable(Variable $node): string;
    public function generateLiteral(Literal $node): string;
    public function generateBinaryOp(BinaryOp $node): string;
    public function generateUnaryOp(UnaryOp $node): string;
    public function generateFunctionCall(FunctionCall $node): string;
    public function generateMethodCall(MethodCall $node): string;
    public function generatePropertyAccess(PropertyAccess $node): string;
    public function generateArrayAccess(ArrayAccess $node): string;
    public function generateArrayLiteral(ArrayLiteral $node): string;
    public function generateTernary(Ternary $node): string;

    // 控制流
    public function generateIf(IfStatement $node): string;
    public function generateWhile(WhileStatement $node): string;
    public function generateFor(ForStatement $node): string;
    public function generateForeach(ForeachStatement $node): string;
    public function generateSwitch(SwitchStatement $node): string;

    // 等等 — 共 50+ 个方法
}
```

### 可用生成器

| 生成器 | 语言 | 状态变量语法 | 变量声明 |
|-----------|----------|----------------------|---------------------|
| `SwiftGenerator` | Swift | `name = ...` | `var name = ...` |
| `JavaScriptGenerator` | JavaScript | `state.name = ...` | `let name = ...` |
| `KotlinGenerator` | Kotlin | `name.value = ...` | `var name = ...` |
| `DartGenerator` | Dart | `name.value = ...` | `var name = ...` |
| `CSharpGenerator` | C# | `name = ...` | `var name = ...` |

---

## Compiler

将微件树编译为原生可执行文件。

```php
use Perry\Build\Compiler;
use Perry\Build\Target;
use Perry\Build\CompileResult;

$compiler = new Compiler(Target $target);
$result = $compiler->compile(Widget $root, string $name): CompileResult;
```

### CompileResult

| 属性 | 类型 | 说明 |
|----------|------|-------------|
| `success` | `bool` | 编译是否成功 |
| `outputFile` | `?string` | 构建的二进制文件路径 |
| `error` | `?string` | 失败时的错误信息 |
