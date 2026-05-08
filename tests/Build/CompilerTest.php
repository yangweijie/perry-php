<?php

declare(strict_types=1);

use Perry\Build\Compiler;
use Perry\Build\Target;
use Perry\UI\Styling\Style;
use Perry\UI\Widget\Button;
use Perry\UI\Widget\Text;
use Perry\UI\Widget\VStack;

test('compiler constructor sets target and default buildDir', function () {
    $compiler = new Compiler(Target::MacOs);
    expect($compiler)->toBeInstanceOf(Compiler::class);
});

test('compiler constructor accepts custom buildDir', function () {
    $compiler = new Compiler(Target::Web, '/tmp/perry-test-build');
    expect($compiler)->toBeInstanceOf(Compiler::class);
});

test('compiler compile for web target creates HTML file', function () {
    $buildDir = sys_get_temp_dir() . '/perry_test_' . uniqid();

    $root = new VStack(
        new Text('Hello Perry'),
    );

    $compiler = new Compiler(Target::Web, $buildDir);
    $result = $compiler->compile($root, 'test-app');

    expect($result->success)->toBeTrue();
    expect($result->outputFile)->toEndWith('.html');
    expect(file_exists($result->outputFile))->toBeTrue();

    $content = file_get_contents($result->outputFile);
    expect($content)->toContain('Hello Perry');

    // cleanup
    unlink($result->outputFile);
    rmdir($buildDir);
});

test('compiler compile for wasm target also creates HTML', function () {
    $buildDir = sys_get_temp_dir() . '/perry_test_' . uniqid();

    $root = new VStack(
        new Text('Wasm Test'),
    );

    $compiler = new Compiler(Target::Wasm, $buildDir);
    $result = $compiler->compile($root, 'wasm-app');

    expect($result->success)->toBeTrue();
    expect($result->outputFile)->toEndWith('.html');
    expect(file_exists($result->outputFile))->toBeTrue();

    $content = file_get_contents($result->outputFile);
    expect($content)->toContain('Wasm Test');

    // cleanup
    unlink($result->outputFile);
    rmdir($buildDir);
});

test('compiler creates build directory automatically', function () {
    $buildDir = sys_get_temp_dir() . '/perry_test_nonexistent_' . uniqid();
    expect(is_dir($buildDir))->toBeFalse();

    $root = new VStack(new Text('test'));
    $compiler = new Compiler(Target::Web, $buildDir);
    $result = $compiler->compile($root);

    expect($result->success)->toBeTrue();
    expect(is_dir($buildDir))->toBeTrue();

    // cleanup
    unlink($result->outputFile);
    rmdir($buildDir);
});

test('compiler compileWeb creates valid HTML structure', function () {
    $buildDir = sys_get_temp_dir() . '/perry_test_' . uniqid();

    $root = new VStack(
        new Text('Title'),
        new Button('Click'),
    );

    $compiler = new Compiler(Target::Web, $buildDir);
    $result = $compiler->compile($root);

    expect($result->success)->toBeTrue();
    expect($result->outputFile)->not->toBeEmpty();
    expect($result->sourceFile)->not->toBeEmpty();

    $html = file_get_contents($result->outputFile);
    expect($html)->toContain('<!DOCTYPE html>');
    expect($html)->toContain('Title');
    expect($html)->toContain('Click');

    // cleanup
    unlink($result->outputFile);
    rmdir($buildDir);
});

test('compiler returns CompileResult', function () {
    $buildDir = sys_get_temp_dir() . '/perry_test_' . uniqid();

    $root = new VStack(new Text('test'));
    $compiler = new Compiler(Target::Web, $buildDir);
    $result = $compiler->compile($root);

    expect($result)->toBeInstanceOf(\Perry\Build\CompileResult::class);
    expect($result->success)->toBeBool();

    // cleanup
    unlink($result->outputFile);
    rmdir($buildDir);
});
