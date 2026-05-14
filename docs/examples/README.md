# Examples

Practical examples showing Perry PHP in action.

| Example | Description | Platforms |
|---------|-------------|-----------|
| [Calculator](/examples/calculator.html) | Full calculator with 7 actions | macOS, Web, Windows, Android, Linux |
| [Counter](/examples/counter.html) | Simple increment/decrement counter | All platforms |
| [Todo List](/examples/todo.html) | Todo list with add/clear | macOS, Web |
| [Pry — JSON Viewer](/examples/pry.html) | Native JSON viewer with tree view | Windows (WebView2), macOS, Web |

All examples are in the `examples/` directory and can be run directly:

```bash
# Generate code
php examples/calculator.php web > output.html

# Build native app
php examples/calculator.php macos --build
php examples/pry.php windows --build
```
