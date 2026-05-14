# Calculator

Full-featured calculator with 7 action types, state management, and custom styling. Runs on macOS, Windows, Web, Android, and Linux.

```bash
# Generate HTML
php examples/calculator.php web > calculator.html

# Build macOS app
php examples/calculator.php macos --build
# Output: build/Calculator.app
```

## Code

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

// State
$display = new Binding('display', '0');
$result = new Binding('result', '');
$operand1 = new Binding('operand1', 0.0);
$operand2 = new Binding('operand2', 0.0);
$operation = new Binding('operation', '');
$isTyping = new Binding('isTyping', false);
$typed = new Binding('typed', '');

// Styles
$numberBtn = Style::make()->fontSize(24)->backgroundColor('#f0f0f0')->padding(20)->cornerRadius(8);
$opBtn = Style::make()->fontSize(24)->backgroundColor('#FF9500')->foregroundColor('#fff')->padding(20)->cornerRadius(8);

// Digit button factory
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

// Build
$app = new App(Target::fromString('macos'));
$app->setRoot(
    new AppContainer(
        new VStack(
            (new Text($result))->style(Style::make()->fontSize(16)->foregroundColor('#888')),
            (new Text($display))->style(Style::make()->fontSize(32)->padding(16)),
            // ... (rows of buttons)
        ),
        320, 480,
        $operand1, $operand2, $operation, $isTyping, $typed
    )
);

echo $app->generateForTarget();
```
