# 计算器

功能完整的计算器示例，含 7 种动作类型、状态管理和自定义样式。支持 macOS、Windows、Web、Android 和 Linux。

```bash
# 生成 HTML
php examples/calculator.php web > calculator.html

# 构建 macOS 应用
php examples/calculator.php macos --build
# 输出：build/Calculator.app
```

## 代码

```php
<?php

use Perry\App;
use Perry\Build\Target;
use Perry\UI\Action;
use Perry\UI\AppContainer;
use Perry\UI\Binding;
use Perry\UI\Styling\Style;
use Perry\UI\Widget\Button;
use Perry\UI\Widget\HStack;
use Perry\UI\Widget\Text;
use Perry\UI\Widget\VStack;

// 状态
$display = new Binding('display', '0');
$result = new Binding('result', '');
$operand1 = new Binding('operand1', 0.0);
$operand2 = new Binding('operand2', 0.0);
$operation = new Binding('operation', '');
$isTyping = new Binding('isTyping', false);
$typed = new Binding('typed', '');

// 样式
$numberBtn = Style::make()->fontSize(24)->backgroundColor('#f0f0f0')->padding(20)->cornerRadius(8);
$opBtn = Style::make()->fontSize(24)->backgroundColor('#FF9500')->foregroundColor('#fff')->padding(20)->cornerRadius(8);

// 数字按钮工厂
function numBtn(string $d, Binding $display, Binding $op2, Binding $typing, Binding $typed, Binding $op): Button {
    return (new Button($d, Action::fromClosure(
        function () use ($d, $display, $op2, $typing, $typed, $op) {
            if ($typing) {
                $typed .= $d;
                $display .= $d;
            } else {
                $typed = $d;
                $display = $d;
                $typing = true;
            }
            $op2 = floatval($typed);
        },
        compact('d')
    )))->style(Style::make()->fontSize(24)->backgroundColor('#f0f0f0')->padding(20)->cornerRadius(8));
}

// 构建
$app = new App(Target::fromString('macos'));
$app->setRoot(
    new AppContainer(
        new VStack(
            (new Text($result))->style(Style::make()->fontSize(16)->foregroundColor('#888')),
            (new Text($display))->style(Style::make()->fontSize(32)->padding(16)),
            // ...（按钮行）
        ),
        320, 480,
        $operand1, $operand2, $operation, $isTyping, $typed
    )
);

echo $app->generateForTarget();
```
