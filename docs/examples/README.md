# Examples

Practical examples showing Perry PHP in action. Each example has a **Live Demo** (generated HTML/JS) and **Docs** (step-by-step guide).

| Example | Live Demo | Docs | Platforms |
|---------|-----------|------|-----------|
| Calculator | [Run →](/perry-php/gallery/calculator.html){target="_blank"} | [Guide](/examples/calculator.html) | macOS, Web, Windows, Android, Linux |
| Counter | [Run →](/perry-php/gallery/counter.html){target="_blank"} | [Guide](/examples/counter.html) | All platforms |
| Todo List | [Run →](/perry-php/gallery/todo.html){target="_blank"} | [Guide](/examples/todo.html) | macOS, Web |
| Pry — JSON Viewer | [Run →](/perry-php/gallery/pry.html){target="_blank"} | [Guide](/examples/pry.html) | Windows (WebView2), macOS, Web |
| Showcase | [Run →](/perry-php/gallery/showcase.html){target="_blank"} | — | Web, macOS, Windows |

All examples are in the `examples/` directory and can be run directly:

```bash
# Generate code
php examples/calculator.php web > output.html

# Build native app
php examples/calculator.php macos --build
php examples/pry.php windows --build
```
