<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Perry\IR\Builder;

$builder = new Builder();

$display = '0';
$isTyping = false;
$result = '';
$digit = '1';

$closure = function() use ($display, $isTyping, $result, $digit) {
    $result = "";
    if ($isTyping) {
        $display = $display == "0" ? $digit : $display . $digit;
    } else {
        $display = $digit;
        $isTyping = true;
    }
};

$reflection = new ReflectionFunction($closure);
echo "Start line: " . $reflection->getStartLine() . "\n";
echo "End line: " . $reflection->getEndLine() . "\n";
echo "File: " . $reflection->getFileName() . "\n\n";

$program = $builder->buildFromClosure($closure);

echo "=== IR Statements ===\n";
echo "Count: " . count($program->statements) . "\n";

$swiftGen = new \Perry\Generator\SwiftGenerator(['display', 'result', 'isTyping']);
echo "\n=== Swift Output ===\n";
echo $program->accept($swiftGen) . "\n";
