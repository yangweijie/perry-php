# 待办列表

演示使用 append 模式的字符串绑定——添加和清除待办事项。

## 代码

```php
<?php

use Perry\App;
use Perry\UI\Action;
use Perry\UI\Binding;
use Perry\UI\Styling\Style;
use Perry\UI\Widget\Button;
use Perry\UI\Widget\HStack;
use Perry\UI\Widget\ScrollView;
use Perry\UI\Widget\Text;
use Perry\UI\Widget\VStack;
use Perry\UI\Widget\AppContainer;

$items = new Binding('items', 'Buy milk');
$newItem = new Binding('newItem', '');

$app = new App();
$app->setRoot(
    new AppContainer(
        new VStack(
            (new Text($items))->style(Style::make()->fontSize(16)),
            new HStack(
                (new Button('Add', Action::fromClosure(function () use ($items, $newItem) {
                    $items .= "\n" . $newItem;
                    $newItem = '';
                })))->style(Style::make()->backgroundColor('#007AFF')->foregroundColor('#fff')),
                (new Button('Clear', Action::fromClosure(function () use ($items) {
                    $items = '';
                })))->style(Style::make()->backgroundColor('#ff3b30')->foregroundColor('#fff')),
            ),
        ),
        320, 480,
        $newItem
    )
);

echo $app->generateCode('swiftui');
```
