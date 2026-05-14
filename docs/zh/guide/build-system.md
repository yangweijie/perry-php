# 构建系统

Perry 可以使用 `Compiler` 类和平台工具链将 UI 定义编译为原生可执行文件。

---

## 编译

```php
use Perry\Build\Compiler;
use Perry\Build\Target;
use Perry\UI\Widget\VStack;
use Perry\UI\Widget\Text;

$compiler = new Compiler(Target::fromString('windows'));
$root = new VStack(new Text('Hello, Perry!'));

$result = $compiler->compile($root, 'myapp');

if ($result->success) {
    echo "输出：{$result->outputFile}\n";
} else {
    echo "失败：{$result->error}\n";
}
```

### 编译命令

```bash
# macOS
php examples/calculator.php macos --build
# 输出：build/Calculator.app

# Windows
php examples/pry.php windows --build
# 输出：build/pry.exe + build/pry.html

# Web
php examples/calculator.php web
# 输出：build/calculator.html

# 通用 CLI 编译
./bin/perry compile --target=macos
./bin/perry compile --target=windows
```

---

## Windows 要求

使用 `WebView` 微件的应用（如 Pry JSON 查看器）需要 **WebView2 Runtime**。

### 安装 WebView2 Runtime

- **选项 1 — Evergreen Bootstrapper**（推荐，自动更新）：
  从 [Microsoft Edge WebView2](https://developer.microsoft.com/en-us/microsoft-edge/webview2/) 下载
- **选项 2 — Evergreen Standalone Installer**：
  https://go.microsoft.com/fwlink/p/?LinkId=2124702
- **选项 3 — Fixed Version**（离线/受限环境）：
  https://developer.microsoft.com/en-us/microsoft-edge/webview2/#download-section

---

## macOS / iOS 要求

- macOS 13+ 或 iOS 17+
- Xcode 15+ with Swift 5.9+
- `swiftc` 必须在 PATH 中

## Linux（GTK4）要求

- GTK4 开发库：`libgtk-4-dev`
- GCC 或 Clang
- `pkg-config` 用于 GTK4 发现

## Android 要求

- Android SDK（设置 `ANDROID_HOME` 环境变量）
- Gradle
- Java 17+

## Web

无需构建工具——生成独立的 HTML 文件。

---

## CLI 用法

```bash
./bin/perry info                   # 平台信息
./bin/perry demo --target=macos    # 生成演示代码
./bin/perry codegen --target=web   # 为指定后端生成代码
./bin/perry compile --target=macos # 编译为可执行文件
./bin/perry targets                # 列出目标
./bin/perry backends               # 列出后端
```
