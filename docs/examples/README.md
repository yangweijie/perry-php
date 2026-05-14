# Examples

Practical examples showing Perry PHP in action. Each example has a **Live Demo** (generated HTML/JS) and **Docs** (step-by-step guide).

| Example | Live Demo | Docs | Platforms |
|---------|-----------|------|-----------|
| Calculator | [Run →](/gallery/calculator.html) | [Guide](/examples/calculator.html) | macOS, Web, Windows, Android, Linux |
| Counter | [Run →](/gallery/counter.html) | [Guide](/examples/counter.html) | All platforms |
| Todo List | [Run →](/gallery/todo.html) | [Guide](/examples/todo.html) | macOS, Web |
| Pry — JSON Viewer | [Run →](/gallery/pry.html) | [Guide](/examples/pry.html) | Windows (WebView2), macOS, Web |
| Showcase | [Run →](/gallery/showcase.html) | — | Web, macOS, Windows |

All examples are in the `examples/` directory and can be run directly:

```bash
# Generate code
php examples/calculator.php web > output.html

# Build native app
php examples/calculator.php macos --build
php examples/pry.php windows --build
```
