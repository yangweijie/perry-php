# Perry PHP — Cross-Platform UI Code Generator

## Goal
Build a PHP package that extracts perry-ts's cross-platform UI abstraction and build system. PHP developers define UIs with closures/actions → Perry transpiles to native code for 11 platforms.

## Current Status: COMPLETED ✅

All core features implemented and working. Calculator and Pry examples build and run on macOS + web.

---

## Phase 1: Core Package [COMPLETE ✅]
- [x] Create PHP package structure (composer.json, directories)
- [x] Implement UI module: Widget system (11 widgets), Binding, Action, AppContainer
- [x] Implement Styling: Style fluent API, 28 StyleProperty enums, StyleMatrix
- [x] Implement Build module: Target (11 platforms), Compiler, LibraryResolver, Linker
- [x] Implement Codegen module: 6 backends (SwiftUI, HTML, Compose, AndroidXml, WinUI, Gtk4)
- [x] CLI entry point (bin/perry)

## Phase 2: AST Calculator [COMPLETE ✅]
- [x] Install nikic/php-parser v5.7
- [x] Create IR system (54 node types): Node.php, AstToIrVisitor.php, Builder.php, Generator.php
- [x] Create 5 generators: Swift, JavaScript, Kotlin, Dart, C#
- [x] Implement Action::fromClosure() + replaceBindings()
- [x] Calculator demo builds and runs on macOS (.app) and web (HTML)
- [x] All calculator operations verified: 1+2=3, 8%7=1, 5×3=15, 10÷2=5, 1+0.1=1.1

## Phase 3: IR Extension [COMPLETE ✅]
- [x] Extended IR from 14 → 54 node types (loops, switch, match, exceptions, casting, inc/dec, compound assign, nullsafe, static, include)
- [x] Updated Generator interface to 50+ methods
- [x] Extended AstToIrVisitor for all PHP AST nodes
- [x] Implemented all new methods in all 5 generators

## Phase 4: HtmlBackend Rewrite [COMPLETE ✅]
- [x] Rewrote from broken inline onclick to proper JS app architecture
- [x] State management: `const state = {...}` with render() function
- [x] Named action functions, textarea sync, innerHTML support
- [x] Added `$customScript` and `$innerHTMLVars` static properties for apps like Pry

## Phase 5: Examples [COMPLETE ✅]
- [x] **Calculator** — Full calculator with 7 actions (macOS + web)
- [x] **Pry** — JSON viewer with tree view, expand/collapse, search, copy (web + macOS via WebView)
- [x] **Mango** — MongoDB GUI with sidebar, query bar, document cards, edit/delete (web + macOS via WebView)

## Phase 6: Documentation [COMPLETE ✅]
- [x] README.md — Comprehensive API docs (~900 lines)
- [x] All 11 widgets documented with constructor params, methods, examples
- [x] Action system documented with PHP→target language function mapping tables
- [x] Extension guides: Custom Widget, Backend, Generator, PHP Function Mappings

---

## Project Architecture

```
/Volumes/data/git/php/perry/perry-php/
├── bin/perry                          # CLI (info, demo, codegen, compile, targets, backends)
├── composer.json                      # nikic/php-parser v5.7
├── README.md                          # ~900 lines comprehensive docs
├── examples/
│   ├── calculator.php                 # Full calculator (macOS + web)
│   ├── pry.php                        # JSON viewer (web + macOS WebView)
│   └── mango.php                      # MongoDB GUI (web + macOS WebView)
├── src/
│   ├── App.php                        # Root app: setRoot(), generateCode(), generateForTarget()
│   ├── UI/
│   │   ├── Widget.php                 # Abstract base (handle, kind, style, children)
│   │   ├── WidgetKind.php             # 15 enum cases (Text=0 through WebView=14)
│   │   ├── WidgetHandle.php           # readonly id, static next()
│   │   ├── Widget/
│   │   │   ├── Text.php              # string|Binding content
│   │   │   ├── Button.php            # label, Action|Closure|null
│   │   │   ├── VStack.php            # variadic Widget...children
│   │   │   ├── HStack.php            # variadic Widget...children
│   │   │   ├── Spacer.php            # no params
│   │   │   ├── Image.php             # string source
│   │   │   ├── ScrollView.php        # variadic Widget...children
│   │   │   ├── TextInput.php         # StateId value, string placeholder
│   │   │   ├── Toggle.php            # StateId isOn, string label
│   │   │   ├── TextEditor.php        # Binding + placeholder (multi-line)
│   │   │   ├── WebView.php           # string $html (WKWebView)
│   │   │   └── AppContainer.php      # content, width?, height?, extraBindings
│   │   ├── Action.php                # ActionType enum, fromClosure(), custom()
│   │   ├── Binding.php               # name, initialValue, expr()
│   │   ├── State.php                 # create(), get(), set(), subscribe()
│   │   ├── StateId.php               # readonly id
│   │   └── Styling/
│   │       ├── Style.php             # Fluent API (16 methods)
│   │       ├── StyleProperty.php     # 28 enum cases
│   │       └── StyleMatrix.php       # Platform support matrix
│   ├── IR/
│   │   ├── Node.php                  # 54 node types (806 lines)
│   │   ├── Generator.php             # Interface: 50+ methods (90 lines)
│   │   ├── AstToIrVisitor.php        # PHP AST → IR (733 lines)
│   │   └── Builder.php               # buildFromClosure(), buildFromCode()
│   ├── Generator/
│   │   ├── SwiftGenerator.php        # PHP→Swift (505 lines)
│   │   ├── JavaScriptGenerator.php   # PHP→JS (~450 lines)
│   │   ├── KotlinGenerator.php       # PHP→Kotlin (~440 lines)
│   │   ├── DartGenerator.php         # PHP→Dart (~450 lines)
│   │   └── CSharpGenerator.php       # PHP→C# (~460 lines)
│   ├── Codegen/
│   │   ├── CodegenBackend.php        # Abstract: name(), supports(), generate()
│   │   ├── CodegenFactory.php        # 6 backends registered
│   │   ├── SwiftUIBackend.php        # macOS/iOS (325 lines, dynamic stateVars)
│   │   ├── HtmlBackend.php           # Web (377 lines, customScript/innerHTMLVars)
│   │   ├── ComposeBackend.php        # Android Jetpack Compose
│   │   ├── AndroidXmlBackend.php     # Android XML layouts
│   │   ├── WinUIBackend.php          # Windows XAML
│   │   └── Gtk4Backend.php           # Linux GTK4 XML
│   └── Build/
│       ├── Target.php                # 11 platform targets
│       ├── Compiler.php              # Invokes platform toolchains
│       ├── BuildPipeline.php         # Build orchestration
│       └── LibraryResolver.php       # Platform library resolution
└── tests/
    ├── Generator/                    # Swift, JS, Kotlin, Dart, C# tests
    ├── Codegen/                      # Calculator integration tests
    └── Generator/IRNodesTest.php     # 31 tests for all 54 node types
```

## Test Results
- **91 tests, 314 assertions — ALL PASS**
- Pest v3.8.6 test framework

## Platform Support Matrix
| Target | Backend | Generator | Status |
|--------|---------|-----------|--------|
| macOS | SwiftUI | Swift | ✅ Calculator + Pry |
| iOS | SwiftUI | Swift | ✅ Builds |
| Web | HtmlBackend | JavaScript | ✅ Calculator + Pry + Mango |
| Android (XML) | AndroidXmlBackend | — | ✅ Generates XML |
| Android (Compose) | ComposeBackend | Kotlin | ✅ Generates Compose code |
| Windows | WinUIBackend | — | ✅ Generates XAML |
| Linux | Gtk4Backend | — | ✅ Generates GTK4 XML |
| Flutter | — | Dart | ⚠️ Generator only (no backend) |
| WinUI (C#) | — | C# | ⚠️ Generator only (no backend) |

## Known Limitations
- Calculator display shows "2" instead of "1+2" after operator press (display expression text, not arithmetic)
- Pry macOS: Paste uses NSAlert/NSTextView (no system clipboard integration)
- Mango macOS: WebView-based (all UI is web HTML in WKWebView)
- No Dart/C# backends (generators exist but no codegen backends)
