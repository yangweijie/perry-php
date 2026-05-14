# 样式

Perry 提供 fluent 风格的样式构建器 API，包含 29 个样式属性，每个属性都映射到各平台的原生样式。

---

## 样式构建器

使用 fluent API 链式调用：

```php
use Perry\UI\Styling\Style;

$cardStyle = Style::make()
    ->backgroundColor('#1a1a1a')
    ->foregroundColor('#ffffff')
    ->fontSize(16)
    ->fontWeight('bold')
    ->padding(16)
    ->paddingAll(8, 8, 12, 12)    // 上、下、前、后
    ->width(300)
    ->height(200)
    ->cornerRadius(12)
    ->border(1, '#333333')
    ->shadow('#000000', 4, 2, 2)
    ->opacity(0.9)
    ->textAlignment('center');

// 合并样式
$base = Style::make()->fontSize(14)->foregroundColor('#333');
$highlight = Style::make()->backgroundColor('#ffff00');
$merged = $base->merge($highlight);
```

### 样式方法

| 方法 | 参数 | 说明 |
|--------|-----------|-------------|
| `make()` | — | 创建新的 Style |
| `set(StyleProperty, $value)` | enum, mixed | 设置任意属性 |
| `get(StyleProperty)` | enum | 获取属性值 |
| `has(StyleProperty)` | enum | 检查属性是否已设置 |
| `all()` | — | 获取所有属性 |
| `merge(Style)` | Style | 合并另一个样式（右侧优先） |
| `backgroundColor(hex)` | string | 背景色 |
| `foregroundColor(hex)` | string | 文字/图标颜色 |
| `fontSize(float)` | float | 字号（点） |
| `fontWeight(string)` | string | 字重（`bold`、`light` 等） |
| `textAlignment(string)` | string | 文本对齐（`center`、`left`、`right`） |
| `padding(float)` | float | 统一内边距 |
| `paddingAll(top, bottom, leading, trailing)` | float×4 | 各边单独内边距 |
| `width(float)` | float | 固定宽度 |
| `height(float)` | float | 固定高度 |
| `cornerRadius(float)` | float | 圆角半径 |
| `opacity(float)` | float | 不透明度（0.0–1.0） |
| `border(width, color)` | float, string | 边框宽度 + 颜色 |
| `shadow(color, radius, offsetX, offsetY)` | string, float×3 | 投影 |

完整方法列表请参阅 [API 参考](/zh/guide/api-reference.html)。

---

## 样式属性参考

| 属性 | 枚举值 | 类型 | 说明 |
|----------|------|------|-------------|
| 背景色 | `StyleProperty::BackgroundColor` | `string` | 背景颜色（十六进制） |
| 前景色 | `StyleProperty::ForegroundColor` | `string` | 文字/图标颜色（十六进制） |
| 边框颜色 | `StyleProperty::BorderColor` | `string` | 边框颜色（十六进制） |
| 边框宽度 | `StyleProperty::BorderWidth` | `float` | 边框宽度 |
| 圆角半径 | `StyleProperty::CornerRadius` | `float` | 圆角 |
| 不透明度 | `StyleProperty::Opacity` | `float` | 透明度（0–1） |
| 内边距 | `StyleProperty::Padding` | `float` | 统一内边距 |
| 上内边距 | `StyleProperty::PaddingTop` | `float` | 上方内边距 |
| 下内边距 | `StyleProperty::PaddingBottom` | `float` | 下方内边距 |
| 前内边距 | `StyleProperty::PaddingLeading` | `float` | 左侧内边距 |
| 后内边距 | `StyleProperty::PaddingTrailing` | `float` | 右侧内边距 |
| 外边距 | `StyleProperty::Margin` | `float` | 统一外边距 |
| 宽度 | `StyleProperty::Width` | `float` | 固定宽度 |
| 高度 | `StyleProperty::Height` | `float` | 固定高度 |
| 最小宽度 | `StyleProperty::MinWidth` | `float` | 最小宽度 |
| 最小高度 | `StyleProperty::MinHeight` | `float` | 最小高度 |
| 最大宽度 | `StyleProperty::MaxWidth` | `float` | 最大宽度 |
| 最大高度 | `StyleProperty::MaxHeight` | `float` | 最大高度 |
| 字号 | `StyleProperty::FontSize` | `float` | 字号 |
| 字重 | `StyleProperty::FontWeight` | `string` | 字重 |
| 字体 | `StyleProperty::FontFamily` | `string` | 字体名称 |
| 文本对齐 | `StyleProperty::TextAlignment` | `string` | 文本对齐方式 |
| 文本装饰 | `StyleProperty::TextDecoration` | `string` | 文本装饰 |
| 行间距 | `StyleProperty::LineSpacing` | `float` | 行高间距 |
| 字间距 | `StyleProperty::LetterSpacing` | `float` | 字符间距 |
| 阴影颜色 | `StyleProperty::ShadowColor` | `string` | 阴影颜色 |
| 阴影半径 | `StyleProperty::ShadowRadius` | `float` | 阴影模糊半径 |
| 阴影偏移 X | `StyleProperty::ShadowOffsetX` | `float` | 阴影 X 偏移 |
| 阴影偏移 Y | `StyleProperty::ShadowOffsetY` | `float` | 阴影 Y 偏移 |
| 旋转 | `StyleProperty::Rotate` | `float` | 旋转角度（度） |
| 缩放 | `StyleProperty::Scale` | `float` | 统一缩放因子 |
| 平移 X | `StyleProperty::TranslateX` | `float` | X 轴平移 |
| 平移 Y | `StyleProperty::TranslateY` | `float` | Y 轴平移 |
| Flex 增长 | `StyleProperty::FlexGrow` | `float` | flex 增长因子 |
| Flex 收缩 | `StyleProperty::FlexShrink` | `float` | flex 收缩因子 |
| 间距 | `StyleProperty::Gap` | `float` | 子元素间距 |

---

## 平台支持矩阵

所有后端都支持完整的样式属性和事件系统：

| 特性 | macOS (SwiftUI) | iOS (SwiftUI) | Android (XML) | Android (Compose) | Web (HTML) | Linux (Gtk4) | Windows (WinUI) |
|---------|------------------|---------------|-----------------|--------------------|--------------|----------------|---------------|
| **样式属性** |
| BackgroundColor | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| ForegroundColor | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| BorderWidth/BorderColor | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| CornerRadius | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| Padding（所有边） | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| Margin | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| Width / Height | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| FontSize / FontWeight | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| TextAlignment | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| Shadow | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| TextDecoration | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| LineSpacing | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| Min/Max Width/Height | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| Opacity | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| Transform (Rotate/Scale) | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| **事件系统** |
| Button 点击 | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| Slider onChange | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| TextInput onChange | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| Toggle onToggle | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| **微件** |
| Slider / TextInput / Toggle | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| NavigationView / TabView | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| List | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |

---

## 使用 StyleMatrix

```php
use Perry\UI\Styling\StyleMatrix;
use Perry\UI\Styling\StyleProperty;
use Perry\UI\Styling\PlatformSupport;

$matrix = new StyleMatrix();

// 检查某个属性在某个平台上的支持情况
$support = $matrix->getSupport('macos', StyleProperty::CornerRadius);
// PlatformSupport::Wired（完全支持）

// 获取某平台所有支持的属性
$wired = $matrix->getWiredProperties('macos');

// 检查平台是否完全支持所有属性
$full = $matrix->isFullySupported('macos'); // bool

// 获取缺失的属性
$missing = $matrix->getMissingProperties('android');
```

### 支持级别

| 级别 | 说明 |
|-------|-------------|
| `Wired` | 完全支持，生成原生代码 |
| `Stub` | 存根实现（tvOS, visionOS, watchOS） |
| `Missing` | 尚未实现 |
| `NotApplicable` | 对该平台不适用 |
