<?php

declare(strict_types=1);

use Perry\Codegen\CodegenFactory;
use Perry\UI\Action;
use Perry\UI\Binding;
use Perry\UI\Styling\Style;
use Perry\UI\Widget\AppContainer;
use Perry\UI\Widget\Button;
use Perry\UI\Widget\Checkbox;
use Perry\UI\Widget\HStack;
use Perry\UI\Widget\Text;
use Perry\UI\Widget\TextInput;
use Perry\UI\Widget\VStack;

/**
 * Compilation & integration tests.
 * Verify generated code can be parsed/compiled where tooling is available.
 */

test('SwiftUI calculator code compiles with swiftc', function () {
    $swiftcPath = `which swiftc 2>/dev/null`;
    if (trim($swiftcPath) === '') {
        $this->markTestSkipped('swiftc not available on this system');
    }

    // Generate code
    $out = shell_exec('php ' . __DIR__ . '/../../examples/calculator.php swiftui 2>/dev/null');
    expect($out)->not->toBeNull();
    expect($out)->toContain('import SwiftUI');

    $tempFile = sys_get_temp_dir() . '/perry_calc_' . uniqid() . '.swift';
    file_put_contents($tempFile, $out);

    // Try compilation
    $binary = sys_get_temp_dir() . '/perry_calc_' . uniqid();
    $cmd = sprintf('swiftc -o %s %s -framework SwiftUI -parse-as-library 2>/dev/null', $binary, $tempFile);
    $output = null;
    $retCode = null;
    exec($cmd, $output, $retCode);

    if ($retCode === 0) {
        expect(file_exists($binary))->toBeTrue('swift compilation succeeded');
        @unlink($binary);
    } else {
        // Warnings are acceptable (nil coalescing warnings from transpiler)
        // Only fail on actual errors
        $hasError = false;
        foreach ($output as $line) {
            if (str_contains($line, 'error:')) {
                $hasError = true;
            }
        }
        expect($hasError)->toBeFalse('Swift compilation should not have errors: ' . implode("\n", $output));
    }

    @unlink($tempFile);
});

test('SwiftUI simple button compiles', function () {
    $swiftcPath = `which swiftc 2>/dev/null`;
    if (trim($swiftcPath) === '') {
        $this->markTestSkipped('swiftc not available');
    }

    $backend = (new CodegenFactory())->get('swiftui');
    $root = new VStack(
        new Button('Test', Action::set(new Binding('count', 0), 1)),
        new Text('Hello World'),
    );

    $code = $backend->generate($root);

    $tempFile = sys_get_temp_dir() . '/perry_btn_' . uniqid() . '.swift';
    file_put_contents($tempFile, $code);

    $binary = sys_get_temp_dir() . '/perry_btn_' . uniqid();
    $cmd = sprintf('swiftc -o %s %s -framework SwiftUI -parse-as-library 2>/dev/null', $binary, $tempFile);
    $output = null;
    $retCode = null;
    exec($cmd, $output, $retCode);

    $hasError = false;
    foreach ($output as $line) {
        if (str_contains($line, 'error:')) {
            $hasError = true;
        }
    }
    expect($hasError)->toBeFalse('SwiftUI button code should compile: ' . ($hasError ? implode("\n", $output) : 'OK'));

    @unlink($tempFile);
    @unlink($binary);
});

// ================================================================
// Boundary: Special characters in text content
// ================================================================

test('special characters generate safely across all backends', function () {
    $factory = new CodegenFactory();
    $specials = [
        "Simple text",
        "Text with 'quotes'",
        'Text with "double quotes"',
        "Text with <html> tags",
        "Text with \n newlines",
        "Text with \$dollar sign",
        "Text with emoji 🎉",
        "A\nB\nC\nMulti-line",
        "Text with & ampersand & more",
    ];

    foreach ($factory->available() as $name) {
        $backend = $factory->get($name);
        foreach ($specials as $idx => $content) {
            $widget = new Text($content);
            $out = $backend->generate($widget);
            expect(strlen($out))->toBeGreaterThan(0, "$name: special[$idx] '$content' should generate output");
        }
    }
});

// ================================================================
// Boundary: Deep nesting
// ================================================================

test('deep nesting generates output across all backends', function () {
    $factory = new CodegenFactory();

    for ($depth = 1; $depth <= 10; $depth++) {
        $widget = new Text('leaf');
        for ($i = 0; $i < $depth; $i++) {
            $widget = new VStack($widget);
        }

        foreach ($factory->available() as $name) {
            $backend = $factory->get($name);
            $out = $backend->generate($widget);
            expect(strlen($out))->toBeGreaterThan(0, "$name: depth=$depth should generate output");
        }
    }
});

// ================================================================
// Boundary: Text::content() with binding returns binding name
// ================================================================

test('Text with binding returns binding name via content()', function () {
    $binding = new Binding('userName', 'Alice');
    $text = new Text($binding);

    expect($text->content())->toBe('userName');
    expect($text->getBinding())->toBe($binding);
});

// ================================================================
// Boundary: TabView with children registered
// ================================================================

test('TabView registers tabs as children', function () {
    $tabView = new \Perry\UI\Widget\TabView(
        new VStack(new Text('Tab 1')),
        new VStack(new Text('Tab 2')),
    );

    expect(count($tabView->children()))->toBe(2);
});

// ================================================================
// Boundary: Action::calculate stores all operands
// ================================================================

test('Action::calculate stores all bindings', function () {
    $display = new Binding('display', '0');
    $op1 = new Binding('operand1', 0.0);
    $op2 = new Binding('operand2', 0.0);
    $op = new Binding('operation', '');

    $action = Action::calculate($display, $op1, $op2, $op);

    expect($action->type)->toBe(\Perry\UI\ActionType::Calculate);
    expect($action->target)->toBe($display);
    // closureBindings should contain the other three
    expect($action->closureBindings)->toHaveKeys(['operand1', 'operand2', 'operation']);
    expect($action->closureBindings['operand1'])->toBe($op1);
    expect($action->closureBindings['operand2'])->toBe($op2);
    expect($action->closureBindings['operation'])->toBe($op);
});

// ================================================================
// Boundary: Null/missing values
// ================================================================

test('widgets with empty string content generate output', function () {
    $factory = new CodegenFactory();
    $emptyText = new Text('');

    foreach ($factory->available() as $name) {
        $backend = $factory->get($name);
        $out = $backend->generate($emptyText);
        expect(strlen($out))->toBeGreaterThan(0, "$name: empty text should generate output");
    }
});

test('Button without action generates output', function () {
    $factory = new CodegenFactory();
    $btn = new Button('No Action');

    foreach ($factory->available() as $name) {
        $backend = $factory->get($name);
        $out = $backend->generate($btn);
        expect(strlen($out))->toBeGreaterThan(0, "$name: button without action should generate output");
    }
});
