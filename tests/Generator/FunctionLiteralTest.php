<?php

declare(strict_types=1);

use Perry\Generator\CGenerator;
use Perry\Generator\CSharpGenerator;
use Perry\Generator\DartGenerator;
use Perry\Generator\JavaScriptGenerator;
use Perry\Generator\KotlinGenerator;
use Perry\Generator\SwiftGenerator;
use Perry\IR;

// ============================================================
// FunctionLiteral (Anonymous Function / Closure) Tests
// ============================================================

test('SwiftGenerator generates arrow function literal', function () {
    $param = new IR\MethodParameter('x', 'Int');
    $body = new IR\BinaryOp('+', new IR\Variable('x'), new IR\Literal(1));
    $func = new IR\FunctionLiteral([$param], $body, [], true);
    
    $gen = new SwiftGenerator();
    $result = $gen->generate($func);
    
    expect($result)->toBe('{ (x: Int) in x + 1 }');
});

test('SwiftGenerator generates block closure', function () {
    $param = new IR\MethodParameter('x', 'Int');
    $body = new IR\ReturnStatement(new IR\BinaryOp('+', new IR\Variable('x'), new IR\Literal(1)));
    $func = new IR\FunctionLiteral([$param], $body, [], false);
    
    $gen = new SwiftGenerator();
    $result = $gen->generate($func);
    
    expect($result)->toContain('{ (x: Int) in')
        ->and($result)->toContain('return x + 1')
        ->and($result)->toContain('}');
});

test('KotlinGenerator generates lambda', function () {
    $param = new IR\MethodParameter('x', 'Int');
    $body = new IR\BinaryOp('+', new IR\Variable('x'), new IR\Literal(1));
    $func = new IR\FunctionLiteral([$param], $body, [], true);
    
    $gen = new KotlinGenerator();
    $result = $gen->generate($func);
    
    expect($result)->toBe('{ (x: Int) -> x + 1 }');
});

test('DartGenerator generates arrow function', function () {
    $param = new IR\MethodParameter('x', 'int');
    $body = new IR\BinaryOp('+', new IR\Variable('x'), new IR\Literal(1));
    $func = new IR\FunctionLiteral([$param], $body, [], true);
    
    $gen = new DartGenerator();
    $result = $gen->generate($func);
    
    expect($result)->toBe('(x) => x + 1');
});

test('JavaScriptGenerator generates arrow function', function () {
    $param = new IR\MethodParameter('x');
    $body = new IR\BinaryOp('+', new IR\Variable('x'), new IR\Literal(1));
    $func = new IR\FunctionLiteral([$param], $body, [], true);
    
    $gen = new JavaScriptGenerator();
    $result = $gen->generate($func);
    
    expect($result)->toBe('(x) => x + 1');
});

test('JavaScriptGenerator generates function expression', function () {
    $param = new IR\MethodParameter('x');
    $body = new IR\ReturnStatement(new IR\BinaryOp('+', new IR\Variable('x'), new IR\Literal(1)));
    $func = new IR\FunctionLiteral([$param], $body, [], false);
    
    $gen = new JavaScriptGenerator();
    $result = $gen->generate($func);
    
    expect($result)->toContain('function(x) {')
        ->and($result)->toContain('return x + 1')
        ->and($result)->toContain('}');
});

test('CSharpGenerator generates lambda', function () {
    $param = new IR\MethodParameter('x', 'int');
    $body = new IR\BinaryOp('+', new IR\Variable('x'), new IR\Literal(1));
    $func = new IR\FunctionLiteral([$param], $body, [], true);
    
    $gen = new CSharpGenerator();
    $result = $gen->generate($func);
    
    expect($result)->toBe('(x) => x + 1');
});

test('CSharpGenerator generates anonymous method', function () {
    $param = new IR\MethodParameter('x', 'int');
    $body = new IR\ReturnStatement(new IR\BinaryOp('+', new IR\Variable('x'), new IR\Literal(1)));
    $func = new IR\FunctionLiteral([$param], $body, [], false);
    
    $gen = new CSharpGenerator();
    $result = $gen->generate($func);
    
    expect($result)->toContain('delegate(x) {')
        ->and($result)->toContain('return x + 1')
        ->and($result)->toContain('}');
});

test('CGenerator generates inline arrow function', function () {
    $param = new IR\MethodParameter('x', 'int');
    $body = new IR\BinaryOp('+', new IR\Variable('x'), new IR\Literal(1));
    $func = new IR\FunctionLiteral([$param], $body, [], true);
    
    $gen = new CGenerator();
    $result = $gen->generate($func);
    
    // C just returns the body for inline expressions
    expect($result)->toBe('(x + 1)');
});

test('FunctionLiteral with multiple parameters', function () {
    $params = [
        new IR\MethodParameter('a', 'Int'),
        new IR\MethodParameter('b', 'Int'),
    ];
    $body = new IR\BinaryOp('*', new IR\Variable('a'), new IR\Variable('b'));
    $func = new IR\FunctionLiteral($params, $body, [], true);
    
    $gen = new SwiftGenerator();
    $result = $gen->generate($func);
    
    expect($result)->toBe('{ (a: Int, b: Int) in a * b }');
});

test('FunctionLiteral with no parameters', function () {
    $body = new IR\Literal(42);
    $func = new IR\FunctionLiteral([], $body, [], true);
    
    $gen = new DartGenerator();
    $result = $gen->generate($func);
    
    expect($result)->toBe('() => 42');
});

test('FunctionLiteral with captured variables', function () {
    // Captures are stored but not directly used in generation
    // (actual capture handling happens in the transpiler)
    $param = new IR\MethodParameter('x', 'Int');
    $capture = new IR\Variable('capturedVar');
    $body = new IR\BinaryOp('+', new IR\Variable('x'), $capture);
    $func = new IR\FunctionLiteral([$param], $body, [$capture], true);
    
    $gen = new KotlinGenerator();
    $result = $gen->generate($func);
    
    // Captured variables are used directly in the body
    expect($result)->toBe('{ (x: Int) -> x + capturedVar }');
});
