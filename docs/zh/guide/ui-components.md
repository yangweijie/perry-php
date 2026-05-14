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

### 方法

| 方法 | 返回值 | 说明 |
|--------|---------|-------------|
| `content()` | `string` | 文本内容（绑定时返回空字符串） |
| `getBinding()` | `?Binding` | 绑定的 `Binding` 对象，静态文本为 null |

### Binding 的工作原理

当 `Text` 微件收到 `Binding` 时，`AppContainer::bindings()` 会自动收集它。后端会生成对应的 `@State`（Swift）、`const state = {}`（JS）或 `mutableStateOf`（Kotlin）。

**生成的代码：**
```swift
// SwiftUI — 静态
Text("Hello, World!")

// SwiftUI — 响应式（binding 变成 @State 变量）
Text(display)
```

**完整示例——实时时钟显示：**
```php
use Perry\App;
use Perry\UI\AppContainer;
use Perry\UI\Binding;
use Perry\UI\Widget\Text;
use Perry\UI\Widget\VStack;

$time = new Binding('time', '00:00:00');
$date = new Binding('date', '2024-01-01');

$app = new App();
$app->setRoot(
    new AppContainer(
        new VStack(
            (new Text($time))->style(
                \Perry\UI\Styling\Style::make()->fontSize(32)->textAlignment('center')
            ),
            (new Text($date))->style(
                \Perry\UI\Styling\Style::make()->fontSize(16)->foregroundColor('#888')
            ),
        ),
        320, 200,
        $date,
    )
);

echo $app->generateCode('html');
// 生成：const state = { time: "00:00:00", date: "2024-01-01" };
//       function render() { el_time.textContent = state.time; ... }
```

---

## Button

可点击的按钮，带有标签和可选动作。

```php
use Perry\UI\Action;
use Perry\UI\Binding;
use Perry\UI\Widget\Button;
use Perry\UI\Styling\Style;

$display = new Binding('display', '0');

// 1. 静态按钮 — 无动作
$ok = new Button('OK');

// 2. 简单动作 — 设置绑定值
$setZero = new Button('Reset', Action::set($display, '0'));

// 3. Append 动作 — 向绑定追加字符串
$addDigit = new Button('1', Action::append($display, '1'));

// 4. 闭包动作 — 完整 PHP 逻辑 → 跨平台代码
$toggleSign = new Button('±', Action::fromClosure(function () use ($display) {
    if ($display[0] === '-') {
        $display = substr($display, 1);
    } else {
        $display = '-' . $display;
    }
}));

// 5. 带外部绑定的闭包
$button = new Button('×', Action::fromClosure(
    function () use ($display, $operand1, $operation) {
        $operand1 = floatval($display);
        $operation = '×';
        $display .= '×';
    },
    compact('operand1', 'operation')
));

// 样式化按钮
$styled = (new Button('Submit', $toggleSign))
    ->style(Style::make()
        ->backgroundColor('#007AFF')
        ->foregroundColor('#ffffff')
        ->fontSize(18)
        ->padding(12)
        ->cornerRadius(8)
    );
```

**构造函数：**

| 参数 | 类型 | 默认值 | 说明 |
|-----------|------|---------|-------------|
| `$label` | `string` | — | 按钮文字 |
| `$action` | `Action\|\Closure\|null` | `null` | 点击处理器 |

### 方法

| 方法 | 返回值 | 说明 |
|--------|---------|-------------|
| `label()` | `string` | 按钮标签文字 |
| `getAction()` | `?Action` | 动作对象 |

**生成的代码（Swift）：**
```swift
// 静态按钮
Button(action: {}) {
    Text("OK")
}

// 带闭包动作
Button(action: { display = "0" }) {
    Text("Reset")
}
```

**生成的代码（HTML）：**
```html
<!-- 静态 -->
<button>OK</button>

<!-- 带动作 -->
<button onclick="action_0()">1</button>
<script>
function action_0() {
    state.display = state.display + "1"
    render();
}
</script>
```

---

## VStack

垂直布局——从上到下排列子元素。

```php
use Perry\UI\Widget\VStack;
use Perry\UI\Widget\Text;
use Perry\UI\Widget\Button;

// 将子元素作为构造函数参数传入
$layout = new VStack(
    new Text('Header'),
    new Text('Body content goes here'),
    new Text('Footer'),
);
```

**构造函数：**

| 参数 | 类型 | 说明 |
|-----------|------|-------------|
| `...$children` | `Widget` | 子微件（可变参数） |

间距通过 `Style::padding()` 控制——`padding` 值在 SwiftUI 中变为 `spacing`：

```php
use Perry\UI\Styling\Style;

$spaced = (new VStack(
    new Text('A'),
    new Text('B'),
    new Text('C'),
))->style(Style::make()->padding(16));  // 子元素间间距 16px
```

**生成的代码：**
```swift
// SwiftUI
VStack(spacing: 16) {
    Text("A")
    Text("B")
    Text("C")
}
```

```html
<!-- HTML -->
<div class="vstack" style="padding: 16px">
    <span>A</span>
    <span>B</span>
    <span>C</span>
</div>
```

---

## HStack

水平布局——从左到右排列子元素。

```php
use Perry\UI\Widget\HStack;
use Perry\UI\Widget\Text;
use Perry\UI\Widget\Spacer;

$toolbar = new HStack(
    (new Button('Bold'))->style(Style::make()->fontSize(14)),
    (new Button('Italic'))->style(Style::make()->fontSize(14)),
    (new Button('Underline'))->style(Style::make()->fontSize(14)),
);

// Spacer 将 "Menu" 推到右侧
$navbar = new HStack(
    new Text('Logo'),
    new Spacer(),
    new Text('Menu'),
);
```

**生成的代码：**
```swift
// SwiftUI
HStack(spacing: 8) {
    Text("Logo")
    Spacer()
    Text("Menu")
}
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

**构造函数：** 无参数。

---

## Image

从路径或资源名称显示图像。

```php
use Perry\UI\Widget\Image;

$logo = new Image('logo.png');
$avatar = new Image('avatar');
```

**构造函数：**

| 参数 | 类型 | 说明 |
|-----------|------|-------------|
| `$source` | `string` | 图片路径或资源名称 |

**生成的代码：**
```swift
// SwiftUI
Image("logo.png")
```

```html
<!-- HTML -->
<img src="logo.png" alt="">
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

**生成的代码：**
```swift
// SwiftUI
ScrollView {
    VStack(spacing: 8) {
        Text("Item 1")
        Text("Item 2")
        Text("Item 3")
    }
}
```

```html
<!-- HTML -->
<div style="overflow:auto;max-height:100vh">
    <div class="vstack">...</div>
</div>
```

---

## TextInput

带有占位符和可选 onChange 动作的文本输入框。

```php
use Perry\UI\State;
use Perry\UI\Widget\TextInput;

$state = new State();
$name = $state->create('');  // 初始值：空字符串

$input = new TextInput($name, 'Enter your name...');
```

**构造函数：**

| 参数 | 类型 | 默认值 | 说明 |
|-----------|------|---------|-------------|
| `$value` | `StateId` | — | 绑定到输入框的状态变量 |
| `$placeholder` | `string` | `''` | 占位符文本 |

**生成的代码：**
```swift
// SwiftUI
TextField("Enter your name...", text: .constant(""))
```

```html
<!-- HTML -->
<input type="text" placeholder="Enter your name...">
```

---

## Toggle

带标签的开关。

```php
use Perry\UI\State;
use Perry\UI\Widget\Toggle;

$state = new State();
$darkMode = $state->create(false);

$toggle = new Toggle($darkMode, 'Dark Mode');
```

**构造函数：**

| 参数 | 类型 | 默认值 | 说明 |
|-----------|------|---------|-------------|
| `$isOn` | `StateId` | — | 绑定到开关的状态变量 |
| `$label` | `string` | `''` | 开关标签 |

**生成的代码：**
```swift
// SwiftUI
Toggle("Dark Mode", isOn: .constant(false))
```

```html
<!-- HTML -->
<div class="toggle">
    <input type="checkbox">
    <span>Dark Mode</span>
</div>
```

---

## AppContainer

根应用容器。包裹微件树，设置窗口尺寸，自动收集所有 `Binding` 对象。

```php
use Perry\UI\AppContainer;
use Perry\UI\Binding;
use Perry\UI\Widget\VStack;
use Perry\UI\Widget\Text;
use Perry\UI\Widget\Button;
use Perry\UI\Action;
use Perry\UI\Styling\Style;

$count = new Binding('count', 0);
$label = new Binding('label', 'Clicks: 0');

$app = new AppContainer(
    // 1. 内容微件树
    new VStack(
        (new Text($label))->style(Style::make()->fontSize(24)),
        (new Button('Increment', function () use ($count, $label) {
            $count += 1;
            $label = 'Clicks: ' . strval($count);
        }))->style(Style::make()->backgroundColor('#007AFF')->foregroundColor('#fff')),
    ),
    // 2. 窗口尺寸（可选）
    320,
    480,
    // 3. 未附加到 Text 微件的额外绑定
    $count,
);

$app2 = new App();
$app2->setRoot($app);
echo $app2->generateCode('html');
```

**构造函数：**

| 参数 | 类型 | 默认值 | 说明 |
|-----------|------|---------|-------------|
| `$content` | `Widget` | — | 根微件树 |
| `$windowWidth` | `?int` | `null` | 窗口宽度（像素） |
| `$windowHeight` | `?int` | `null` | 窗口高度（像素） |
| `...$extraBindings` | `Binding` | — | 额外的状态绑定 |

**方法：**

| 方法 | 返回值 | 说明 |
|--------|---------|-------------|
| `content()` | `Widget` | 根微件 |
| `windowWidth()` | `?int` | 窗口宽度 |
| `windowHeight()` | `?int` | 窗口高度 |
| `bindings()` | `Binding[]` | 所有收集的 Binding |

**Binding 收集逻辑：** `AppContainer` 遍历整个微件树，从 `Text` 微件中收集所有 `Binding`。通过 `...$extraBindings` 传入的绑定也会包含在内。

---

## WebView

在原生应用内嵌入完整的 HTML 页面。使用 WKWebView（macOS/iOS）、WebView2（Windows）、GtkWebView（Linux）、AndroidView（Android）或 iframe（Web）。

```php
use Perry\UI\Widget\WebView;

// HTML 由 HtmlBackend 生成，在构建时嵌入
$webview = new WebView($fullHtmlContent);
```

**使用模式（来自 Pry 示例）：**
```php
// 1. 通过 HtmlBackend 生成完整 HTML
$webApp = new App(Target::fromString('web'));
$webApp->setRoot($widgetTree);
$webHtml = $webApp->generateForTarget();

// 2. 用 WebView 包裹并编译
$root = new AppContainer(
    new WebView($webHtml),
    800, 700,
);
$compiler = new Compiler(Target::fromString('windows'));
$result = $compiler->compile($root, 'pry');
```

**Windows 说明：** 需要 [WebView2 Runtime](/zh/guide/build-system.html#windows-requirements)。

---

## Slider

带最小值、最大值、步长和可选 onChange 动作的滑块控件。

```php
use Perry\UI\Binding;
use Perry\UI\Widget\Slider;

$value = new Binding('value', 50.0);
$slider = new Slider(0, 100, $value, step: 1);
```

---

## Checkbox

带标签和可选 onChange 动作的复选框。

```php
use Perry\UI\Binding;
use Perry\UI\Widget\Checkbox;

$checked = new Binding('checked', false);
$checkbox = new Checkbox('Enable feature', $checked);
```

---

## RadioButton

带分组和值选择的单选按钮。

```php
use Perry\UI\Binding;
use Perry\UI\Widget\RadioButton;

$selected = new Binding('color', 'red');
$radio = new RadioButton('Red', 'colors', 'red', $selected);
```

---

## Progress

带可选绑定的进度条。

```php
use Perry\UI\Binding;
use Perry\UI\Widget\Progress;

$progress = new Binding('progress', 0.5);
$bar = new Progress($progress);
```

---

## TabView

基于标签页的导航容器。

```php
use Perry\UI\Widget\TabView;
use Perry\UI\Widget\VStack;
use Perry\UI\Widget\Text;

$tabs = new TabView(
    new VStack(new Text('Tab 1 Content')),
    new VStack(new Text('Tab 2 Content')),
);
```
