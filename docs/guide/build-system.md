# Build System

Perry can compile UI definitions into native executables using the `Compiler` class and platform toolchains.

---

## Compiling

```php
use Perry\Build\Compiler;
use Perry\Build\Target;
use Perry\UI\Widget\VStack;
use Perry\UI\Widget\Text;

$compiler = new Compiler(Target::fromString('windows'));
$root = new VStack(new Text('Hello, Perry!'));

$result = $compiler->compile($root, 'myapp');

if ($result->success) {
    echo "Output: {$result->outputFile}\n";
} else {
    echo "Failed: {$result->error}\n";
}
```

### Compile Commands

```bash
# macOS
php examples/calculator.php macos --build
# Output: build/Calculator.app

# Windows
php examples/pry.php windows --build
# Output: build/pry.exe + build/pry.html

# Web
php examples/calculator.php web
# Output: build/calculator.html

# Generic CLI compile
./bin/perry compile --target=macos
./bin/perry compile --target=windows
```

---

## Windows Requirements

Apps that use the `WebView` widget (e.g., the Pry JSON viewer) require **WebView2 Runtime** on Windows.

### Install WebView2 Runtime

- **Option 1 — Evergreen Bootstrapper** (recommended, auto-updates):  
  Download from [Microsoft Edge WebView2](https://developer.microsoft.com/en-us/microsoft-edge/webview2/):  
  https://go.microsoft.com/fwlink/p/?LinkId=2124703

- **Option 2 — Evergreen Standalone Installer**:  
  https://go.microsoft.com/fwlink/p/?LinkId=2124702

- **Option 3 — Fixed Version** (for offline/restricted environments):  
  https://developer.microsoft.com/en-us/microsoft-edge/webview2/#download-section

### Check if Already Installed

Open `Control Panel → Programs and Features` and look for **WebView2 Runtime**.  
Or check `C:\Program Files (x86)\Microsoft\EdgeWebView\Application\`.

WebView2 ships with Microsoft Edge (Chromium-based), so it's often already present on modern Windows systems.

### Build Output

The compiler writes `pry.html` alongside the `.exe` file. The app reads it at runtime via WebView2's `NavigateToString()`.

---

## macOS / iOS Requirements

- macOS 13+ or iOS 17+
- Xcode 15+ with Swift 5.9+
- `swiftc` must be in PATH

## Linux (GTK4) Requirements

- GTK4 development libraries: `libgtk-4-dev`
- GCC or Clang
- `pkg-config` for GTK4 discovery

## Android Requirements

- Android SDK (set `ANDROID_HOME` environment variable)
- Gradle (or use the wrapper)
- Java 17+

## Web

No build tools required — generates standalone HTML files.

---

## CLI Usage

```bash
./bin/perry info                   # platform info
./bin/perry demo --target=macos    # generate demo code
./bin/perry codegen --target=web   # generate for backend
./bin/perry compile --target=macos # compile to executable
./bin/perry targets                # list targets
./bin/perry backends               # list backends
```
