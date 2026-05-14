# Guide

Welcome to the Perry PHP guide. Perry is a **cross-platform UI code generation framework** — you define your UI once in PHP and generate native source code for 11 platforms.

## How It Works

```
PHP Widget Tree (DSL)
    │
    ▼
Codegen Backend (e.g., SwiftUIBackend, HtmlBackend)
    │
    ▼
Native Source Code (Swift, HTML, Kotlin, C#, etc.)
    │
    ▼
Platform Toolchain (swiftc, dotnet, gradle, etc.)
    │
    ▼
Native App (.app, .exe, .apk, etc.)
```

## Contents

- **[Getting Started](/guide/getting-started.html)** — Installation and your first app
- **[UI Components](/guide/ui-components.html)** — All 16 widgets: Text, Button, VStack, HStack, Spacer, Image, ScrollView, TextInput, Toggle, AppContainer, WebView, Slider, Checkbox, RadioButton, Progress, TabView
- **[State Management](/guide/state-management.html)** — Binding and State objects for reactive data
- **[Actions](/guide/actions.html)** — Simple actions, Closure actions (AST transpilation), PHP function mappings
- **[Styling](/guide/styling.html)** — 29 style properties, Style builder, platform support matrix
- **[Code Generation](/guide/code-generation.html)** — 11 backends, 5 generators, 54 IR node types
- **[Platform Support](/guide/platforms.html)** — Supported targets and build requirements
- **[Build System](/guide/build-system.html)** — CLI usage, Windows (WebView2) requirements
- **[Extending Perry](/guide/extending.html)** — Custom widgets, backends, generators, and function mappings
