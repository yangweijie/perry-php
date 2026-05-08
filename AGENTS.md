# PERRY PHP — UI Codegen Framework (Port of perry-ts)

## OVERVIEW

PHP DSL that defines cross-platform UIs declaratively and generates native source code for SwiftUI (Apple), Jetpack Compose (Android), GTK4 (Linux), WinUI (Windows), and HTML/CSS/JS (Web). Codegen only — no runtime.

This is the **UI codegen layer** port of perry-ts (Rust TS→native compiler). Perry-ts is a full compiler pipeline (30 crates, 379k LOC); perry-php focuses exclusively on the UI definition + codegen portion (~11.5k PHP LOC).

## STRUCTURE

```
src/
├── App.php       # Entry point (setRoot, generateCode, generateForTarget)
├── Build/        # Build pipeline orchestration
├── Codegen/      # Platform code generators (SwiftUI, Compose, GTK4, WinUI, HTML, AndroidXml)
├── Generator/    # Language-specific code generators (Swift, Kotlin, Dart, JS, C#)
├── IR/           # PHP AST→IR (for Closure transpilation), 54 node types
└── UI/           # DSL components
    ├── Platform/   # Platform-specific drivers (macOS, iOS, Android, GTK4, Windows, Web)
    ├── Styling/    # Style system (Style::make()->fontSize()), 28 properties, platform matrix
    └── Widget/     # Widget class hierarchy (16 widgets: VStack, HStack, Button, Text, etc.)
```

## PORT STATUS (vs perry-ts)

### Architecture Comparison

| Area | perry-ts (Rust) | LOC | perry-php (PHP) | LOC | Completeness |
|------|----------------|-----|-----------------|-----|-------------|
| Compiler pipeline | parser, types, HIR, LLVM codegen, transforms, dispatch, diagnostics | 204k | **Not ported** | 0 | **0%** |
| Runtime/Stdlib | GC, NaN-boxing, stdlib (Node APIs), JS runtime | 161k | **Not ported** | 0 | **0%** |
| UI Codegen (Rust) | perry-codegen-{swiftui,js,wasm,arkts,glance,wear-tiles} | 57k | Codegen/ + Generator/ + IR/ | 7.9k | **~14%** |
| UI Widget abstraction | perry-ui (6 rs, 1.5k LOC) | 1.5k | UI/Widget/* + Widget.php | 2.0k | **100%+** (more widgets) |
| CLI / Build | perry crate (25+ commands) | 33k | bin/perry + Build/ | 1.1k | **~3%** |
| Native platform bindings | perry-ui-{macos,ios,android,gtk4,windows,visionos,watchos,tvos} | 31k | Codegen/* (generates source instead) | — | **N/A** (different approach) |
| **TOTAL** | **30 crates** | **379k** | **src/** | **11.5k** | **UI layer only** |

### Codegen Backend Comparison

| Backend | perry-ts | perry-php | Notes |
|---------|----------|-----------|-------|
| SwiftUI | ✅ perry-codegen-swiftui (3.0k LOC) | ✅ SwiftUIBackend.php (556 loc) | Core emits working, needs coverage |
| JavaScript | ✅ perry-codegen-js (8.2k LOC) | ✅ HtmlBackend.php (573 loc) | Diff approach: HTML+CSS+JS vs raw JS |
| Jetpack Compose | Via perry-ui-android (native) | ✅ ComposeBackend.php (451 loc) | Unique to PHP |
| Android XML | ❌ | ✅ AndroidXmlBackend.php (857 loc) | Unique to PHP |
| GTK4 | Via perry-ui-gtk4 (native) | ✅ Gtk4Backend.php (571 loc) | Codegen vs native bindings |
| WinUI | Via perry-ui-windows (native) | ✅ WinUIBackend.php (825 loc) | Codegen vs native bindings |
| WASM | ✅ perry-codegen-wasm (20.4k LOC) | ❌ | Missing |
| ArkTS/HarmonyOS | ✅ perry-codegen-arkts (20.2k LOC) | ❌ | Missing |
| Glance/Wear Tiles | ✅ perry-codegen-glance + wear-tiles | ❌ | Missing |

### Generator Language Coverage

| Language | Lines | Completeness (IR→target) | Notes |
|----------|-------|-------------------------|-------|
| Swift | 552 loc | ~30% (basic closure transpilation) | 118 lines test coverage |
| Kotlin | 563 loc | ~30% | 142 lines test |
| Dart | 578 loc | ~25% | 130 lines test |
| JavaScript | 574 loc | ~25% | 116 lines test |
| C# | 594 loc | ~20% | 133 lines test |

### Widget Parity (16 widgets)

```
Text, Button, VStack, HStack, Spacer, Image, ScrollView,
TextInput, TextEditor, Toggle, Slider, ListWidget, NavigationView,
TabView, AppContainer, WebView
```

## COMPLETION BREAKDOWN

### What Works (Production-Ready)
- **Widget DSL**: All 16 widgets, fluent style API, binding system
- **Closure→IR**: PHP closure transpilation via nikic/php-parser (AST→IR visitor)
- **SwiftUI Codegen**: Full app generation with state management, actions, styling
- **HTML Codegen**: Interactive web output with reactive state
- **Build Target System**: 11 platform targets with auto-detection

### What's Partial (Needs Work)
- **Compose/GTK4/WinUI Backends**: Scaffolds exist, widget coverage is basic
- **IR Generator Interface**: 50+ methods to implement per language
- **PHP Function Mappings**: Each generator maps PHP builtins → target language (incomplete)
- **Build Pipeline (Compiler/Linker)**: Stubs exist, real toolchain integration missing

### What's Not Ported (Out of Scope)
- **Compiler**: parser, type system, HIR, transforms, LLVM codegen, dispatch
- **Runtime**: NaN-boxing, GC, memory management, stdlib, JS interop
- **Native UI**: Direct platform UI bindings (uses codegen instead)

## WHERE TO LOOK

| Task | Location |
|------|----------|
| Add new widget | `src/UI/Widget/` |
| Add new platform codegen | `src/Codegen/` |
| Styling logic | `src/UI/Styling/Style.php` |
| IR node types | `src/IR/` |
| IR→target language | `src/Generator/` |
| Build/target system | `src/Build/` |
| Tests | `tests/Codegen/`, `tests/Generator/` |
| Examples | `examples/` |

## CONVENTIONS

- **Fluent API**: widgets use method chaining (`(new Text($v))->style(...)`)
- **Binding system**: `new Binding('name', $default)` for reactive values
- **Pest testing**: all tests use Pest PHP framework
- **No runtime**: PHP generates code strings, never interprets UI
- **Visitor pattern**: IR nodes call `accept(Generator)` for language emission
- **Closure transpilation**: PHP closures parsed via nikic/php-parser, lowered to IR, emitted to target language

## COMMANDS

```bash
./vendor/bin/pest         # Run all tests
composer test             # Same via composer
php bin/perry info        # Show platform info & available backends
php bin/perry demo        # Generate demo app for current platform
php bin/perry compile     # Compile widget tree to executable
php bin/perry targets     # List available targets
php bin/perry backends    # List codegen backends
```
