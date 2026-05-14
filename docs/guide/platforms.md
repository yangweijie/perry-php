# Platform Support

## Target Platforms

| Platform | Target String | Backend |
|----------|--------------|---------|
| macOS | `macos` | `swiftui` |
| iOS | `ios` | `swiftui` |
| iOS Simulator | `ios-simulator` | `swiftui` |
| tvOS | `tvos` | `swiftui` |
| visionOS | `visionos` | `swiftui` |
| watchOS | `watchos` | `swiftui` |
| Glance (Android widgets) | `glance` | `glance` |
| Wear Tiles | `wear-tiles` | `wear-tiles` |
| Android | `android` | `compose` / `android-xml` |
| Linux | `gtk4-linux` | `gtk4` |
| Windows | `windows` | `winui` |
| Flutter | `flutter` | `flutter` |
| HarmonyOS | `harmonyos` | `arkts` |
| Web | `web` | `html` |
| WebAssembly | `wasm` | `wasm` |

## Target Detection

```php
use Perry\Build\Target;

$target = Target::detect();            // auto-detect current platform
$target = Target::fromString('macos'); // from string

$target->isApple();    // true for macOS, iOS, tvOS, visionOS, watchOS
$target->isDesktop();  // true for macOS, Linux, Windows
$target->isMobile();   // true for iOS, Android, watchOS
```

## CLI

```bash
# List all targets
./bin/perry targets

# List all backends
./bin/perry backends
```
