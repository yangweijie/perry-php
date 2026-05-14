# 动作

动作定义了用户与微件交互时发生的事情。Perry 支持多种动作类型，包括强大的闭包转译系统，可将 PHP 闭包转换为原生代码。

---

## 简单动作

预构建的常见操作动作类型：

```php
use Perry\UI\Action;
use Perry\UI\Binding;

$display = new Binding('display', '0');

// SetValue — 赋值
$action = Action::set($display, '42');
$action = Action::set($display, true);     // bool
$action = Action::set($display, 3.14);    // float

// Append — 字符串拼接
$action = Action::append($display, '1');   // display += "1"

// Clear — 重置为初始值
$action = Action::clear($display);         // display = "0"

// Custom — 原始平台特定代码（原样传递）
$action = Action::custom('display.text = ""');
```

## 微件动作

交互式微件支持 `Action` 作为事件处理：

```php
use Perry\UI\Action;
use Perry\UI\Binding;
use Perry\UI\Widget\Button;
use Perry\UI\Widget\Slider;
use Perry\UI\Widget\TextInput;
use Perry\UI\Widget\Toggle;

$display = new Binding('display', '0');

// Button — 点击时触发
$btn = new Button('7', Action::append($display, '7'));

// Slider — 值变化时触发
$slider = new Slider(0, 100, step: 1,
    onChange: Action::set($display, '50'));

// TextInput — 文本变化时触发
$input = new TextInput($display,
    onChange: Action::set($display, ''));

// Toggle — 切换时触发
$toggle = new Toggle(true,
    onToggle: Action::set($display, 'toggled'));
```

### 支持的事件

| 微件 | 事件属性 | 说明 |
|-------|----------------|-------------|
| `Button` | `action`（构造函数） | 点击/触摸时触发 |
| `Slider` | `onChange` | 值变化时触发 |
| `TextInput` | `onChange` | 文本变化时触发 |
| `Toggle` | `onToggle` | 切换状态变化时触发 |
| `Checkbox` | `onChange` | 选中状态变化时触发 |

### 按微件分类的动作类型

| ActionType | Button | Slider | TextInput | Toggle |
|------------|--------|--------|-----------|--------|
| `SetValue` | ✅ | ✅ | ✅ | ✅ |
| `Append` | ✅ | — | — | — |
| `Clear` | ✅ | — | — | — |
| `Custom` | ✅ | ✅ | ✅ | ✅ |
| `Closure` | ✅ | ✅ | ✅ | ✅ |

---

## 闭包动作（AST 转译）

最强大的动作类型。编写 PHP 闭包，将其解析为 AST 并跨平台编译。

```php
use Perry\UI\Action;
use Perry\UI\Binding;

$display = new Binding('display', '0');
$operand1 = new Binding('operand1', 0.0);
$operation = new Binding('operation', '');

$action = Action::fromClosure(
    function () use ($display, $operand1, $operation) {
        $operand1 = floatval($display);
        $operation = '+';
        $display .= '+';
    }
);
```

### 工作原理

```
PHP 闭包
    ↓ nikic/php-parser
PHP AST
    ↓ Perry\IR\AstToIrVisitor
Perry IR（54 个节点类型）
    ↓ Perry\Generator\{Swift,JavaScript,Kotlin,Dart,CSharp}Generator
目标语言代码
```

### 闭包绑定（参数替换）

```php
// 在定义时将外部值传入闭包
$action = Action::fromClosure(
    function () use ($display, $digit) {
        $display .= $digit;
    },
    ['digit' => '5']  // 在生成的代码中 $digit 被替换为 "5"
);

// 生成的 Swift：display = display + "5"
// 生成的 JS：   state.display = state.display + "5"
```

### 嵌套闭包模式

用于参数化按钮工厂：

```php
function numBtn(string $digit, Binding $display): Button {
    return new Button($digit, Action::fromClosure(
        function () use ($digit, $display) {
            $display .= $digit;
        },
        compact('digit')
    ));
}

$row = new HStack(
    numBtn('1', $display),
    numBtn('2', $display),
    numBtn('3', $display),
);
```

---

## 支持的 PHP 特性

| 特性 | Swift | JavaScript | Kotlin | Dart | C# |
|---------|-------|------------|--------|------|-----|
| 变量 | `var x` | `let x` | `var x` | `var x` | `var x` |
| 状态变量 | `x = ...` | `state.x = ...` | `x.value = ...` | `x.value = ...` | `x = ...` |
| If/else | `if {} else {}` | `if {} else {}` | `if {} else {}` | `if {} else {}` | `if {} else {}` |
| While | `while {}` | `while {}` | `while {}` | `while {}` | `while {}` |
| For | `for {}` | `for {}` | `for {}` | `for {}` | `for {}` |
| Foreach | `for x in y` | `for x of y` | `for x in y` | `for x in y` | `foreach x in y` |
| 三元运算 | `c ? a : b` | `c ? a : b` | `if (c) a else b` | `c ? a : b` | `c ? a : b` |
| Switch | `switch {}` | `switch {}` | `when {}` | `switch {}` | `switch {}` |
| Match | `match {}` | `switch+return` | `when->` | `switch+IIFE` | `switch expr` |
| Try/catch | `do{}catch{}` | `try{}catch{}` | `try{}catch{}` | `try{}catch{}` | `try{}catch{}` |
| Throw | `throw` | `throw` | `throw` | `throw` | `throw` |
| 类型转换 | `Int()` | `parseInt()` | `.toInt()` | `int.parse()` | `(int)` |

---

## PHP 函数映射

Perry 将 97+ 个 PHP 内置函数映射到所有 5 种目标语言。完整表格请查看[英文版页面](/guide/actions.html)。
