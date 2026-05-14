# Code Generation

Perry's code generation pipeline: **Widget Tree → Backend → Native Source Code**.

---

## Backends

11 platform code generators, each implementing `CodegenBackend`:

| Backend | Class | Platforms | Output |
|---------|-------|-----------|--------|
| `swiftui` | `SwiftUIBackend` | macOS, iOS, tvOS, visionOS, watchOS | SwiftUI Swift |
| `html` | `HtmlBackend` | Web, WebAssembly | HTML/CSS/JavaScript |
| `compose` | `ComposeBackend` | Android | Jetpack Compose Kotlin |
| `android-xml` | `AndroidXmlBackend` | Android | Android XML layouts |
| `winui` | `WinUIBackend` | Windows | WPF/WinUI XAML |
| `gtk4` | `Gtk4Backend` | Linux | GTK4 XML UI |
| `arkts` | `ArkTsBackend` | HarmonyOS | ArkUI TypeScript |
| `glance` | `GlanceBackend` | Android (home screen) | Kotlin Glance composables |
| `wear-tiles` | `WearTilesBackend` | Wear OS | Kotlin TileService |
| `flutter` | `FlutterBackend` | Flutter | Dart widget tree |
| `wasm` | `WasmBackend` | WebAssembly | HTML + JS bridge API |

### Usage

```php
use Perry\App;
use Perry\Build\Target;

$app = new App();
$app->setRoot($widgetTree);

// By name
echo $app->generateCode('swiftui');
echo $app->generateCode('html');

// Auto-detect from target
$app = new App(Target::fromString('macos'));
echo $app->generateForTarget();

// Write to file
$backend = $app->codegen()->get('html');
$backend->generateToFile($widgetTree, 'build/output.html');
```

---

## Generators

Generators transform **IR nodes** (Intermediate Representation) into target language code. Each implements `Perry\IR\Generator` with 50+ methods.

| Generator | Language | State Variable | New Variable |
|-----------|----------|-----------|---------|
| `SwiftGenerator` | Swift | `name = ...` | `var name = ...` |
| `JavaScriptGenerator` | JavaScript | `state.name = ...` | `let name = ...` |
| `KotlinGenerator` | Kotlin | `name.value = ...` | `var name = ...` |
| `DartGenerator` | Dart | `name.value = ...` | `var name = ...` |
| `CSharpGenerator` | C# | `name = ...` | `var name = ...` |

### Usage

```php
use Perry\Generator\SwiftGenerator;
use Perry\IR\Assignment;
use Perry\IR\Literal;

$gen = new SwiftGenerator(stateVars: ['display', 'result']);
$ir = new Assignment('display', new Literal('Hello'));
echo $gen->generateAssignment($ir);
// Output: display = "Hello"

$ir2 = new Assignment('count', new Literal(42));
echo $gen->generateAssignment($ir2);
// Output: var count = 42  (new variable, not a state var)
```

---

## IR System

**54 intermediate representation node types** represent PHP code in a language-agnostic form:

### Core (14)
`Program`, `Assignment`, `IfStatement`, `BinaryOp`, `UnaryOp`, `Variable`, `Literal`, `FunctionCall`, `ReturnStatement`, `ArrayAccess`, `MethodCall`, `PropertyAccess`, `Ternary`, `ArrayLiteral`

### Loops (5)
`WhileStatement`, `ForStatement`, `ForeachStatement`, `BreakStatement`, `ContinueStatement`

### Switch/Match (3)
`SwitchStatement`, `CaseNode`, `MatchExpression`

### Output (2)
`EchoStatement`, `PrintStatement`

### Type System (1)
`Cast`

### Inc/Dec (2)
`Increment`, `Decrement`

### Compound Assignment (5)
`PlusAssign`, `MinusAssign`, `MulAssign`, `DivAssign`, `ModAssign`

### Binary Ops (11)
`PowOp`, `BitwiseAnd`, `BitwiseOr`, `BitwiseXor`, `ShiftLeft`, `ShiftRight`, `SpaceshipOp`, `CoalesceOp`, `LogicalAnd`, `LogicalOr`, `LogicalXor`

### Unary Ops (2)
`UnaryPlus`, `BitwiseNot`

### Nullsafe (2)
`NullsafeMethodCall`, `NullsafePropertyAccess`

### Exceptions (3)
`ThrowStatement`, `TryCatchStatement`, `CatchClause`

### Static (3)
`StaticCall`, `StaticPropertyAccess`, `ClassConstFetch`

### Other (1)
`IncludeStatement`

### Pipeline

```
PHP Closure
    │
    ▼ nikic/php-parser
PHP AST
    │
    ▼ Perry\IR\AstToIrVisitor
Perry IR (54 node types)
    │
    ▼ Perry\Generator\{Swift,JavaScript,Kotlin,Dart,CSharp}Generator
Target Language Code
```
