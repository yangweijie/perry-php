# Perry PHP — 代码生成后端的系统审计

> 生成日期：2026-05-08
> 目标：找出每个后端需要改进的具体差距。
> 当前状态：266 个测试，1216 个断言，11 个后端，16 个目标。

---

## 1. 核心风格属性缺失（高优先级）

建立了 37 个 `StyleProperty`。每个后端的处理情况：

| 后端 | 处理的属性 | 覆盖率 | 严重程度 |
|--------|---------|----------|--------|
| HtmlBackend | ~28 | 75% | — |
| Gtk4Backend | ~28 | 75% | — |
| AndroidXmlBackend | ~28 | 75% | — |
| WasmBackend | ~28 | 75% | — |
| WinUIBackend | ~26 | 70% | — |
| **ArkTsBackend** | ~24 | 65% | ⚠️ 中 |
| **FlutterBackend** | ~20 | 54% | ⚠️ 中 |
| **ComposeBackend** | ~5 | 14% | 🔴 高 |
| **GlanceBackend** | ~5 | 14% | 🔴 高 |
| **WearTilesBackend** | ~5 | 14% | 🔴 高 |
| **SwiftUIBackend** | ~3 | 8% | 🔴 高 |
| **AndroidXmlBackend** | 通过 XML 属性处理 | ~28 | — |

**缺失的属性示例：**
- `PaddingLeading`, `PaddingTrailing` — 几乎所有后端都缺失
- `LetterSpacing` — Flutter 有，但其他缺失
- `MinWidth`, `MaxWidth`, `MaxHeight` — 多数缺失
- `Shadow*` — Glance, WearTiles, Compose 缺失
- `BorderWidth` / `BorderColor` — Glance, WearTiles, Compose 缺失
- `TextDecoration` — Glance, WearTiles, Compose 缺失

**建议的行动项：**
1. 为每个后端定义一个 `supportedStyleProperties(): array`，并对照 `StyleProperty::cases()` 进行测试
2. 按优先级（排版 > 尺寸 > 颜色 > 装饰）填补缺失的属性

---

## 2. 计算器集成测试差距

当前仅在 4 个目标上进行测试：`macos`、`web`、`linux`、`windows`。

**缺失的计算器测试：**
- `compose` — Kotlin/Jetpack Compose 输出
- `wasm` — HTML+JS 输出
- `arkts` — HarmonyOS ArkUI 输出
- `glance` — Glance 应用小部件
- `wear-tiles` — Wear OS Tiles
- `flutter` — Dart/Flutter 输出
- `android` — Android XML 布局

`examples/calculator.php` 已经支持所有目标（通过 `Target::fromString()`），但测试没有覆盖。

**建议的行动项：** 为每个缺失的后端添加测试断言。例如：
```php
test('calculator generates Flutter output', function () {
    $out = shell_exec('php examples/calculator.php flutter 2>&1');
    expect($out)->toContain('Perry Calculator');
    expect($out)->toContain('import \'package:flutter/material.dart\'');
});
```

---

## 3. AppContainer/State 处理差距

| 后端 | AppContainer（带状态） | 生成的代码 |
|--------|---------------------|----------------|
| SwiftUI | ✅ `@State` | 好 |
| Html | ✅ JS 变量 | 好 |
| Compose | ✅ `@Composable` + `remember` | 好 |
| Wasm | ✅ JS 变量 | 好 |
| ArkTS | ✅ `@State` | 好 |
| **Flutter** | ✅ `StatefulWidget` + `setState()` | 好 |
| **Glance** | ❌ `generateGlanceState()` 返回 `''` | 🔴 空的 – 没有生成状态绑定 |
| **WearTiles** | ❌ AppContainer 只使用 `content()`，忽略绑定 | 🔴 没有状态处理 |
| **AndroidXml** | ⚠️ 有限的，通过 onClick 处理 | 中 |
| **Gtk4** | ⚠️ 有限的 | 中 |
| **WinUI** | ⚠️ 有限的 | 中 |

**建议的行动项：**
1. GlanceBackend：生成 `remember { mutableStateOf(...) }` 用于状态变量
2. WearTilesBackend：将绑定映射到 `TileRequest` 中的状态
3. AndroidXml/Gtk4/WinUI：为带有完整状态支持的 AppContainer 测试添加测试

---

## 4. 空壳/未支持的小部件处理

当特定平台没有原生映射时，大多数后端使用回退：

| 后端 | 回退策略 | 问题 |
|--------|-------------|-------|
| SwiftUI, Compose, ArkTS, Flutter, Wasm | 抛出/空值 | — |
| **HTML** | `WebView` 有 `<?= ?>` 但其他平台转到 `default` | 有些平台静默失败 |
| **Glance** | `generateUnsupported()` → `Text("[X not supported]")` | ✅ 显式回退 |
| **WearTiles** | `generateUnsupported()` → `Text.Builder("[...]")` | ✅ 显式回退 |
| **AndroidXml** | 回退到 `TextView` 作为占位符 | ✅ 合理 |
| **Gtk4** | `default: return "''"` | 🔴 无声空输出 |
| **WinUI** | `default: return ''` | 🔴 无声空输出 |

**建议的行动项：** 所有后端都应该有显式的 `generateUnsupported($name)` 方法，而不是无声空输出。

---

## 5. 代码质量问题

### 不必要的文档字符串/注释
| 文件 | 行 | 内容 | 建议 |
|------|------|---------|----------|
| `WearTilesBackend.php` | 31–36 | 公共 API 类的 docblock | ✅ 保留（框架公共 API） |
| `FlutterBackend.php` | 32–36 | 公共 API 类的 docblock | ✅ 保留（框架公共 API） |
| `FlutterBackend.php` | 378–381 | `generateStyleWrappers` docstring – 这是公共方法，且逻辑非平凡 | ✅ 保留 |
| `FlutterBackend.php` | 506 | `// Flutter uses maxLines + overflow...` | 🔴 移除 – 死代码，方法返回 `''` |

### 遗漏的 `use` 导入
- `AndroidXmlBackend.php` 使用 `\Perry\UI\ActionType::` 完全限定名称 → 🔴 应该导入
- `Gtk4Backend.php` 使用 `\Perry\UI\ActionType::` → 🔴 应该导入
- `GlanceBackend.php` 使用 `\Perry\UI\Styling\StyleProperty::` → 🔴 应该导入（已导入其他）

### `StyleProperty` 使用不一致
一些后端解引用 `$props = StyleProperty::class`，另一些使用 `\Perry\UI\Styling\StyleProperty::class`。应该统一。

---

## 6. 测试覆盖率差距

| 测试方面 | 覆盖范围 |
|-----------|---------|
| 每个后端所有 16 个小部件 | ✅ 所有后端的烟雾测试 + 关键词测试 |
| 样式属性（每个） | ❌ 未测试 |
| 绑定的边界情况 | ⚠️ 部分：`EmptyVStack`, `Spacer alone`, `Toggle without binding`, `Slider with defaults` |
| Glance 状态绑定 | ❌ 未测试 |
| WearTiles 状态绑定 | ❌ 未测试 |
| 所有后端的 onAppear/onDisappear | ❌ 未测试 |
| 所有后端的 action navigation | ❌ 未测试 |
| 所有后端的 closure transpilation | ⚠️ 仅部分 |
| `fromString` 目标解析 | ✅ 所有 16 个目标 |
| 代码生成器工厂注册 | ✅ 所有 11 个后端 |

**建议的行动项：**
1. 为 `Style` 渲染添加基于属性的测试
2. 为 Glance/WearTiles 添加状态测试

---

## 7. 性能/代码结构

缺少任何 `CodegenBackend` 的 `supportedStyleProperties()` 方法。这使得验证覆盖范围变得困难。添加一个：

```php
abstract public function supportedStyleProperties(): array;
```

这将允许一个集中测试来验证覆盖率矩阵。

---

## 总结：需要拆分的任务

1. **（高优先级）Glance + WearTiles 状态绑定** — 实现 AppContainer 状态处理；目前为空
2. **（高优先级）Compose + Glance + WearTiles 样式属性覆盖** — 将属性从 5 个扩展到 20+ 个
3. **（中优先级）SwiftUI + Compose 样式属性覆盖** — 从 3-5 个扩展到 20+ 个
4. **（中优先级）计算器集成测试** — 为所有 11 个后端添加
5. **（低优先级）杀死无声空回退** — Gtk4，WinUI 默认分支应该显式失败或发出占位符
6. **（低优先级）死注释** — 从 FlutterBackend 中移除无用的溢出注释
7. **（低优先级）完全限定的命名空间** — 在 Gtk4、AndroidXml 中将 `\Perry\UI\` 导入替换为 `use` 语句
8. **（未来）`supportedStyleProperties()` 抽象方法** — 使覆盖率可测试
