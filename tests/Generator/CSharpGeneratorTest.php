<?php

use Perry\Generator\CSharpGenerator;
use Perry\IR;

test('CSharpGenerator generates var declaration', function () {
    $literal = new IR\Literal(0);
    $assign = new IR\Assignment('count', $literal);
    
    $gen = new CSharpGenerator([]);
    $result = $gen->generate($assign);
    
    expect($result)->toBe('var count = 0;');
});

test('CSharpGenerator generates state var assignment', function () {
    $literal = new IR\Literal('0');
    $assign = new IR\Assignment('display', $literal);
    
    $gen = new CSharpGenerator(['display']);
    $result = $gen->generate($assign);
    
    expect($result)->toBe('display = "0";');
});

test('CSharpGenerator generates Convert.ToDouble', function () {
    $call = new IR\FunctionCall('floatval', [new IR\Variable('x')]);
    
    $gen = new CSharpGenerator([]);
    $result = $gen->generate($call);
    
    expect($result)->toBe('Convert.ToDouble(x)');
});

test('CSharpGenerator generates Convert.ToInt32', function () {
    $call = new IR\FunctionCall('intval', [new IR\Variable('x')]);
    
    $gen = new CSharpGenerator([]);
    $result = $gen->generate($call);
    
    expect($result)->toBe('Convert.ToInt32(x)');
});

test('CSharpGenerator generates .Length', function () {
    $call = new IR\FunctionCall('strlen', [new IR\Variable('s')]);
    
    $gen = new CSharpGenerator([]);
    $result = $gen->generate($call);
    
    expect($result)->toBe('s.Length');
});

test('CSharpGenerator generates .Contains', function () {
    $call = new IR\FunctionCall('in_array', [
        new IR\Variable('val'),
        new IR\Variable('arr')
    ]);
    
    $gen = new CSharpGenerator([]);
    $result = $gen->generate($call);
    
    expect($result)->toBe('arr.Contains(val)');
});

test('CSharpGenerator generates .IndexOf', function () {
    $call = new IR\FunctionCall('strpos', [
        new IR\Variable('s'),
        new IR\Literal('x')
    ]);
    
    $gen = new CSharpGenerator([]);
    $result = $gen->generate($call);
    
    expect($result)->toBe('s.IndexOf("x")');
});

test('CSharpGenerator generates Math.Floor', function () {
    $call = new IR\FunctionCall('floor', [new IR\Variable('x')]);
    
    $gen = new CSharpGenerator([]);
    $result = $gen->generate($call);
    
    expect($result)->toBe('Math.Floor(x)');
});

test('CSharpGenerator generates .ToString("F2")', function () {
    $call = new IR\FunctionCall('number_format', [
        new IR\Variable('n'),
        new IR\Literal(2)
    ]);
    
    $gen = new CSharpGenerator([]);
    $result = $gen->generate($call);
    
    expect($result)->toBe('n.ToString("F2")');
});

test('CSharpGenerator generates Regex.Split', function () {
    $call = new IR\FunctionCall('preg_split', [
        new IR\Literal('/[+\\-]/'),
        new IR\Variable('str')
    ]);
    
    $gen = new CSharpGenerator([]);
    $result = $gen->generate($call);
    
    expect($result)->toContain('Regex.Split(');
});

test('CSharpGenerator generates ternary', function () {
    $ternary = new IR\Ternary(
        new IR\Variable('cond'),
        new IR\Literal('yes'),
        new IR\Literal('no')
    );
    
    $gen = new CSharpGenerator([]);
    $result = $gen->generate($ternary);
    
    expect($result)->toBe('cond ? "yes" : "no"');
});

test('CSharpGenerator generates array literal', function () {
    $arr = new IR\ArrayLiteral([
        new IR\Literal('a'),
        new IR\Literal('b'),
    ]);
    
    $gen = new CSharpGenerator([]);
    $result = $gen->generate($arr);
    
    expect($result)->toBe('new[] { "a", "b" }');
});
