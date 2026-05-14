<?php

declare(strict_types=1);

if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require __DIR__ . '/../vendor/autoload.php';
} else {
    spl_autoload_register(function (string $class) {
        $prefix = 'Perry\\';
        $baseDir = __DIR__ . '/../src/';
        if (strncmp($prefix, $class, strlen($prefix)) !== 0) return;
        $relativeClass = substr($class, strlen($prefix));
        $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
        if (file_exists($file)) require $file;
    });
}

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

$target = $argv[1] ?? 'html';
echo $app->generateCode($target);
