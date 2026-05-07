<?php
require_once __DIR__ . '/../vendor/autoload.php';

use Perry\App;
use Perry\Build\Target;
use Perry\UI\Action;
use Perry\UI\Binding;
use Perry\UI\StateId;
use Perry\UI\Styling\Style;
use Perry\UI\Widget\AppContainer;
use Perry\UI\Widget\Button;
use Perry\UI\Widget\Text;
use Perry\UI\Widget\TextInput;
use Perry\UI\Widget\Toggle;
use Perry\UI\Widget\Slider;
use Perry\UI\Widget\HStack;
use Perry\UI\Widget\VStack;
use Perry\UI\Widget\Spacer;
use Perry\UI\Widget\Image;
use Perry\UI\Widget\ScrollView;

$title    = new Binding('title', 'Perry Showcase');
$name     = new Binding('name', '');
$agree    = new Binding('agree', false);
$brightness = new Binding('brightness', 50.0);
$counter  = new Binding('counter', 0);

$titleStyle = Style::make()
    ->fontSize(28)
    ->fontWeight('bold')
    ->textAlignment('center')
    ->foregroundColor('#1a1a1a');

$inputStyle = Style::make()
    ->border(1, '#ccc')
    ->cornerRadius(8)
    ->padding(12)
    ->fontSize(16)
    ->width(300);

$toggleStyle = Style::make();

$sliderStyle = Style::make()
    ->width(300);

$counterStyle = Style::make()
    ->backgroundColor('#f0f0f0')
    ->cornerRadius(12)
    ->padding(20)
    ->shadow('#000000', 4, 0, 2);

$btnStyle = Style::make()
    ->backgroundColor('#007AFF')
    ->foregroundColor('#ffffff')
    ->fontSize(18)
    ->fontWeight('bold')
    ->cornerRadius(8)
    ->padding(12, 24, 12, 24)
    ->shadow('#000000', 2, 0, 1);

$imageStyle = Style::make()
    ->width(200)
    ->height(120)
    ->cornerRadius(8)
    ->shadow('#000000', 4, 0, 2);

$increment = Action::fromClosure(
    function () use ($counter) {
        $counter++;
    }
);

$decrement = Action::fromClosure(
    function () use ($counter) {
        $counter--;
    }
);

$reset = Action::set($counter, 0);

$submitName = Action::fromClosure(
    function () use ($name, $title) {
        if (!empty($name)) {
            $title = 'Hello, ' . $name . '!';
        }
    }
);

$root = new AppContainer(
    new VStack(
        new Text($title)->style($titleStyle),
        new Text('Counter: ')->style(Style::make()->fontSize(14)->foregroundColor('#666')),
        
        new HStack(
            new Text('Status:'),
            new Spacer(),
            new Text($counter)->style(Style::make()->fontWeight('bold')),
        )->style(Style::make()),
        
        new HStack(
            new Button('-', $decrement)->style(
                Style::make()
                    ->backgroundColor('#FF3B30')
                    ->foregroundColor('#ffffff')
                    ->fontSize(18)
                    ->fontWeight('bold')
                    ->cornerRadius(8)
                    ->padding(8, 16, 8, 16)
                    ->width(48)
            ),
            new Button('+', $increment)->style(
                Style::make()
                    ->backgroundColor('#34C759')
                    ->foregroundColor('#ffffff')
                    ->fontSize(18)
                    ->fontWeight('bold')
                    ->cornerRadius(8)
                    ->padding(8, 16, 8, 16)
                    ->width(48)
            ),
            new Button('Reset', $reset)->style(
                Style::make()
                    ->backgroundColor('#FF9500')
                    ->foregroundColor('#ffffff')
                    ->fontSize(14)
                    ->fontWeight('bold')
                    ->cornerRadius(8)
                    ->padding(8, 16, 8, 16)
            ),
        )->style(Style::make()->padding(4)),
        
        new HStack(
            new Text('Agree to terms:'),
            new Spacer(),
            new Toggle('Agree to terms:', $agree, Action::fromClosure(
                function () use ($agree, $title) {
                    $title = $agree ? 'Agreed!' : 'Perry Showcase';
                }
            ))->style($toggleStyle),
        ),
        
        new VStack(
            new Text('Brightness: ')->style(Style::make()->fontSize(14)),
            new Slider($brightness, 0, 100, onChange: Action::fromClosure(
                function () use ($title, $brightness) {
                    $title = 'Brightness: ' . $brightness;
                }
            ))->style($sliderStyle),
        ),
        
        new Spacer(),
        
        new ScrollView(
            new VStack(
                new Text('Scrollable content')->style(Style::make()->fontSize(14)->foregroundColor('#666')),
                new Text('Line 1'),
                new Text('Line 2'),
                new Text('Line 3'),
                new Text('Line 4'),
                new Text('Line 5'),
            )->style(Style::make()->padding(16))
        )->style(Style::make()->height(100))
    ),
    null, null, // windowWidth, windowHeight
    $title, $name, $agree, $brightness, $counter // extra bindings
)->style(
    Style::make()
        ->padding(24)
        ->backgroundColor('#fafa')
);

$app = new App();
$app->setRoot($root);

$target = $argv[1] ?? 'macos';
$build  = in_array('--build', $argv);

echo "=== Perry Showcase ===\n";
echo "Target: {$target}\n\n";

if ($build) {
    $compiler = new \Perry\Build\Compiler(Target::fromString($target), 'build');
    $result   = $compiler->compile($root, 'showcase');

    if ($result->success) {
        echo "✓ Build successful!\n";
        echo "  Output: {$result->outputFile}\n";
        echo "  Source: {$result->sourceFile}\n";
    } else {
        echo "✗ Build failed:\n";
        echo $result->error . "\n";
    }
} else {
    $app->setTarget(Target::fromString($target));
    echo $app->generateForTarget();
}
