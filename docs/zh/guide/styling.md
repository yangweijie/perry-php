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
| `padding(float)` | float | 统一内边距 |
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
| 前景色 | `StyleProperty::ForegroundColor` | `string` | 文字/图标颜色 |
| 边框颜色 | `StyleProperty::BorderColor` | `string` | 边框颜色 |
| 边框宽度 | `StyleProperty::BorderWidth` | `float` | 边框宽度 |
| 圆角半径 | `StyleProperty::CornerRadius` | `float` | 圆角 |
| 不透明度 | `StyleProperty::Opacity` | `float` | 透明度（0–1） |
| 内边距 | `StyleProperty::Padding` | `float` | 统一内边距 |
| 字号 | `StyleProperty::FontSize` | `float` | 字号 |
| 字重 | `StyleProperty::FontWeight` | `string` | 字重 |
| 文本对齐 | `StyleProperty::TextAlignment` | `string` | 文本对齐方式 |

完整 29 个属性列表请参阅 [API 参考](/zh/guide/api-reference.html)。

---

## 平台支持矩阵

所有后端都支持完整的样式属性和事件系统（详见[英文版](/guide/styling.html)的平台支持矩阵）。
