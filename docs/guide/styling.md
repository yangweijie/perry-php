# Styling

Perry provides a fluent style builder API with 29 style properties that map to native styling on each platform.

---

## Style Builder

Use the fluent API to chain methods:

```php
use Perry\UI\Styling\Style;

$cardStyle = Style::make()
    ->backgroundColor('#1a1a1a')
    ->foregroundColor('#ffffff')
    ->fontSize(16)
    ->fontWeight('bold')
    ->padding(16)
    ->paddingAll(8, 8, 12, 12)    // top, bottom, leading, trailing
    ->width(300)
    ->height(200)
    ->cornerRadius(12)
    ->border(1, '#333333')
    ->shadow('#000000', 4, 2, 2)
    ->opacity(0.9)
    ->textAlignment('center');

// Merge styles
$base = Style::make()->fontSize(14)->foregroundColor('#333');
$highlight = Style::make()->backgroundColor('#ffff00');
$merged = $base->merge($highlight);
```

### Style Methods

| Method | Parameters | Description |
|--------|-----------|-------------|
| `make()` | — | Create new Style |
| `set(StyleProperty, $value)` | enum, mixed | Set any property |
| `get(StyleProperty)` | enum | Get property value |
| `has(StyleProperty)` | enum | Check if property is set |
| `all()` | — | Get all properties |
| `merge(Style)` | Style | Merge another style (right wins) |
| `backgroundColor(hex)` | string | Background color |
| `foregroundColor(hex)` | string | Text/icon color |
| `fontSize(float)` | float | Font size in points |
| `fontWeight(string)` | string | Font weight (`bold`, `light`, etc.) |
| `textAlignment(string)` | string | Text alignment (`center`, `left`, `right`) |
| `padding(float)` | float | Uniform padding |
| `paddingAll(top, bottom, leading, trailing)` | float×4 | Individual padding |
| `width(float)` | float | Fixed width |
| `height(float)` | float | Fixed height |
| `cornerRadius(float)` | float | Corner radius |
| `opacity(float)` | float | Opacity (0.0–1.0) |
| `border(width, color)` | float, string | Border width + color |
| `shadow(color, radius, offsetX, offsetY)` | string, float×3 | Drop shadow |

---

## Style Properties Reference

| Property | Enum | Type | Description |
|----------|------|------|-------------|
| Background Color | `StyleProperty::BackgroundColor` | `string` | Background color (hex) |
| Foreground Color | `StyleProperty::ForegroundColor` | `string` | Text/icon color (hex) |
| Border Color | `StyleProperty::BorderColor` | `string` | Border color (hex) |
| Border Width | `StyleProperty::BorderWidth` | `float` | Border width |
| Corner Radius | `StyleProperty::CornerRadius` | `float` | Corner rounding |
| Opacity | `StyleProperty::Opacity` | `float` | Transparency (0–1) |
| Padding | `StyleProperty::Padding` | `float` | Uniform padding |
| Padding Top | `StyleProperty::PaddingTop` | `float` | Top padding |
| Padding Bottom | `StyleProperty::PaddingBottom` | `float` | Bottom padding |
| Padding Leading | `StyleProperty::PaddingLeading` | `float` | Left padding |
| Padding Trailing | `StyleProperty::PaddingTrailing` | `float` | Right padding |
| Margin | `StyleProperty::Margin` | `float` | Uniform margin |
| Width | `StyleProperty::Width` | `float` | Fixed width |
| Height | `StyleProperty::Height` | `float` | Fixed height |
| Min Width | `StyleProperty::MinWidth` | `float` | Minimum width |
| Min Height | `StyleProperty::MinHeight` | `float` | Minimum height |
| Max Width | `StyleProperty::MaxWidth` | `float` | Maximum width |
| Max Height | `StyleProperty::MaxHeight` | `float` | Maximum height |
| Font Size | `StyleProperty::FontSize` | `float` | Font size |
| Font Weight | `StyleProperty::FontWeight` | `string` | Font weight |
| Font Family | `StyleProperty::FontFamily` | `string` | Font family |
| Text Alignment | `StyleProperty::TextAlignment` | `string` | Text alignment |
| Text Decoration | `StyleProperty::TextDecoration` | `string` | Text decoration |
| Line Spacing | `StyleProperty::LineSpacing` | `float` | Line spacing |
| Letter Spacing | `StyleProperty::LetterSpacing` | `float` | Letter spacing |
| Shadow Color | `StyleProperty::ShadowColor` | `string` | Shadow color |
| Shadow Radius | `StyleProperty::ShadowRadius` | `float` | Shadow blur |
| Shadow Offset X | `StyleProperty::ShadowOffsetX` | `float` | Shadow X offset |
| Shadow Offset Y | `StyleProperty::ShadowOffsetY` | `float` | Shadow Y offset |
| Rotate | `StyleProperty::Rotate` | `float` | Rotation in degrees |
| Scale | `StyleProperty::Scale` | `float` | Uniform scale |
| Translate X | `StyleProperty::TranslateX` | `float` | X translation |
| Translate Y | `StyleProperty::TranslateY` | `float` | Y translation |
| Flex Grow | `StyleProperty::FlexGrow` | `float` | Flex grow factor |
| Flex Shrink | `StyleProperty::FlexShrink` | `float` | Flex shrink factor |
| Gap | `StyleProperty::Gap` | `float` | Gap between children |

---

## Platform Support Matrix

All backends support the full set of style properties and event system:

| Feature | macOS (SwiftUI) | iOS (SwiftUI) | Android (XML) | Android (Compose) | Web (HTML) | Linux (Gtk4) | Windows (WinUI) |
|---------|------------------|---------------|-----------------|--------------------|--------------|----------------|---------------|
| **StyleProperties** |
| BackgroundColor | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| ForegroundColor | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| BorderWidth/BorderColor | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| CornerRadius | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| Padding (all edges) | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
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
| **Event System** |
| Button action (Click) | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| Slider onChange | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| TextInput onChange | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| Toggle onToggle | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| **Widgets** |
| Slider / TextInput / Toggle | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| NavigationView / TabView | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| List | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |

---

## Using the Support Matrix

```php
use Perry\UI\Styling\StyleMatrix;
use Perry\UI\Styling\StyleProperty;

$matrix = new StyleMatrix();

// Check a specific property on a platform
$support = $matrix->getSupport('macos', StyleProperty::CornerRadius);
// PlatformSupport::Wired (fully supported)

// Get all supported properties for a platform
$wired = $matrix->getWiredProperties('macos');

// Check if a platform has full support
$full = $matrix->isFullySupported('macos'); // bool

// Get missing properties
$missing = $matrix->getMissingProperties('android');
```

**Support levels:**

| Level | Description |
|-------|-------------|
| `Wired` | Fully supported, generates native code |
| `Stub` | Stub implementation (tvOS, visionOS, watchOS) |
| `Missing` | Not yet implemented |
| `NotApplicable` | Not applicable for this platform |
