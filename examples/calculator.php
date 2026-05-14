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
use Perry\UI\Styling\Style;
use Perry\UI\Widget\AppContainer;
use Perry\UI\Widget\Button;
use Perry\UI\Widget\HStack;
use Perry\UI\Widget\Text;
use Perry\UI\Widget\VStack;

$display = new Binding('display', '0');
$result = new Binding('result', '');
$operand1 = new Binding('operand1', 0.0);
$operand2 = new Binding('operand2', 0.0);
$operation = new Binding('operation', '');
$isTyping = new Binding('isTyping', false);
$typed = new Binding('typed', '');

$bgBlack = Style::make()->backgroundColor('#000000')->cornerRadius(20);
$displayAreaStyle = Style::make()->height(80);
$displayStyle = Style::make()->fontSize(36)->foregroundColor('#ffffff')->padding(4);
$resultStyle = Style::make()->fontSize(18)->foregroundColor('#888888')->padding(4);

$grayBtn = Style::make()->fontSize(18)->foregroundColor('#ffffff')->cornerRadius(20)->width(40)->height(40)->backgroundColor('#505050');
$darkGrayBtn = Style::make()->fontSize(18)->foregroundColor('#ffffff')->cornerRadius(20)->width(40)->height(40)->backgroundColor('#333333');
$orangeBtn = Style::make()->fontSize(18)->foregroundColor('#ffffff')->cornerRadius(20)->width(40)->height(40)->backgroundColor('#CC7700');

$appendDigit = function (string $digit) use ($display, $operand2, $operation, $isTyping, $result, $typed): Action {
    return Action::fromClosure(function() use ($display, $operand2, $operation, $isTyping, $result, $typed, $digit) {
        $result = "";
        if ($isTyping) {
            $display = $display . $digit;
            $typed = $typed . $digit;
        } else {
            if (empty($operation)) {
                $display = $digit;
            } else {
                $display = $display . $digit;
            }
            $typed = $digit;
            $isTyping = true;
        }
        $operand2 = floatval($typed);
    }, ['digit' => $digit]);
};

$setOperation = function (string $op) use ($display, $operand1, $operand2, $operation, $isTyping, $result, $typed): Action {
    return Action::fromClosure(function() use ($display, $operand1, $operand2, $operation, $isTyping, $result, $typed, $op) {
        $result = "";
        $operators = ["+", "-", "x", "÷", "%"];
        $lastChar = substr($display, -1);
        if (in_array($lastChar, $operators)) {
            $display = substr($display, 0, -1);
        } else {
            $operand1 = !empty($typed) ? floatval($typed) : floatval($display);
            $operand2 = 0;
        }
        $operation = $op;
        $isTyping = false;
        $typed = "";
        $display = $display . $op;
    }, ['op' => $op]);
};

$calculate = function () use ($display, $operand1, $operand2, $operation, $isTyping, $result, $typed): Action {
    return Action::fromClosure(function() use ($display, $operand1, $operand2, $operation, $isTyping, $result, $typed) {
        if (empty($operation)) {
            return;
        }
        $current = $operand2;
        $calcResult = $operand1;
        $isAdd = $operation == "+";
        $isSub = $operation == "-";
        $isMul = $operation == "x";
        $isDiv = $operation == "÷";
        $isMod = $operation == "%";
        if ($isAdd) {
            $calcResult = $operand1 + $current;
        }
        if ($isSub) {
            $calcResult = $operand1 - $current;
        }
        if ($isMul) {
            $calcResult = $operand1 * $current;
        }
        if ($isDiv && $current != 0) {
            $calcResult = $operand1 / $current;
        }
        if ($isMod && $current != 0) {
            $calcResult = floatval(intval($operand1) % intval($current));
        }
        $isWhole = $calcResult == floor($calcResult);
        $resultStr = $isWhole ? strval(intval($calcResult)) : strval($calcResult);
        $result = $display . "=";
        $display = $resultStr;
        $operand1 = $calcResult;
        $operation = "";
        $isTyping = false;
        $typed = "";
    });
};

$clear = Action::fromClosure(function() use ($display, $result, $operand1, $operand2, $operation, $isTyping, $typed) {
    $display = "0";
    $result = "";
    $operand1 = 0;
    $operand2 = 0;
    $operation = "";
    $isTyping = false;
    $typed = "";
});

$backspace = Action::fromClosure(function() use ($display, $result, $isTyping, $typed) {
    $result = "";
    if (strlen($display) > 1) {
        $display = substr($display, 0, -1);
        if (strlen($typed) > 1) {
            $typed = substr($typed, 0, -1);
        } else {
            $typed = "";
        }
    } else {
        $display = "0";
        $isTyping = false;
        $typed = "";
    }
});

$negate = Action::fromClosure(function() use ($display, $result, $typed) {
    $result = "";
    $val = floatval($typed);
    if ($val != 0) {
        $negVal = -$val;
        $negStr = $val == floor($val) ? strval(intval($negVal)) : strval($negVal);
        $typed = $negStr;
        $display = $negStr;
    }
});

$decimal = Action::fromClosure(function() use ($display, $result, $isTyping, $typed, $operation) {
    $result = "";
    if ($isTyping) {
        if (strpos($typed, '.') === false) {
            $display = $display . ".";
            $typed = $typed . ".";
        }
    } else {
        $operators = ["+", "-", "x", "÷", "%"];
        $lastChar = substr($display, -1);
        if (in_array($lastChar, $operators)) {
            $display = $display . "0.";
            $typed = "0.";
        } elseif (empty($operation) && $display == "0") {
            $display = "0.";
            $typed = "0.";
        } elseif (empty($operation)) {
            $display = $display . "0.";
            $typed = "0.";
        } else {
            $display = $display . ".";
            $typed = $typed . ".";
        }
        $isTyping = true;
    }
});

$calculator = new AppContainer(
    (new VStack(
        (new VStack(
            (new Text($result))->style($resultStyle),
            (new Text($display))->style($displayStyle),
        ))->style($displayAreaStyle),
        (new HStack(
            (new Button('⌫', $backspace))->style($grayBtn),
            (new Button('C', $clear))->style($grayBtn),
            (new Button('%', $setOperation('%')))->style($grayBtn),
            (new Button('÷', $setOperation('÷')))->style($orangeBtn),
        ))->style(Style::make()->padding(4)),
        (new HStack(
            (new Button('7', $appendDigit('7')))->style($darkGrayBtn),
            (new Button('8', $appendDigit('8')))->style($darkGrayBtn),
            (new Button('9', $appendDigit('9')))->style($darkGrayBtn),
            (new Button('x', $setOperation('x')))->style($orangeBtn),
        ))->style(Style::make()->padding(4)),
        (new HStack(
            (new Button('4', $appendDigit('4')))->style($darkGrayBtn),
            (new Button('5', $appendDigit('5')))->style($darkGrayBtn),
            (new Button('6', $appendDigit('6')))->style($darkGrayBtn),
            (new Button('-', $setOperation('-')))->style($orangeBtn),
        ))->style(Style::make()->padding(4)),
        (new HStack(
            (new Button('1', $appendDigit('1')))->style($darkGrayBtn),
            (new Button('2', $appendDigit('2')))->style($darkGrayBtn),
            (new Button('3', $appendDigit('3')))->style($darkGrayBtn),
            (new Button('+', $setOperation('+')))->style($orangeBtn),
        ))->style(Style::make()->padding(4)),
        (new HStack(
            (new Button('+/-', $negate))->style($orangeBtn),
            (new Button('0', $appendDigit('0')))->style($darkGrayBtn),
            (new Button('.', $decimal))->style($darkGrayBtn),
            (new Button('=', $calculate()))->style($orangeBtn),
        ))->style(Style::make()->padding(4)),
    ))->style($bgBlack),
    230,
    410,
    $operand1,
    $operand2,
    $operation,
    $isTyping,
    $typed,
);

$target = $argv[1] ?? 'macos';
$build = in_array('--build', $argv);

if ($build) {
    echo "=== Perry Calculator - Build ===\n";
    echo "Target: {$target}\n\n";

    $app = new App(Target::fromString($target));
    $app->setRoot($calculator);

    $compiler = new \Perry\Build\Compiler(Target::fromString($target), 'build');
    $result = $compiler->compile($calculator, 'calculator');

    if ($result->success) {
        echo "✓ Build successful!\n";
        echo "  Output: {$result->outputFile}\n";
        echo "  Source: {$result->sourceFile}\n\n";
        if ($target === 'macos') {
            echo "Run with:\n";
            echo "  open {$result->outputFile}\n";
        } else if ($target === 'web') {
            echo "Open in browser:\n";
            echo "  {$result->outputFile}\n";
        }
    } else {
        echo "✗ Build failed:\n";
        echo "  {$result->error}\n";
    }
} else {
    $app = new App(Target::fromString($target));
    $app->setRoot($calculator);
    echo $app->generateForTarget() . "\n\n";
}
