# Examples

Practical examples showing Perry PHP in action. Each example has a **Live Demo** (generated HTML/JS) and **Docs** (step-by-step guide).

<table>
<thead><tr><th>Example</th><th>Live Demo</th><th>Docs</th><th>Platforms</th></tr></thead>
<tbody>
<tr>
  <td>Calculator</td>
  <td><a href="/perry-php/gallery/calculator.html" target="_blank">Run →</a></td>
  <td><router-link to="/examples/calculator.html">Guide</router-link></td>
  <td>macOS, Web, Windows, Android, Linux</td>
</tr>
<tr>
  <td>Counter</td>
  <td><a href="/perry-php/gallery/counter.html" target="_blank">Run →</a></td>
  <td><router-link to="/examples/counter.html">Guide</router-link></td>
  <td>All platforms</td>
</tr>
<tr>
  <td>Todo List</td>
  <td><a href="/perry-php/gallery/todo.html" target="_blank">Run →</a></td>
  <td><router-link to="/examples/todo.html">Guide</router-link></td>
  <td>macOS, Web</td>
</tr>
<tr>
  <td>Pry — JSON Viewer</td>
  <td><a href="/perry-php/gallery/pry.html" target="_blank">Run →</a></td>
  <td><router-link to="/examples/pry.html">Guide</router-link></td>
  <td>Windows (WebView2), macOS, Web</td>
</tr>
<tr>
  <td>Showcase</td>
  <td><a href="/perry-php/gallery/showcase.html" target="_blank">Run →</a></td>
  <td>—</td>
  <td>Web, macOS, Windows</td>
</tr>
</tbody>
</table>

All examples are in the `examples/` directory and can be run directly:

```bash
# Generate code
php examples/calculator.php web > output.html

# Build native app
php examples/calculator.php macos --build
php examples/pry.php windows --build
```
