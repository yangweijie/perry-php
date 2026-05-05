<?php

use Perry\Generator\SwiftGenerator;
use Perry\IR;

test('SwiftGenerator generates variable assignment', function () {
    $literal = new IR\Literal('0');
    $assign = new IR\Assignment('display', $literal);
    
    $gen = new SwiftGenerator(['display']);
    $result = $gen->generate($assign);
    
    expect($result)->toBe('display = "0"');
});

test('SwiftGenerator generates new variable declaration', function () {
    $literal = new IR\Literal(0);
    $assign = new IR\Assignment('count', $literal);
    
    $gen = new SwiftGenerator([]);
    $result = $gen->generate($assign);
    
    expect($result)->toBe('var count = 0');
});

test('SwiftGenerator generates if statement', function () {
    $condition = new IR\BinaryOp('===', new IR\Variable('x'), new IR\Literal(0));
    $then = new IR\Assignment('result', new IR\Literal('zero'));
    $if = new IR\IfStatement($condition, $then, null);
    
    $gen = new SwiftGenerator([]);
    $result = $gen->generate($if);
    
    expect($result)->toContain('if x == 0 {')
        ->and($result)->toContain('result = "zero"')
        ->and($result)->toContain('}');
});

test('SwiftGenerator generates ternary as ternary', function () {
    $ternary = new IR\Ternary(
        new IR\Variable('cond'),
        new IR\Literal('yes'),
        new IR\Literal('no')
    );
    
    $gen = new SwiftGenerator([]);
    $result = $gen->generate($ternary);
    
    expect($result)->toBe('cond ? "yes" : "no"');
});

test('SwiftGenerator handles === false', function () {
    $op = new IR\BinaryOp('===', new IR\Variable('found'), new IR\Literal(false));
    
    $gen = new SwiftGenerator([]);
    $result = $gen->generate($op);
    
    expect($result)->toBe('!found');
});

test('SwiftGenerator generates function calls', function () {
    $call = new IR\FunctionCall('strlen', [
        new IR\Variable('str')
    ]);
    
    $gen = new SwiftGenerator([]);
    $result = $gen->generate($call);
    
    expect($result)->toBe('str.count');
});

test('SwiftGenerator generates substr with -1', function () {
    $call = new IR\FunctionCall('substr', [
        new IR\Variable('s'),
        new IR\Literal(-1)
    ]);
    
    $gen = new SwiftGenerator([]);
    $result = $gen->generate($call);
    
    expect($result)->toBe('String(s.last!)');
});

test('SwiftGenerator generates floatval', function () {
    $call = new IR\FunctionCall('floatval', [
        new IR\Variable('x')
    ]);
    
    $gen = new SwiftGenerator([]);
    $result = $gen->generate($call);
    
    expect($result)->toBe('Double(x) ?? 0');
});

test('SwiftGenerator generates in_array', function () {
    $call = new IR\FunctionCall('in_array', [
        new IR\Variable('val'),
        new IR\Variable('arr')
    ]);
    
    $gen = new SwiftGenerator([]);
    $result = $gen->generate($call);
    
    expect($result)->toBe('arr.contains(val)');
});

test('SwiftGenerator generates preg_split', function () {
    $call = new IR\FunctionCall('preg_split', [
        new IR\Literal('/[+\\-]/'),
        new IR\Variable('str')
    ]);
    
    $gen = new SwiftGenerator([]);
    $result = $gen->generate($call);
    
    expect($result)->toContain('components(separatedBy:')
        ->and($result)->toContain('CharacterSet(charactersIn:');
});
