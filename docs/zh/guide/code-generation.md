# 代码生成

Perry 的代码生成管线：**微件树 → 后端 → 原生源代码**。

---

## 后端

11 个平台代码生成器，每个实现 `CodegenBackend`：

| 后端 | 类名 | 平台 | 输出语言 |
|---------|-------|-----------|--------|
| `swiftui` | `SwiftUIBackend` | macOS, iOS, tvOS, visionOS, watchOS | SwiftUI Swift |
| `html` | `HtmlBackend` | Web, WebAssembly | HTML/CSS/JavaScript |
| `compose` | `ComposeBackend` | Android | Jetpack Compose Kotlin |
| `android-xml` | `AndroidXmlBackend` | Android | Android XML 布局 |
| `winui` | `WinUIBackend` | Windows | WPF/WinUI XAML |
| `gtk4` | `Gtk4Backend` | Linux | GTK4 XML UI |
| `arkts` | `ArkTsBackend` | HarmonyOS | ArkUI TypeScript |
| `glance` | `GlanceBackend` | Android 主屏幕 | Kotlin Glance 可组合项 |
| `wear-tiles` | `WearTilesBackend` | Wear OS | Kotlin TileService |
| `flutter` | `FlutterBackend` | Flutter | Dart 微件树 |
| `wasm` | `WasmBackend` | WebAssembly | HTML + JS 桥接 API |

### 用法

```php
use Perry\App;
use Perry\Build\Target;

$app = new App();
$app->setRoot($widgetTree);

// 按名称生成
echo $app->generateCode('swiftui');
echo $app->generateCode('html');

// 自动检测目标平台
$app = new App(Target::fromString('macos'));
echo $app->generateForTarget();

// 写入文件
$backend = $app->codegen()->get('html');
$backend->generateToFile($widgetTree, 'build/output.html');
```

---

## 生成器

生成器将 **IR 节点**（中间表示）转换为目标语言代码。每个实现 `Perry\IR\Generator` 接口，包含 50+ 个方法。

| 生成器 | 语言 | 状态变量 | 新变量 |
|-----------|----------|-----------|---------|
| `SwiftGenerator` | Swift | `name = ...` | `var name = ...` |
| `JavaScriptGenerator` | JavaScript | `state.name = ...` | `let name = ...` |
| `KotlinGenerator` | Kotlin | `name.value = ...` | `var name = ...` |
| `DartGenerator` | Dart | `name.value = ...` | `var name = ...` |
| `CSharpGenerator` | C# | `name = ...` | `var name = ...` |

### 用法

```php
use Perry\Generator\SwiftGenerator;
use Perry\IR\Assignment;
use Perry\IR\Literal;

$gen = new SwiftGenerator(stateVars: ['display', 'result']);
$ir = new Assignment('display', new Literal('Hello'));
echo $gen->generateAssignment($ir);
// 输出：display = "Hello"

$ir2 = new Assignment('count', new Literal(42));
echo $gen->generateAssignment($ir2);
// 输出：var count = 42（新变量，非状态变量）
```

---

## IR 系统

**54 个中间表示节点类型**，以语言无关的形式表示 PHP 代码：

### 核心（14 个）
`Program`, `Assignment`, `IfStatement`, `BinaryOp`, `UnaryOp`, `Variable`, `Literal`, `FunctionCall`, `ReturnStatement`, `ArrayAccess`, `MethodCall`, `PropertyAccess`, `Ternary`, `ArrayLiteral`

### 循环（5 个）
`WhileStatement`, `ForStatement`, `ForeachStatement`, `BreakStatement`, `ContinueStatement`

### 管线

```
PHP 闭包
    │
    ▼ nikic/php-parser
PHP AST
    │
    ▼ Perry\IR\AstToIrVisitor
Perry IR（54 个节点类型）
    │
    ▼ Perry\Generator\{Swift,JavaScript,Kotlin,Dart,CSharp}Generator
目标语言代码
```
