---
home: true
title: 首页
heroText: Perry PHP
tagline: 用 PHP 定义 UI，为 11 个平台生成原生代码。
actions:
  - text: 快速开始
    link: /zh/guide/getting-started.html
    type: primary
  - text: 在 GitHub 上查看
    link: https://github.com/yangweijie/perry-php
    type: secondary
features:
  - title: 一套 DSL，11 个平台
    details: SwiftUI (macOS/iOS)、HTML/JS (Web)、Jetpack Compose (Android)、Android XML、GTK4 (Linux)、WinUI (Windows)、ArkTS (HarmonyOS)、Glance、Wear Tiles、Flutter。
  - title: 闭包 → 跨平台代码
    details: 编写包含完整逻辑（if/else、循环、数学运算）的 PHP 闭包，基于 AST 的转译器自动编译为 Swift、JavaScript、Kotlin、Dart 和 C#。
  - title: 完整的样式系统
    details: 29 个样式属性——颜色、字体、内边距、阴影、变换、动画。每个后端映射到原生样式 API。
  - title: 响应式状态管理
    details: Binding 对象实现双向响应式数据流，由 AppContainer 自动收集，输出为 @State、useState 或 mutableStateOf。
  - title: 978 项测试，全部通过
    details: 覆盖 11 个后端、5 个生成器、54 个 IR 节点类型和 97+ 个 PHP 函数映射的 3689 个断言。
  - title: 无需运行时
    details: 仅代码生成——目标设备上无需 PHP 运行时。你的 PHP 代码变成纯原生代码。
footer: MIT Licensed | 版权所有 © 2024 Perry PHP
---
