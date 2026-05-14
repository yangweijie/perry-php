# API Reference

Complete reference for all Perry PHP public APIs.

---

## App

Entry point for the Perry framework.

```php
use Perry\App;

$app = new App(?Target $target = null);
```

### Methods

| Method | Returns | Description |
|--------|---------|-------------|
| `setRoot(Widget $root)` | `void` | Set the root widget tree |
| `generateCode(string $backend)` | `string` | Generate code for a named backend |
| `generateForTarget()` | `string` | Generate code for the configured target |
| `codegen()` | `CodegenFactory` | Get the codegen factory instance |
| `target()` | `?Target` | Get the configured target |

---

## Widget

Base class for all UI widgets.

```php
use Perry\UI\Widget;

abstract class Widget
```

### Methods

| Method | Returns | Description |
|--------|---------|-------------|
| `kind()` | `WidgetKind` | Widget type enum |
| `handle()` | `WidgetHandle` | Unique widget identifier |
| `style(?Style $style)` | `static` | Apply style (fluent) |
| `getStyle()` | `Style` | Get applied style |
| `children()` | `Widget[]` | Get child widgets |
| `parent()` | `?Widget` | Get parent widget |
| `setParent(Widget $parent)` | `void` | Set parent (internal) |

---

## WidgetKind

Enum of all widget types.

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

Declarative two-way data binding.

```php
use Perry\UI\Binding;

$binding = new Binding(string $name, string|int|float|bool $initialValue);
```

### Methods

| Method | Returns | Description |
|--------|---------|-------------|
| `name()` | `string` | Variable name in generated code |
| `initialValue()` | `string|int|float|bool` | Default value |

---

## State / StateId

Low-level state management.

```php
use Perry\UI\State;

$state = new State();
$id = $state->create(mixed $initialValue);
```

### State Methods

| Method | Returns | Description |
|--------|---------|-------------|
| `create(mixed $initial)` | `StateId` | Create a state variable |
| `get(StateId $id)` | `mixed` | Get current value |
| `set(StateId $id, mixed $value)` | `void` | Set value |
| `subscribe(StateId $id, callable $callback)` | `void` | Subscribe to changes |

---

## Action

Defines user interaction handlers.

```php
use Perry\UI\Action;
use Perry\UI\Binding;
```

### Static Factory Methods

| Method | Description |
|--------|-------------|
| `Action::set(Binding\|string $target, mixed $value)` | Assign a value to a binding |
| `Action::append(Binding $target, string $value)` | Concatenate a string |
| `Action::clear(Binding $target)` | Reset to initial value |
| `Action::custom(string $code)` | Raw platform-specific code |
| `Action::fromClosure(Closure $fn, array $bindings = [])` | PHP Closure → cross-platform code |

### Properties

| Property | Type | Description |
|----------|------|-------------|
| `type` | `ActionType` | Set, Append, Clear, Custom, Closure |
| `target` | `?string` | Target binding name (for Set/Append/Clear) |
| `value` | `mixed` | Value (for Set), or initial (for Closure) |

---

## Style

Fluent style builder with 29+ properties.

```php
use Perry\UI\Styling\Style;

$style = Style::make();
```

### Methods

| Method | Parameters | Description |
|--------|-----------|-------------|
| `make()` | — | Create new Style |
| `set(StyleProperty, mixed)` | enum, value | Set any property |
| `get(StyleProperty)` | enum | Get property value |
| `has(StyleProperty)` | enum | Check if set |
| `all()` | — | Get all properties |
| `merge(Style)` | Style | Merge (right wins) |

### Fluent Setters

| Method | Type | Description |
|--------|------|-------------|
| `backgroundColor(string $hex)` | string | Hex color (e.g. `#ff0000`) |
| `foregroundColor(string $hex)` | string | Text/icon color |
| `fontSize(float $pt)` | float | Font size in points |
| `fontWeight(string $w)` | string | `'bold'`, `'normal'`, `'light'` |
| `fontFamily(string $f)` | string | Font family name |
| `textAlignment(string $a)` | string | `'left'`, `'center'`, `'right'` |
| `padding(float $p)` | float | Uniform padding |
| `paddingAll(float $t, float $b, float $l, float $r)` | 4×float | Edge-specific padding |
| `margin(float $m)` | float | Uniform margin |
| `width(float $w)` | float | Fixed width |
| `height(float $h)` | float | Fixed height |
| `minWidth(float $w)` | float | Minimum width |
| `minHeight(float $h)` | float | Minimum height |
| `maxWidth(float $w)` | float | Maximum width |
| `maxHeight(float $h)` | float | Maximum height |
| `cornerRadius(float $r)` | float | Corner rounding |
| `border(float $w, string $color)` | float, string | Border width + color |
| `shadow(string $color, float $radius, float $ox, float $oy)` | string, 3×float | Drop shadow |
| `opacity(float $o)` | float | 0.0 (transparent) – 1.0 (opaque) |
| `textDecoration(string $d)` | string | `'underline'`, `'line-through'` |
| `lineSpacing(float $s)` | float | Line height spacing |
| `letterSpacing(float $s)` | float | Character spacing |
| `rotate(float $deg)` | float | Rotation degrees |
| `scale(float $s)` | float | Uniform scale factor |
| `translateX(float $x)` | float | X-axis translation |
| `translateY(float $y)` | float | Y-axis translation |
| `flexGrow(float $g)` | float | Flex grow factor |
| `flexShrink(float $s)` | float | Flex shrink factor |
| `gap(float $g)` | float | Gap between flex children |

---

## StyleProperty

Enum of all 29 style properties.

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

Queries which properties are supported on each platform.

```php
use Perry\UI\Styling\StyleMatrix;
use Perry\UI\Styling\StyleProperty;
use Perry\UI\Styling\PlatformSupport;

$matrix = new StyleMatrix();

$support = $matrix->getSupport('macos', StyleProperty::CornerRadius);
// PlatformSupport::Wired, ::Stub, ::Missing, ::NotApplicable

$wired = $matrix->getWiredProperties('macos');   // fully-supported list
$missing = $matrix->getMissingProperties('android');
$full = $matrix->isFullySupported('macos');
```

---

## AppContainer

Root container that wraps the widget tree and collects bindings.

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

### Methods

| Method | Returns | Description |
|--------|---------|-------------|
| `content()` | `Widget` | Root widget |
| `windowWidth()` | `?int` | Window width in pixels |
| `windowHeight()` | `?int` | Window height in pixels |
| `bindings()` | `Binding[]` | All collected bindings |

---

## Target

Platform target enum and helpers.

```php
use Perry\Build\Target;

// Factory
$target = Target::detect();             // auto-detect current platform
$target = Target::fromString('macos');  // from string

// Classification
$target->isApple();    // macOS, iOS, tvOS, visionOS, watchOS
$target->isDesktop();  // macOS, Linux, Windows
$target->isMobile();   // iOS, Android, watchOS
$target->isWeb();      // web, wasm
```

### Available Targets

| Target String | Platform |
|--------------|----------|
| `macos` | macOS |
| `ios` | iOS |
| `ios-simulator` | iOS Simulator |
| `tvos` | tvOS |
| `visionos` | visionOS |
| `watchos` | watchOS |
| `android` | Android |
| `glance` | Android Glance widgets |
| `wear-tiles` | Wear OS Tiles |
| `gtk4-linux` | Linux GTK4 |
| `windows` | Windows WinUI |
| `web` | Web |
| `wasm` | WebAssembly |
| `harmonyos` | HarmonyOS |
| `flutter` | Flutter |

---

## CodegenBackend

Abstract base class for code generation backends. All 11 backends extend this.

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

### Available Backends

| Backend Name | Class | Output |
|-------------|-------|--------|
| `swiftui` | `SwiftUIBackend` | SwiftUI Swift |
| `html` | `HtmlBackend` | HTML/CSS/JavaScript |
| `compose` | `ComposeBackend` | Jetpack Compose Kotlin |
| `android-xml` | `AndroidXmlBackend` | Android XML layouts |
| `winui` | `WinUIBackend` | WPF/WinUI XAML |
| `gtk4` | `Gtk4Backend` | GTK4 XML UI |
| `arkts` | `ArkTsBackend` | ArkUI TypeScript |
| `glance` | `GlanceBackend` | Kotlin Glance composables |
| `wear-tiles` | `WearTilesBackend` | Kotlin Wear TileService |
| `flutter` | `FlutterBackend` | Flutter Dart |
| `wasm` | `WasmBackend` | HTML + JS bridge |

---

## Generator Interface

Language generators implement this interface with 50+ methods for IR→target code emission.

```php
use Perry\IR\Generator;

interface Generator
{
    // Core
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

    // Control Flow
    public function generateIf(IfStatement $node): string;
    public function generateWhile(WhileStatement $node): string;
    public function generateFor(ForStatement $node): string;
    public function generateForeach(ForeachStatement $node): string;
    public function generateSwitch(SwitchStatement $node): string;

    // etc. — 50+ methods total
}
```

### Available Generators

| Generator | Language | State Variable Syntax | Variable Declaration |
|-----------|----------|----------------------|---------------------|
| `SwiftGenerator` | Swift | `name = ...` | `var name = ...` |
| `JavaScriptGenerator` | JavaScript | `state.name = ...` | `let name = ...` |
| `KotlinGenerator` | Kotlin | `name.value = ...` | `var name = ...` |
| `DartGenerator` | Dart | `name.value = ...` | `var name = ...` |
| `CSharpGenerator` | C# | `name = ...` | `var name = ...` |

---

## Compiler

Compiles widget trees into native executables.

```php
use Perry\Build\Compiler;
use Perry\Build\Target;
use Perry\Build\CompileResult;

$compiler = new Compiler(Target $target);
$result = $compiler->compile(Widget $root, string $name): CompileResult;
```

### CompileResult

| Property | Type | Description |
|----------|------|-------------|
| `success` | `bool` | Whether compilation succeeded |
| `outputFile` | `?string` | Path to the built binary |
| `error` | `?string` | Error message on failure |
