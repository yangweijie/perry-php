# Pry — JSON Viewer

A native JSON viewer with tree view, search, syntax highlighting, and clipboard support. Uses the `WebView` widget to embed a full HTML/JS UI inside a native window.

```bash
# Build for Windows
php examples/pry.php windows --build
# Output: build/pry.exe + build/pry.html

# Build for macOS
php examples/pry.php macos --build
# Output: build/pry.app

# Generate web version
php examples/pry.php web
# Output: build/pry.html
```

## How It Works

Pry demonstrates a powerful pattern: generate a full web UI with `HtmlBackend`, then embed it natively via `WebView`:

```php
// 1. Generate full HTML using HtmlBackend in web mode
$webApp = new App(Target::fromString('web'));
$webApp->setRoot(
    new AppContainer(
        new VStack(
            (new HStack(
                (new Text('Pry'))->style($headerStyle),
                new Spacer(),
            ))->style($toolbarStyle),
            (new Text($treeHtml))->style($treeStyle),
            (new Text($stats))->style($statusStyle),
        ),
        800, 700,
        $jsonInput, $treeHtml, $stats, $searchInfo,
    )
);
$webHtml = $webApp->generateForTarget();

// 2. Embed in WebView and compile for Windows
$root = new AppContainer(
    new WebView($webHtml),
    800, 700,
);
$compiler = new Compiler(Target::fromString('windows'));
$result = $compiler->compile($root, 'pry');
```

## Features

- **Tree view** with expand/collapse for objects and arrays
- **Search** keys and values with match highlighting
- **Context menu** — right-click any node to copy path or value
- **Format / Minify** JSON
- **Sample JSON** for quick testing
- **Syntax highlighting** — keys, strings, numbers, booleans, null
- **Node count, byte size, parse time** stats

## Key Pattern: WebView Embedding

The `--build` flag in `pry.php`:

1. Temporarily switches to `HtmlBackend` in web mode
2. Generates the complete HTML page (with JS tree renderer)
3. Wraps the HTML in a `WebView` widget
4. Compiles for the target platform (Windows, macOS, etc.)

This gives 100% feature parity with the web version while running as a native app.

## Windows Note

Requires [WebView2 Runtime](/guide/build-system.html#windows-requirements). The generated `pry.html` file is written alongside the `.exe` during build and loaded at runtime.
