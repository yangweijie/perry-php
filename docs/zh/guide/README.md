# 指南

欢迎查看 Perry PHP 指南。Perry 是一个**跨平台 UI 代码生成框架**——你可以用 PHP 定义 UI，为 11 个平台生成原生代码。

## 工作原理

```
PHP 微件树 (DSL)
    │
    ▼
代码生成后端 (例如 SwiftUIBackend、HtmlBackend)
    │
    ▼
原生源代码 (Swift、HTML、Kotlin、C# 等)
    │
    ▼
平台工具链 (swiftc、dotnet、gradle 等)
    │
    ▼
原生应用 (.app、.exe、.apk 等)
```

## 目录

- **[快速开始](/zh/guide/getting-started.html)** — 安装和你的第一个应用
- **[UI 组件](/zh/guide/ui-components.html)** — 所有 16 个微件
- **[状态管理](/zh/guide/state-management.html)** — Binding 和 State 对象
- **[动作](/zh/guide/actions.html)** — 简单动作、闭包动作、PHP 函数映射
- **[样式](/zh/guide/styling.html)** — 29 个样式属性、平台支持矩阵
- **[代码生成](/zh/guide/code-generation.html)** — 11 个后端、5 个生成器、54 个 IR 节点类型
- **[平台支持](/zh/guide/platforms.html)** — 支持的目标和构建要求
- **[构建系统](/zh/guide/build-system.html)** — CLI 使用、构建需求
- **[扩展 Perry](/zh/guide/extending.html)** — 自定义微件、后端、生成器和函数映射
- **[API 参考](/zh/guide/api-reference.html)** — 完整的 API 文档
- **[最佳实践](/zh/guide/best-practices.html)** — 编写高质量 Perry 代码的指南
- **[贡献指南](/zh/guide/contributing.html)** — 如何为 Perry PHP 做贡献
