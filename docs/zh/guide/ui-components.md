# UI 组件

所有微件都继承自 `Perry\UI\Widget`。每个微件具有：
- **构造函数**接受其特定的参数
- **`kind()`** 方法返回 `WidgetKind` 枚举
- **`style()`** 方法（继承自 `Widget`）用于 fluent 样式链式调用
- 唯一的 **`handle()`**（自动生成的 `WidgetHandle`）

---

## Text

显示静态文本或响应式绑定数据。

```php
use Perry\UI\Binding;
use Perry\UI\Widget\Text;

// 静态文本
$title = new Text('Hello, World!');

// 响应式文本 — 状态变化时自动更新
$display = new Binding('display', '0');
$counter = new Text($display);
```

**构造函数：**

| 参数 | 类型 | 说明 |
|-----------|------|-------------|
| `$content` | `string\|Binding` | 静态字符串或用于响应式显示的 Binding |

### Binding 的工作原理

当 `Text` 微件收到 `Binding` 时，`AppContainer::bindings()` 会自动收集它。后端会生成对应的 `@State`（Swift）、`const state = {}`（JS）或 `mutableStateOf`（Kotlin）。

**生成的代码：**
```swift
// SwiftUI — 静态
Text("Hello, World!")

// SwiftUI — 响应式（binding 变成 @State 变量）
Text(display)
```

---

## Button

可点击的按钮，带有标签和可选动作。

```php
use Perry\UI\Action;
use Perry\UI\Binding;
use Perry\UI\Widget\Button;

$display = new Binding('display', '0');

// 1. 静态按钮 — 无动作
$ok = new Button('OK');

// 2. 简单动作 — 设置绑定值
$setZero = new Button('Reset', Action::set($display, '0'));

// 3. 闭包动作 — 完整 PHP 逻辑 → 跨平台代码
$toggleSign = new Button('±', Action::fromClosure(function () use ($display) {
    if ($display[0] === '-') {
        $display = substr($display, 1);
    } else {
        $display = '-' . $display;
    }
}));
```

**构造函数：**

| 参数 | 类型 | 默认值 | 说明 |
|-----------|------|---------|-------------|
| `$label` | `string` | — | 按钮文字 |
| `$action` | `Action\|\Closure\|null` | `null` | 点击处理器 |

---

## VStack

垂直布局——从上到下排列子元素。

```php
use Perry\UI\Widget\VStack;
use Perry\UI\Widget\Text;

$layout = new VStack(
    new Text('Header'),
    new Text('Body'),
    new Text('Footer'),
);
```

间距通过 `Style::padding()` 控制——`padding` 值在 SwiftUI 中变为 `spacing`。

---

## HStack

水平布局——从左到右排列子元素。

```php
use Perry\UI\Widget\HStack;
use Perry\UI\Widget\Text;
use Perry\UI\Widget\Spacer;

$navbar = new HStack(
    new Text('Logo'),
    new Spacer(),  // 将 "Menu" 推到右侧
    new Text('Menu'),
);
```

---

## Spacer

弹性空白，可扩展以填充可用区域。

```php
use Perry\UI\Widget\Spacer;

// 将元素推开
$row = new HStack(
    new Text('Left'),
    new Spacer(),
    new Text('Right'),
);
```

---

## Image

从路径或资源名称显示图像。

```php
use Perry\UI\Widget\Image;

$logo = new Image('logo.png');
$avatar = new Image('avatar');
```

---

## ScrollView

可滚动的容器，用于超出视口的内容。

```php
use Perry\UI\Widget\ScrollView;
use Perry\UI\Widget\VStack;
use Perry\UI\Widget\Text;

$list = new ScrollView(
    new VStack(
        new Text('Item 1'),
        new Text('Item 2'),
        new Text('Item 3'),
    )
);
```

---

## TextInput

带有占位符和可选 onChange 动作的文本输入框。

```php
use Perry\UI\Binding;
use Perry\UI\Widget\TextInput;

$name = new Binding('name', '');
$input = new TextInput($name, 'Enter your name...');
```

---

## Toggle

带标签的开关。

```php
use Perry\UI\Binding;
use Perry\UI\Widget\Toggle;

$darkMode = new Binding('darkMode', false);
$toggle = new Toggle($darkMode, 'Dark Mode');
```

---

## AppContainer

根应用容器。包裹微件树，设置窗口尺寸，自动收集所有 `Binding` 对象。

```php
use Perry\UI\AppContainer;
use Perry\UI\Binding;
use Perry\UI\Widget\VStack;
use Perry\UI\Widget\Text;

$count = new Binding('count', 0);

$app = new AppContainer(
    new VStack(new Text('Hello')),
    320, 480,
    $count,
);
```

---

## WebView

在原生应用内嵌入完整的 HTML 页面。使用 WKWebView（macOS/iOS）、WebView2（Windows）、GtkWebView（Linux）、AndroidView（Android）或 iframe（Web）。

---

## 更多微件

Perry 还支持 **Slider**、**Checkbox**、**RadioButton**、**Progress**、**TabView**、**NavigationView**、**ListWidget** 等微件。详见英文版文档或 API 参考。
