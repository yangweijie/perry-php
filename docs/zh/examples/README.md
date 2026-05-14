# 示例

Perry PHP 的实际应用示例。每个示例都有**在线演示**（生成的 HTML/JS）和**文档**（逐步指南）。

<table>
<thead><tr><th>示例</th><th>在线演示</th><th>文档</th><th>平台</th></tr></thead>
<tbody>
<tr>
  <td>计算器</td>
  <td><a href="/perry-php/gallery/calculator.html" target="_blank">运行 →</a></td>
  <td><router-link to="/zh/examples/calculator.html">指南</router-link></td>
  <td>macOS, Web, Windows, Android, Linux</td>
</tr>
<tr>
  <td>计数器</td>
  <td><a href="/perry-php/gallery/counter.html" target="_blank">运行 →</a></td>
  <td><router-link to="/zh/examples/counter.html">指南</router-link></td>
  <td>所有平台</td>
</tr>
<tr>
  <td>待办列表</td>
  <td><a href="/perry-php/gallery/todo.html" target="_blank">运行 →</a></td>
  <td><router-link to="/zh/examples/todo.html">指南</router-link></td>
  <td>macOS, Web</td>
</tr>
<tr>
  <td>Pry — JSON 查看器</td>
  <td><a href="/perry-php/gallery/pry.html" target="_blank">运行 →</a></td>
  <td><router-link to="/zh/examples/pry.html">指南</router-link></td>
  <td>Windows (WebView2), macOS, Web</td>
</tr>
<tr>
  <td>功能展示</td>
  <td><a href="/perry-php/gallery/showcase.html" target="_blank">运行 →</a></td>
  <td>—</td>
  <td>Web, macOS, Windows</td>
</tr>
</tbody>
</table>

所有示例都在 `examples/` 目录中，可直接运行：

```bash
# 生成代码
php examples/calculator.php web > output.html

# 构建原生应用
php examples/calculator.php macos --build
php examples/pry.php windows --build
```
