# 示例

Perry PHP 的实际应用示例。

| 示例 | 说明 | 平台 |
|---------|-------------|-----------|
| [计算器](/zh/examples/calculator.html) | 含 7 种动作的完整计算器 | macOS, Web, Windows, Android, Linux |
| [计数器](/zh/examples/counter.html) | 简单的增减计数器 | 所有平台 |
| [待办列表](/zh/examples/todo.html) | 可添加/清除的待办列表 | macOS, Web |
| [Pry — JSON 查看器](/zh/examples/pry.html) | 带树形视图的原生 JSON 查看器 | Windows (WebView2), macOS, Web |

所有示例都在 `examples/` 目录中，可直接运行：

```bash
# 生成代码
php examples/calculator.php web > output.html

# 构建原生应用
php examples/calculator.php macos --build
php examples/pry.php windows --build
```
