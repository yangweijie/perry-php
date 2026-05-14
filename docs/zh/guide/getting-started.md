# 快速开始

## 安装

```bash
composer require perry/perry
```

**系统要求：** PHP 8.2+

## 第一个应用

创建一个简单的计数器：

```php
<?php

use Perry\App;
use Perry\UI\Binding;
use Perry\UI\Styling\Style;
use Perry\UI\Widget\Button;
use Perry\UI\Widget\HStack;
use Perry\UI\Widget\Text;
use Perry\UI\Widget\VStack;

$count = new Binding('count', 0);

$app = new App();
$app->setRoot(
    new VStack(
        (new Text($count))->style(Style::make()->fontSize(48)),
        new HStack(
            (new Button('-', function () use ($count) {
                $count -= 1;
            }))->style(Style::make()->fontSize(24)->padding(16)),
            (new Button('+', function () use ($count) {
                $count += 1;
            }))->style(Style::make()->fontSize(24)->padding(16)),
        ),
    )
);

// 生成 Web 代码
echo $app->generateCode('html');
```

## 生成代码

```bash
# 生成 SwiftUI 代码（macOS/iOS）
php your-app.php > App.swift

# 生成 HTML
php your-app.php > index.html

# 生成 Jetpack Compose
php your-app.php > MainActivity.kt

# 生成 WPF/XAML（ Windows）
php your-app.php > MainWindow.xaml

# 生成 GTK4 XML（Linux）
php your-app.php > app.ui

# 生成 ArkTS（HarmonyOS）
php your-app.php > pages/index.ets

# 生成 Flutter Dart
php your-app.php > main.dart
```

## 使用 CLI

```bash
./bin/perry info                   # 查看平台信息
./bin/perry demo --target=macos    # 生成演示代码
./bin/perry codegen --target=web   # 为指定后端生成代码
./bin/perry compile --target=macos # 编译为可执行文件
./bin/perry targets                # 列出所有 15 个目标平台
./bin/perry backends               # 列出所有代码生成后端
```

## 项目结构

```
src/
├── App.php              # 入口：setRoot, generateCode, generateForTarget
├── Build/               # 构建管线：Target, Compiler, Linker
├── Codegen/             # 11 个平台代码生成器
├── Generator/           # 5 个语言生成器（Swift, Kotlin, Dart, JS, C#）
├── IR/                  # 54 个 IR 节点类型，用于闭包转译
└── UI/                  # DSL：16 个微件、29 个样式属性、平台驱动
```
