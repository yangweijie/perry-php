# 简单计数器

展示核心模式的最小示例：Binding、闭包动作和 HStack/VStack 布局。

```bash
# 生成 HTML
php examples/counter.php > counter.html

# 生成 SwiftUI
php examples/counter.php > Counter.swift
```

## 代码

```php
<?php

use Perry\App;
use Perry\UI\Action;
use Perry\UI\Binding;
use Perry\UI\Styling\Style;
use Perry\UI\Widget\Button;
use Perry\UI\Widget\HStack;
use Perry\UI\Widget\Text;
use Perry\UI\Widget\VStack;

$count = new Binding('count', 0);

$app = new App();
$app->setRoot(
    new VStack(
        (new Text($count))->style(Style::make()->fontSize(48)),
        new HStack(
            (new Button('-', Action::fromClosure(function () use ($count) {
                $count -= 1;
            })))->style(Style::make()->fontSize(24)->padding(16)),
            (new Button('+', Action::fromClosure(function () use ($count) {
                $count += 1;
            })))->style(Style::make()->fontSize(24)->padding(16)),
        ),
    )
);

echo $app->generateCode('html');
```

## 生成的输出（HTML）

```html
<div class="vstack">
    <span id="el_count">0</span>
    <div class="hstack">
        <button onclick="action_0()">-</button>
        <button onclick="action_1()">+</button>
    </div>
</div>
<script>
let state = { count: 0 };
function action_0() { state.count = state.count - 1; render(); }
function action_1() { state.count = state.count + 1; render(); }
function render() { el_count.textContent = state.count; }
render();
</script>
```
