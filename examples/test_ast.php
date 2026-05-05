<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Perry\IR\Builder;
use Perry\Generator\SwiftGenerator;
use Perry\Generator\JavaScriptGenerator;

$builder = new Builder();

$code = <<<'PHP'
<?php
$result = "";
$operators = ["+", "-", "x", "÷", "%"];
$lastChar = substr($display, -1);
if (in_array($lastChar, $operators)) {
    $display = substr($display, 0, -1);
} else {
    $operand1 = floatval($display);
}
$operation = $op;
$isTyping = false;
$display = $display . $op;
PHP;

$program = $builder->buildFromCode($code);

echo "=== IR Nodes ===\n";
echo "Statements: " . count($program->statements) . "\n\n";

$swiftGen = new SwiftGenerator();
$jsGen = new JavaScriptGenerator();

echo "=== Swift Output ===\n";
echo $program->accept($swiftGen) . "\n\n";

echo "=== JavaScript Output ===\n";
echo $program->accept($jsGen) . "\n";
