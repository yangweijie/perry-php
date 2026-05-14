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
use Perry\Build\Target;
use Perry\UI\Action;
use Perry\UI\Binding;
use Perry\UI\State;
use Perry\UI\Styling\Style;
use Perry\UI\Widget\AppContainer;
use Perry\UI\Widget\Button;
use Perry\UI\Widget\Checkbox;
use Perry\UI\Widget\HStack;
use Perry\UI\Widget\Image;
use Perry\UI\Widget\ListWidget;
use Perry\UI\Widget\Progress;
use Perry\UI\Widget\RadioButton;
use Perry\UI\Widget\ScrollView;
use Perry\UI\Widget\Slider;
use Perry\UI\Widget\Spacer;
use Perry\UI\Widget\TabView;
use Perry\UI\Widget\Text;
use Perry\UI\Widget\TextInput;
use Perry\UI\Widget\Toggle;
use Perry\UI\Widget\VStack;

// ============================================================
// 1. State — Bindings & StateId
// ============================================================
$state = new State();
$nameInput = $state->create('');

$greeting  = new Binding('greeting', 'Hello, Perry!');
$counter   = new Binding('counter', 0);
$isDark    = new Binding('isDark', false);
$opacity   = new Binding('opacity', 1.0);
$progress  = new Binding('progress', 0.5);
$color     = new Binding('color', 'red');
$checked   = new Binding('checked', false);
$items     = new Binding('items', 'Perry PHP');

// ============================================================
// 2. Styles — demonstrating all style properties
// ============================================================
$sectionTitle = Style::make()
    ->fontSize(20)
    ->fontWeight('bold')
    ->foregroundColor('#333')
    ->padding(8);

$cardStyle = Style::make()
    ->backgroundColor('#f8f9fa')
    ->cornerRadius(12)
    ->padding(16)
    ->shadow('#000000', 4, 0, 2);

$primaryBtn = Style::make()
    ->backgroundColor('#007AFF')
    ->foregroundColor('#ffffff')
    ->fontSize(16)
    ->fontWeight('bold')
    ->cornerRadius(8)
    ->padding(12);

$dangerBtn = Style::make()
    ->backgroundColor('#FF3B30')
    ->foregroundColor('#ffffff')
    ->fontSize(16)
    ->cornerRadius(8)
    ->padding(12);

$successBtn = Style::make()
    ->backgroundColor('#34C759')
    ->foregroundColor('#ffffff')
    ->fontSize(16)
    ->cornerRadius(8)
    ->padding(12);

$inputStyle = Style::make()
    ->width(280)
    ->padding(10)
    ->border(1, '#ccc')
    ->cornerRadius(6)
    ->fontSize(14);

$counterText = Style::make()
    ->fontSize(36)
    ->fontWeight('bold')
    ->textAlignment('center')
    ->foregroundColor('#007AFF');

// ============================================================
// 3. Actions — all action types
// ============================================================

// 3a. Simple actions
$increment = Action::fromClosure(function () use ($counter) {
    $counter++;
});
$decrement = Action::fromClosure(function () use ($counter) {
    $counter--;
});
$reset = Action::set($counter, 0);
$greet = Action::append($greeting, '!');

// 3b. Closure action with conditional logic
$toggleDark = Action::fromClosure(function () use ($isDark, $greeting) {
    if ($isDark) {
        $greeting = 'Dark Mode ON';
    } else {
        $greeting = 'Light Mode';
    }
});

// 3c. Append action
$addItem = Action::append($items, "\n• item");

// 3d. Progress action
$randomProgress = Action::fromClosure(function () use ($progress) {
    $progress = floatval(rand(0, 100)) / 100.0;
});

// 3e. Checkbox/Toggle actions
$toggleCheck = Action::fromClosure(function () use ($checked, $greeting) {
    $greeting = $checked ? 'Checked!' : 'Unchecked';
});

$colorChanged = Action::fromClosure(function () use ($color, $greeting) {
    $greeting = 'Selected: ' . $color;
});

// ============================================================
// 4. Widget Tree — demonstrating all 16 widgets
// ============================================================

$root = new VStack(
    // ========== Section: Text & Styling ==========
    new Text(' Perry PHP Feature Demo')->style(
        Style::make()
            ->fontSize(24)
            ->fontWeight('bold')
            ->textAlignment('center')
            ->padding(16)
            ->foregroundColor('#1a1a1a')
    ),

    new HStack(
        (new Text($greeting))->style(
            Style::make()->fontSize(18)->foregroundColor('#555')
        ),
        new Spacer(),
        new Text('v1.0')->style(
            Style::make()->fontSize(12)->foregroundColor('#999')
        ),
    ),

    new Spacer(),

    // ========== Section 1: Counter (Binding + Closure + set) ==========
    new Text(' Counter')->style($sectionTitle),
    (new VStack(
        new HStack(
            new Spacer(),
            (new Text($counter))->style($counterText),
            new Spacer(),
        ),
        new HStack(
            new Button('-', $decrement)->style($dangerBtn),
            new Button('Reset', $reset)->style(
                Style::make()->backgroundColor('#FF9500')->foregroundColor('#fff')
                    ->fontSize(14)->cornerRadius(8)->padding(12)
            ),
            new Button('+', $increment)->style($successBtn),
        ),
    ))->style($cardStyle),

    // ========== Section 2: TextInput + Append Action ==========
    new Text(' Text Input & Items')->style($sectionTitle),
    (new VStack(
        new TextInput($nameInput, 'Type and click Add...'),
        new HStack(
            new Button('Add', $addItem)->style($primaryBtn),
            new Button('Clear Items', Action::clear($items))->style($dangerBtn),
        ),
        (new Text($items))->style(Style::make()->fontSize(14)->padding(8)),
    ))->style($cardStyle),

    // ========== Section 3: Toggle + Checkbox + RadioButton ==========
    new Text(' Toggle, Checkbox & Radio')->style($sectionTitle),
    (new VStack(
        new HStack(
            new Toggle('Dark Mode', $isDark, $toggleDark)->style(
                Style::make()->padding(4)
            ),
        ),
        new HStack(
            new Checkbox('Enable feature', $checked, $toggleCheck),
        ),
        new HStack(
            new RadioButton('Red', 'colorGroup', 'red', $color, $colorChanged),
            new RadioButton('Green', 'colorGroup', 'green', $color, $colorChanged),
            new RadioButton('Blue', 'colorGroup', 'blue', $color, $colorChanged),
        ),
    ))->style($cardStyle),

    // ========== Section 4: Slider + Progress ==========
    new Text(' Slider & Progress')->style($sectionTitle),
    (new VStack(
        new HStack(
            new Text('Opacity: '),
            new Spacer(),
            (new Text($opacity))->style(Style::make()->fontWeight('bold')),
        ),
        new Slider($opacity, 0, 100, step: 1, onChange: Action::fromClosure(
            function () use ($opacity, $greeting) {
                $greeting = 'Opacity: ' . $opacity . '%';
            }
        )),
        new HStack(
            (new Progress($progress))->style(Style::make()->width(250)),
            new Button('Random', $randomProgress)->style($primaryBtn),
        ),
    ))->style($cardStyle),

    // ========== Section 5: TabView ==========
    new Text(' Tab View (native only, web shows all tabs)')->style($sectionTitle),
    (new TabView(
        new VStack(
            new Text('Tab 1: Welcome'),
            new Text('This is the first tab content.'),
        ),
        new VStack(
            new Text('Tab 2: Settings'),
            new HStack(
                new Text('Notifications'),
                new Spacer(),
                new Toggle('', $checked),
            ),
        ),
        new VStack(
            new Text('Tab 3: About'),
            new Text('Perry PHP Demo v1.0'),
            new Text('Define UI once, generate for 11 platforms.'),
        ),
    ))->style(Style::make()->padding(8)),

    // ========== Section 6: ListWidget ==========
    new Text(' List Widget')->style($sectionTitle),
    (new ListWidget(
        (new Text('Item 1'))->style(Style::make()->padding(4)),
        (new Text('Item 2'))->style(Style::make()->padding(4)),
        (new Text('Item 3'))->style(Style::make()->padding(4)),
    ))->style(Style::make()->padding(8)),

    // ========== Section 7: ScrollView ==========
    new Text(' Scroll View')->style($sectionTitle),
    (new ScrollView(
        new VStack(
            new Text('Line 1'),
            new Text('Line 2'),
            new Text('Line 3'),
            new Text('Line 4'),
            new Text('Line 5'),
        )->style(Style::make()->padding(8))
    ))->style(Style::make()->height(100)->border(1, '#ccc')),

    // ========== Section 8: Image ==========
    new Text(' Image (SVG placeholder)')->style($sectionTitle),
    (new Image('data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%22200%22 height=%22100%22%3E%3Crect width=%22200%22 height=%22100%22 fill=%22%23eee%22/%3E%3Ctext x=%2250%25%22 y=%2250%25%22 text-anchor=%22middle%22 dy=%22.3em%22 fill=%22%23999%22%3EPlaceholder%3C/text%3E%3C/svg%3E'))->style(
        Style::make()->width(200)->height(100)->cornerRadius(8)
    ),

)->style(
    Style::make()
        ->padding(16)
        ->backgroundColor('#ffffff')
);

// ============================================================
// 5. Wrap in AppContainer with extra bindings
// ============================================================
$appWrapper = new AppContainer(
    $root,
    null, null,  // auto window size
    $greeting, $counter, $isDark, $opacity, $progress,
    $color, $checked, $items
);

// ============================================================
// 6. Build & generate
// ============================================================
$app = new App();
$app->setRoot($appWrapper);

$target = $argv[1] ?? 'macos';
$build  = in_array('--build', $argv);

if ($build) {
    $compiler = new \Perry\Build\Compiler(Target::fromString($target), 'build');
    $result   = $compiler->compile($appWrapper, 'perry-demo');

    if ($result->success) {
        echo "✓ Build successful!\n";
        echo "  Output: {$result->outputFile}\n";
        echo "  Source: {$result->sourceFile}\n";
    } else {
        echo "✗ Build failed:\n";
        echo "  {$result->error}\n";
    }
} else {
    $app->setTarget(Target::fromString($target));
    echo $app->generateForTarget();
}
