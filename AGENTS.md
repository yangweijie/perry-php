# PERRY PHP — UI Codegen Framework (Port of perry-ts)

## OVERVIEW

PHP DSL that defines cross-platform UIs declaratively and generates native source code for 11 platforms (SwiftUI, HTML, Android XML, Jetpack Compose, GTK4, WinUI, Wasm, ArkTS/HarmonyOS, Glance, Wear Tiles, Flutter). Codegen only — no runtime.

This is the **UI codegen layer** port of perry-ts (Rust TS→native compiler). Perry-ts is a full compiler pipeline (31 crates, 334k LOC); perry-php focuses exclusively on the UI definition + codegen portion (~19k PHP LOC).

**Status:** 674 tests, 2896 assertions — all passing ✅ | 11 backends, 29 style properties, 16 widgets, 65+ PHP function mappings

## STRUCTURE

```
src/
├── App.php       # Entry point (setRoot, generateCode, generateForTarget)
├── Build/        # Build pipeline orchestration
├── Codegen/      # 11 platform code generators (SwiftUI, Html, AndroidXml, WinUI, Gtk4, Compose, Wasm, ArkTs, Glance, WearTiles, Flutter)
├── Generator/    # Language-specific code generators (Swift, Kotlin, Dart, JS, C#)
├── IR/           # PHP AST→IR (for Closure transpilation), 54 node types
└── UI/           # DSL components
    ├── Platform/   # Platform-specific drivers (macOS, iOS, Android, GTK4, Windows, Web)
     ├── Styling/    # Style system (Style::make()->fontSize()), 29 properties, platform matrix
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
| Tests | — | — | tests/ | 9.5k (674 tests, 2896 assertions) | **Growing** |
| Native platform bindings | perry-ui-{macos,ios,android,gtk4,windows,visionos,watchos,tvos} | 31k | Codegen/* (generates source instead) | — | **N/A** (different approach) |
| **TOTAL** | **31 crates** | **334k** | **src/** | **19.1k** | **UI layer only** |

### Overall Port Completion

| Layer | Scope | Ported | Status |
|-------|-------|--------|--------|
| **UI Widget DSL** | 16 widgets, 29 style properties, 11 codegen backends | ✅ 100% | Production-ready |
| **Closure Transpilation** | PHP closure → AST → IR → 5 target languages | ✅ Core done | 65+ PHP function mappings |
| **IR System** | 54 node types, 90 interface methods | ✅ 100% | All generators implemented |
| **Build Pipeline** | Compiler, linker, toolchain integration | ⚠️ Stubs | Missing real toolchain |
| **Compiler** | Parser, type system, HIR, LLVM codegen | ❌ 0% | Out of scope |
| **Runtime** | GC, NaN-boxing, stdlib | ❌ 0% | Out of scope |

### Codegen Backend Comparison

| Backend | perry-ts | perry-php | Notes |
|---------|----------|-----------|-------|
| SwiftUI | ✅ perry-codegen-swiftui (3.0k LOC) | ✅ SwiftUIBackend.php (627 loc) | Full app generation with state, actions, styling |
| JavaScript | ✅ perry-codegen-js (8.2k LOC) | ✅ HtmlBackend.php (604 loc) | HTML+CSS+JS vs perry-ts raw JS |
| Jetpack Compose | Via perry-ui-android (native) | ✅ ComposeBackend.php (490 loc) | Unique to PHP |
| Android XML | ❌ | ✅ AndroidXmlBackend.php (888 loc) | Unique to PHP, most LOC backend |
| GTK4 | Via perry-ui-gtk4 (native) | ✅ Gtk4Backend.php (615 loc) | Codegen vs native bindings |
| WinUI | Via perry-ui-windows (native) | ✅ WinUIBackend.php (874 loc) | Codegen vs native bindings |
| WASM | ✅ perry-codegen-wasm (20.4k LOC) | ✅ WasmBackend.php (622 loc) | Generates HTML+JS with perry_ui_* bridge API |
| ArkTS/HarmonyOS | ✅ perry-codegen-arkts (20.2k LOC) | ✅ ArkTsBackend.php (495 loc) | Full ArkUI codegen with @State bindings |
| Glance | ✅ perry-codegen-glance | ✅ GlanceBackend.php (447 loc) | Kotlin Glance composables for home screen widgets |
| Wear Tiles | ✅ perry-codegen-wear-tiles | ✅ WearTilesBackend.php (406 loc) | Kotlin Wear OS TileService builder API |
| Flutter | ❌ | ✅ FlutterBackend.php (608 loc) | Flutter Material Design widgets (unique to PHP) |

### Generator Language Coverage

| Language | Lines | IR Methods | PHP Mappings | Tests | Notes |
|----------|-------|-----------|-------------|-------|-------|
| Swift | 796 loc | 90/90 (100%) | 65+ | 19 | Full IR, expanded PHP mappings |
| Kotlin | 807 loc | 90/90 (100%) | 65+ | 22 | Full IR, expanded PHP mappings |
| Dart | 816 loc | 90/90 (100%) | 65+ | 22 | Full IR, expanded PHP mappings |
| JavaScript | 813 loc | 90/90 (100%) | 65+ | 43 | Full IR, expanded PHP mappings |
| C# | 823 loc | 90/90 (100%) | 65+ | 22 | Full IR, expanded PHP mappings |
| C | 411 loc | 90/90 (100%) | 0 (passthrough) | 46 | Gtk4 Closure → C, full IR |

### Widget Parity (16 widgets)

```
Text, Button, VStack, HStack, Spacer, Image, ScrollView,
TextInput, TextEditor, Toggle, Slider, ListWidget, NavigationView,
TabView, AppContainer, WebView
```

### Perry-ts Features Not in Perry-php (Out of Scope)

| Feature | Description | Why Not Ported |
|---------|-------------|----------------|
| TypeScript parser | SWC-based TS parser | PHP DSL uses native PHP, not TS |
| Type system | Full TypeScript type checking | PHP has its own type system |
| HIR + transforms | High-level IR + optimization passes | Not needed for codegen-only |
| LLVM codegen | Native binary generation | PHP generates source, not binaries |
| GC + NaN-boxing | Runtime value representation | No runtime — codegen only |
| Node.js stdlib | fs, path, process, http, etc. | Not applicable to UI codegen |
| Multi-threading | `perry/thread` module | Not needed for UI codegen |
| i18n | Compile-time localization | Not in scope |
| Home screen widgets | iOS/Android widget codegen | Covered by Glance/WearTiles |

### Perry-php Unique Features (Not in Perry-ts)

| Feature | Description |
|---------|-------------|
| Jetpack Compose backend | Direct Compose codegen (perry-ts uses native bindings) |
| Android XML backend | Legacy Android XML layouts |
| Flutter backend | Flutter Material Design widgets |
| ArkTS/HarmonyOS backend | HarmonyOS ArkUI codegen |
| PHP closure transpilation | PHP AST → IR → 5 target languages |
| 16 widgets | More than perry-ts (which has ~12) |
| 29 style properties | More than perry-ts |
Text, Button, VStack, HStack, Spacer, Image, ScrollView,
TextInput, TextEditor, Toggle, Slider, ListWidget, NavigationView,
TabView, AppContainer, WebView
```

### Perry-ts Features Not in Perry-php (Out of Scope)

| Feature | Description | Why Not Ported |
|---------|-------------|----------------|
| TypeScript parser | SWC-based TS parser | PHP DSL uses native PHP, not TS |
| Type system | Full TypeScript type checking | PHP has its own type system |
| HIR + transforms | High-level IR + optimization passes | Not needed for codegen-only |
| LLVM codegen | Native binary generation | PHP generates source, not binaries |
| GC + NaN-boxing | Runtime value representation | No runtime — codegen only |
| Node.js stdlib | fs, path, process, http, etc. | Not applicable to UI codegen |
| Multi-threading | `perry/thread` module | Not needed for UI codegen |
| i18n | Compile-time localization | Not in scope |
| Home screen widgets | iOS/Android widget codegen | Covered by Glance/WearTiles |

### Perry-php Unique Features (Not in Perry-ts)

| Feature | Description |
|---------|-------------|
| Jetpack Compose backend | Direct Compose codegen (perry-ts uses native bindings) |
| Android XML backend | Legacy Android XML layouts |
| Flutter backend | Flutter Material Design widgets |
| ArkTS/HarmonyOS backend | HarmonyOS ArkUI codegen |
| PHP closure transpilation | PHP AST → IR → 5 target languages |
| 16 widgets | More than perry-ts (which has ~12) |
| 29 style properties | More than perry-ts |
Text, Button, VStack, HStack, Spacer, Image, ScrollView,
TextInput, TextEditor, Toggle, Slider, ListWidget, NavigationView,
TabView, AppContainer, WebView
```

### Perry-ts Features Not in Perry-php (Out of Scope)

| Feature | Description | Why Not Ported |
|---------|-------------|----------------|
| TypeScript parser | SWC-based TS parser | PHP DSL uses native PHP, not TS |
| Type system | Full TypeScript type checking | PHP has its own type system |
| HIR + transforms | High-level IR + optimization passes | Not needed for codegen-only |
| LLVM codegen | Native binary generation | PHP generates source, not binaries |
| GC + NaN-boxing | Runtime value representation | No runtime — codegen only |
| Node.js stdlib | fs, path, process, http, etc. | Not applicable to UI codegen |
| Multi-threading | `perry/thread` module | Not needed for UI codegen |
| i18n | Compile-time localization | Not in scope |
| Home screen widgets | iOS/Android widget codegen | Covered by Glance/WearTiles |

### Perry-php Unique Features (Not in Perry-ts)

| Feature | Description |
|---------|-------------|
| Jetpack Compose backend | Direct Compose codegen (perry-ts uses native bindings) |
| Android XML backend | Legacy Android XML layouts |
| Flutter backend | Flutter Material Design widgets |
| ArkTS/HarmonyOS backend | HarmonyOS ArkUI codegen |
| PHP closure transpilation | PHP AST → IR → 5 target languages |
| 16 widgets | More than perry-ts (which has ~12) |
| 29 style properties | More than perry-ts |
Text, Button, VStack, HStack, Spacer, Image, ScrollView,
TextInput, TextEditor, Toggle, Slider, ListWidget, NavigationView,
TabView, AppContainer, WebView
```

### Perry-ts Features Not in Perry-php (Out of Scope)

| Feature | Description | Why Not Ported |
|---------|-------------|----------------|
| TypeScript parser | SWC-based TS parser | PHP DSL uses native PHP, not TS |
| Type system | Full TypeScript type checking | PHP has its own type system |
| HIR + transforms | High-level IR + optimization passes | Not needed for codegen-only |
| LLVM codegen | Native binary generation | PHP generates source, not binaries |
| GC + NaN-boxing | Runtime value representation | No runtime — codegen only |
| Node.js stdlib | fs, path, process, http, etc. | Not applicable to UI codegen |
| Multi-threading | `perry/thread` module | Not needed for UI codegen |
| i18n | Compile-time localization | Not in scope |
| Home screen widgets | iOS/Android widget codegen | Covered by Glance/WearTiles |

### Perry-php Unique Features (Not in Perry-ts)

| Feature | Description |
|---------|-------------|
| Jetpack Compose backend | Direct Compose codegen (perry-ts uses native bindings) |
| Android XML backend | Legacy Android XML layouts |
| Flutter backend | Flutter Material Design widgets |
| ArkTS/HarmonyOS backend | HarmonyOS ArkUI codegen |
| PHP closure transpilation | PHP AST → IR → 5 target languages |
| 16 widgets | More than perry-ts (which has ~12) |
| 29 style properties | More than perry-ts |
Text, Button, VStack, HStack, Spacer, Image, ScrollView,
TextInput, TextEditor, Toggle, Slider, ListWidget, NavigationView,
TabView, AppContainer, WebView
```

### Perry-ts Features Not in Perry-php (Out of Scope)

| Feature | Description | Why Not Ported |
|---------|-------------|----------------|
| TypeScript parser | SWC-based TS parser | PHP DSL uses native PHP, not TS |
| Type system | Full TypeScript type checking | PHP has its own type system |
| HIR + transforms | High-level IR + optimization passes | Not needed for codegen-only |
| LLVM codegen | Native binary generation | PHP generates source, not binaries |
| GC + NaN-boxing | Runtime value representation | No runtime — codegen only |
| Node.js stdlib | fs, path, process, http, etc. | Not applicable to UI codegen |
| Multi-threading | `perry/thread` module | Not needed for UI codegen |
| i18n | Compile-time localization | Not in scope |
| Home screen widgets | iOS/Android widget codegen | Covered by Glance/WearTiles |

### Perry-php Unique Features (Not in Perry-ts)

| Feature | Description |
|---------|-------------|
| Jetpack Compose backend | Direct Compose codegen (perry-ts uses native bindings) |
| Android XML backend | Legacy Android XML layouts |
| Flutter backend | Flutter Material Design widgets |
| ArkTS/HarmonyOS backend | HarmonyOS ArkUI codegen |
| PHP closure transpilation | PHP AST → IR → 5 target languages |
| 16 widgets | More than perry-ts (which has ~12) |
| 29 style properties | More than perry-ts |
Text, Button, VStack, HStack, Spacer, Image, ScrollView,
TextInput, TextEditor, Toggle, Slider, ListWidget, NavigationView,
TabView, AppContainer, WebView
```

### Perry-ts Features Not in Perry-php (Out of Scope)

| Feature | Description | Why Not Ported |
|---------|-------------|----------------|
| TypeScript parser | SWC-based TS parser | PHP DSL uses native PHP, not TS |
| Type system | Full TypeScript type checking | PHP has its own type system |
| HIR + transforms | High-level IR + optimization passes | Not needed for codegen-only |
| LLVM codegen | Native binary generation | PHP generates source, not binaries |
| GC + NaN-boxing | Runtime value representation | No runtime — codegen only |
| Node.js stdlib | fs, path, process, http, etc. | Not applicable to UI codegen |
| Multi-threading | `perry/thread` module | Not needed for UI codegen |
| i18n | Compile-time localization | Not in scope |
| Home screen widgets | iOS/Android widget codegen | Covered by Glance/WearTiles |

### Perry-php Unique Features (Not in Perry-ts)

| Feature | Description |
|---------|-------------|
| Jetpack Compose backend | Direct Compose codegen (perry-ts uses native bindings) |
| Android XML backend | Legacy Android XML layouts |
| Flutter backend | Flutter Material Design widgets |
| ArkTS/HarmonyOS backend | HarmonyOS ArkUI codegen |
| PHP closure transpilation | PHP AST → IR → 5 target languages |
| 16 widgets | More than perry-ts (which has ~12) |
| 29 style properties | More than perry-ts |
Text, Button, VStack, HStack, Spacer, Image, ScrollView,
TextInput, TextEditor, Toggle, Slider, ListWidget, NavigationView,
TabView, AppContainer, WebView
```

### Perry-ts Features Not in Perry-php (Out of Scope)

| Feature | Description | Why Not Ported |
|---------|-------------|----------------|
| TypeScript parser | SWC-based TS parser | PHP DSL uses native PHP, not TS |
| Type system | Full TypeScript type checking | PHP has its own type system |
| HIR + transforms | High-level IR + optimization passes | Not needed for codegen-only |
| LLVM codegen | Native binary generation | PHP generates source, not binaries |
| GC + NaN-boxing | Runtime value representation | No runtime — codegen only |
| Node.js stdlib | fs, path, process, http, etc. | Not applicable to UI codegen |
| Multi-threading | `perry/thread` module | Not needed for UI codegen |
| i18n | Compile-time localization | Not in scope |
| Home screen widgets | iOS/Android widget codegen | Covered by Glance/WearTiles |

### Perry-php Unique Features (Not in Perry-ts)

| Feature | Description |
|---------|-------------|
| Jetpack Compose backend | Direct Compose codegen (perry-ts uses native bindings) |
| Android XML backend | Legacy Android XML layouts |
| Flutter backend | Flutter Material Design widgets |
| ArkTS/HarmonyOS backend | HarmonyOS ArkUI codegen |
| PHP closure transpilation | PHP AST → IR → 5 target languages |
| 16 widgets | More than perry-ts (which has ~12) |
| 29 style properties | More than perry-ts |
Text, Button, VStack, HStack, Spacer, Image, ScrollView,
TextInput, TextEditor, Toggle, Slider, ListWidget, NavigationView,
TabView, AppContainer, WebView
```

### Perry-ts Features Not in Perry-php (Out of Scope)

| Feature | Description | Why Not Ported |
|---------|-------------|----------------|
| TypeScript parser | SWC-based TS parser | PHP DSL uses native PHP, not TS |
| Type system | Full TypeScript type checking | PHP has its own type system |
| HIR + transforms | High-level IR + optimization passes | Not needed for codegen-only |
| LLVM codegen | Native binary generation | PHP generates source, not binaries |
| GC + NaN-boxing | Runtime value representation | No runtime — codegen only |
| Node.js stdlib | fs, path, process, http, etc. | Not applicable to UI codegen |
| Multi-threading | `perry/thread` module | Not needed for UI codegen |
| i18n | Compile-time localization | Not in scope |
| Home screen widgets | iOS/Android widget codegen | Covered by Glance/WearTiles |

### Perry-php Unique Features (Not in Perry-ts)

| Feature | Description |
|---------|-------------|
| Jetpack Compose backend | Direct Compose codegen (perry-ts uses native bindings) |
| Android XML backend | Legacy Android XML layouts |
| Flutter backend | Flutter Material Design widgets |
| ArkTS/HarmonyOS backend | HarmonyOS ArkUI codegen |
| PHP closure transpilation | PHP AST → IR → 5 target languages |
| 16 widgets | More than perry-ts (which has ~12) |
| 29 style properties | More than perry-ts |
Text, Button, VStack, HStack, Spacer, Image, ScrollView,
TextInput, TextEditor, Toggle, Slider, ListWidget, NavigationView,
TabView, AppContainer, WebView
```

### Perry-ts Features Not in Perry-php (Out of Scope)

| Feature | Description | Why Not Ported |
|---------|-------------|----------------|
| TypeScript parser | SWC-based TS parser | PHP DSL uses native PHP, not TS |
| Type system | Full TypeScript type checking | PHP has its own type system |
| HIR + transforms | High-level IR + optimization passes | Not needed for codegen-only |
| LLVM codegen | Native binary generation | PHP generates source, not binaries |
| GC + NaN-boxing | Runtime value representation | No runtime — codegen only |
| Node.js stdlib | fs, path, process, http, etc. | Not applicable to UI codegen |
| Multi-threading | `perry/thread` module | Not needed for UI codegen |
| i18n | Compile-time localization | Not in scope |
| Home screen widgets | iOS/Android widget codegen | Covered by Glance/WearTiles |

### Perry-php Unique Features (Not in Perry-ts)

| Feature | Description |
|---------|-------------|
| Jetpack Compose backend | Direct Compose codegen (perry-ts uses native bindings) |
| Android XML backend | Legacy Android XML layouts |
| Flutter backend | Flutter Material Design widgets |
| ArkTS/HarmonyOS backend | HarmonyOS ArkUI codegen |
| PHP closure transpilation | PHP AST → IR → 5 target languages |
| 16 widgets | More than perry-ts (which has ~12) |
| 29 style properties | More than perry-ts |
Text, Button, VStack, HStack, Spacer, Image, ScrollView,
TextInput, TextEditor, Toggle, Slider, ListWidget, NavigationView,
TabView, AppContainer, WebView
```

### Perry-ts Features Not in Perry-php (Out of Scope)

| Feature | Description | Why Not Ported |
|---------|-------------|----------------|
| TypeScript parser | SWC-based TS parser | PHP DSL uses native PHP, not TS |
| Type system | Full TypeScript type checking | PHP has its own type system |
| HIR + transforms | High-level IR + optimization passes | Not needed for codegen-only |
| LLVM codegen | Native binary generation | PHP generates source, not binaries |
| GC + NaN-boxing | Runtime value representation | No runtime — codegen only |
| Node.js stdlib | fs, path, process, http, etc. | Not applicable to UI codegen |
| Multi-threading | `perry/thread` module | Not needed for UI codegen |
| i18n | Compile-time localization | Not in scope |
| Home screen widgets | iOS/Android widget codegen | Covered by Glance/WearTiles |

### Perry-php Unique Features (Not in Perry-ts)

| Feature | Description |
|---------|-------------|
| Jetpack Compose backend | Direct Compose codegen (perry-ts uses native bindings) |
| Android XML backend | Legacy Android XML layouts |
| Flutter backend | Flutter Material Design widgets |
| ArkTS/HarmonyOS backend | HarmonyOS ArkUI codegen |
| PHP closure transpilation | PHP AST → IR → 5 target languages |
| 16 widgets | More than perry-ts (which has ~12) |
| 29 style properties | More than perry-ts |

## COMPLETION BREAKDOWN

### What Works (Production-Ready)
- **Widget DSL**: All 16 widgets, fluent style API, binding system
- **Closure→IR**: PHP closure transpilation via nikic/php-parser (AST→IR visitor)
- **All 11 Codegen Backends**: Full app generation with state management, actions, styling for all 11 platforms
- **Style System**: 29 style properties with per-backend supported-properties API
- **Build Target System**: 16 platform targets with auto-detection
- **Closure Transpilation**: CGenerator for Gtk4 C action handlers (380 LOC)

### What's Partial (Needs Work)
- **IR Generator Interface**: 50+ methods to implement per language (basic coverage done, full PHP function mapping incomplete)
- **Build Pipeline (Compiler/Linker)**: Stubs exist, real toolchain integration missing
- **Edge case widget coverage**: Some backends may have gaps in niche widget configurations

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
