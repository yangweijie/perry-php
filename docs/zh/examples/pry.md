# Pry — JSON 查看器

原生 JSON 查看器，包含树形视图、搜索、语法高亮和剪贴板支持。使用 `WebView` 微件在原生窗口中嵌入完整的 HTML/JS UI。

```bash
# 构建 Windows 应用
php examples/pry.php windows --build
# 输出：build/pry.exe + build/pry.html

# 构建 macOS 应用
php examples/pry.php macos --build
# 输出：build/pry.app

# 生成 Web 版本
php examples/pry.php web
# 输出：build/pry.html
```

## 工作原理

Pry 演示了一个强大的模式：先用 `HtmlBackend` 生成完整的 Web UI，然后通过 `WebView` 本地嵌入：

```php
// 1. 使用 HtmlBackend 在 Web 模式下生成完整 HTML
$webApp = new App(Target::fromString('web'));
$webApp->setRoot(
    new AppContainer(
        new VStack(
            // ...UI 组件
        ),
        800, 700,
        $jsonInput, $treeHtml, $stats, $searchInfo,
    )
);
$webHtml = $webApp->generateForTarget();

// 2. 嵌入 WebView 并编译
$root = new AppContainer(
    new WebView($webHtml),
    800, 700,
);
$compiler = new Compiler(Target::fromString('windows'));
$result = $compiler->compile($root, 'pry');
```

## 功能特性

- **树形视图**，支持对象和数组的展开/折叠
- **搜索**键和值，匹配高亮
- **右键菜单**——右键单击任意节点复制路径或值
- **格式化 / 压缩** JSON
- **示例 JSON** 用于快速测试
- **语法高亮**——键、字符串、数字、布尔值、null
- **节点数量、字节大小、解析时间**统计

## 关键模式：WebView 嵌入

`--build` 标志在 `pry.php` 中的工作方式：

1. 临时切换到 `HtmlBackend` 的 Web 模式
2. 生成完整的 HTML 页面（含 JS 树渲染器）
3. 将 HTML 包裹在 `WebView` 微件中
4. 为目标平台编译（Windows、macOS 等）

这使 Web 版本的功能完全一致，同时以原生应用运行。

## Windows 说明

需要 [WebView2 Runtime](/zh/guide/build-system.html)。生成的 `pry.html` 文件在构建时与 `.exe` 一并写入。
