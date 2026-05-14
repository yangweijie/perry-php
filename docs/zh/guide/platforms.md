# 平台支持

## 目标平台

| 平台 | 目标字符串 | 后端 |
|----------|--------------|---------|
| macOS | `macos` | `swiftui` |
| iOS | `ios` | `swiftui` |
| iOS 模拟器 | `ios-simulator` | `swiftui` |
| tvOS | `tvos` | `swiftui` |
| visionOS | `visionos` | `swiftui` |
| watchOS | `watchos` | `swiftui` |
| Glance（Android 微件） | `glance` | `glance` |
| Wear Tiles | `wear-tiles` | `wear-tiles` |
| Android | `android` | `compose` / `android-xml` |
| Linux | `gtk4-linux` | `gtk4` |
| Windows | `windows` | `winui` |
| Flutter | `flutter` | `flutter` |
| HarmonyOS | `harmonyos` | `arkts` |
| Web | `web` | `html` |
| WebAssembly | `wasm` | `wasm` |

## 目标检测

```php
use Perry\Build\Target;

$target = Target::detect();            // 自动检测当前平台
$target = Target::fromString('macos'); // 从字符串指定

$target->isApple();    // macOS, iOS, tvOS, visionOS, watchOS 为 true
$target->isDesktop();  // macOS, Linux, Windows 为 true
$target->isMobile();   // iOS, Android, watchOS 为 true
```

## CLI

```bash
# 列出所有目标
./bin/perry targets

# 列出所有后端
./bin/perry backends
```
