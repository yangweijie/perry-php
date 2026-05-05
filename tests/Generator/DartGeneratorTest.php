<?php

use Perry\Generator\DartGenerator;
use Perry\IR;

test('DartGenerator generates var declaration', function () {
    $literal = new IR\Literal(0);
    $assign = new IR\Assignment('count', $literal);
    
    $gen = new DartGenerator([]);
    $result = $gen->generate($assign);
    
    expect($result)->toBe('var count = 0');
});

test('DartGenerator generates state var assignment', function () {
    $literal = new IR\Literal('0');
    $assign = new IR\Assignment('display', $literal);
    
    $gen = new DartGenerator(['display']);
    $result = $gen->generate($assign);
    
    expect($result)->toBe('display.value = "0"');
});

test('DartGenerator generates double.parse', function () {
    $call = new IR\FunctionCall('floatval', [new IR\Variable('x')]);
    
    $gen = new DartGenerator([]);
    $result = $gen->generate($call);
    
    expect($result)->toBe('double.parse(x.toString())');
});

test('DartGenerator generates int.parse', function () {
    $call = new IR\FunctionCall('intval', [new IR\Variable('x')]);
    
    $gen = new DartGenerator([]);
    $result = $gen->generate($call);
    
    expect($result)->toBe('int.parse(x.toString())');
});

test('DartGenerator generates .length', function () {
    $call = new IR\FunctionCall('strlen', [new IR\Variable('s')]);
    
    $gen = new DartGenerator([]);
    $result = $gen->generate($call);
    
    expect($result)->toBe('s.length');
});

test('DartGenerator generates .contains', function () {
    $call = new IR\FunctionCall('in_array', [
        new IR\Variable('val'),
        new IR\Variable('arr')
    ]);
    
    $gen = new DartGenerator([]);
    $result = $gen->generate($call);
    
    expect($result)->toBe('arr.contains(val)');
});

test('DartGenerator generates .last', function () {
    $call = new IR\FunctionCall('end', [new IR\Variable('arr')]);
    
    $gen = new DartGenerator([]);
    $result = $gen->generate($call);
    
    expect($result)->toBe('arr.last');
});

test('DartGenerator generates .floor()', function () {
    $call = new IR\FunctionCall('floor', [new IR\Variable('x')]);
    
    $gen = new DartGenerator([]);
    $result = $gen->generate($call);
    
    expect($result)->toBe('x.floor()');
});

test('DartGenerator generates .toStringAsFixed', function () {
    $call = new IR\FunctionCall('number_format', [
        new IR\Variable('n'),
        new IR\Literal(2)
    ]);
    
    $gen = new DartGenerator([]);
    $result = $gen->generate($call);
    
    expect($result)->toBe('n.toStringAsFixed(2)');
});

test('DartGenerator generates .split for preg_split', function () {
    $call = new IR\FunctionCall('preg_split', [
        new IR\Literal('/[+\\-]/'),
        new IR\Variable('str')
    ]);
    
    $gen = new DartGenerator([]);
    $result = $gen->generate($call);
    
    expect($result)->toContain('.split(RegExp(');
});

test('DartGenerator generates ternary', function () {
    $ternary = new IR\Ternary(
        new IR\Variable('cond'),
        new IR\Literal('yes'),
        new IR\Literal('no')
    );
    
    $gen = new DartGenerator([]);
    $result = $gen->generate($ternary);
    
    expect($result)->toBe('cond ? "yes" : "no"');
});

test('DartGenerator generates list literal', function () {
    $arr = new IR\ArrayLiteral([
        new IR\Literal('a'),
        new IR\Literal('b'),
    ]);
    
    $gen = new DartGenerator([]);
    $result = $gen->generate($arr);
    
    expect($result)->toBe('["a", "b"]');
});
