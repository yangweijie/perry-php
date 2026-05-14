# 示例

Perry PHP 的实际应用示例。每个示例都有**在线演示**（生成的 HTML/JS）和**文档**（逐步指南）。

| 示例 | 在线演示 | 文档 | 平台 |
|---------|-----------|------|-----------|
| 计算器 | [运行 →](/perry-php/gallery/calculator.html){target="_blank"} | [指南](/zh/examples/calculator.html) | macOS, Web, Windows, Android, Linux |
| 计数器 | [运行 →](/perry-php/gallery/counter.html){target="_blank"} | [指南](/zh/examples/counter.html) | 所有平台 |
| 待办列表 | [运行 →](/perry-php/gallery/todo.html){target="_blank"} | [指南](/zh/examples/todo.html) | macOS, Web |
| Pry — JSON 查看器 | [运行 →](/perry-php/gallery/pry.html){target="_blank"} | [指南](/zh/examples/pry.html) | Windows (WebView2), macOS, Web |
| 功能展示 | [运行 →](/perry-php/gallery/showcase.html){target="_blank"} | — | Web, macOS, Windows |

所有示例都在 `examples/` 目录中，可直接运行：

```bash
# 生成代码
php examples/calculator.php web > output.html

# 构建原生应用
php examples/calculator.php macos --build
php examples/pry.php windows --build
```
