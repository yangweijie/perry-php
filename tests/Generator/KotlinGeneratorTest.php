<?php

use Perry\Generator\KotlinGenerator;
use Perry\IR;

test('KotlinGenerator generates var declaration', function () {
    $literal = new IR\Literal(0);
    $assign = new IR\Assignment('count', $literal);
    
    $gen = new KotlinGenerator([]);
    $result = $gen->generate($assign);
    
    expect($result)->toBe('var count = 0');
});

test('KotlinGenerator generates state var assignment', function () {
    $literal = new IR\Literal('0');
    $assign = new IR\Assignment('display', $literal);
    
    $gen = new KotlinGenerator(['display']);
    $result = $gen->generate($assign);
    
    expect($result)->toBe('display = "0"');
});

test('KotlinGenerator generates toDoubleOrNull', function () {
    $call = new IR\FunctionCall('floatval', [
        new IR\Variable('x')
    ]);
    
    $gen = new KotlinGenerator([]);
    $result = $gen->generate($call);
    
    expect($result)->toBe('(((x) as? Number)?.toDouble() ?: 0.0)');
});

test('KotlinGenerator generates toIntOrNull', function () {
    $call = new IR\FunctionCall('intval', [
        new IR\Variable('x')
    ]);
    
    $gen = new KotlinGenerator([]);
    $result = $gen->generate($call);
    
    expect($result)->toBe('(((x) as? Number)?.toInt() ?: 0)');
});

test('KotlinGenerator generates length', function () {
    $call = new IR\FunctionCall('strlen', [
        new IR\Variable('s')
    ]);
    
    $gen = new KotlinGenerator([]);
    $result = $gen->generate($call);
    
    expect($result)->toBe('s.length');
});

test('KotlinGenerator generates contains', function () {
    $call = new IR\FunctionCall('in_array', [
        new IR\Variable('val'),
        new IR\Variable('arr')
    ]);
    
    $gen = new KotlinGenerator([]);
    $result = $gen->generate($call);
    
    expect($result)->toBe('arr.contains(value)');
});

test('KotlinGenerator generates last()', function () {
    $call = new IR\FunctionCall('end', [
        new IR\Variable('arr')
    ]);
    
    $gen = new KotlinGenerator([]);
    $result = $gen->generate($call);
    
    expect($result)->toBe('arr.last()');
});

test('KotlinGenerator generates Math.floor', function () {
    $call = new IR\FunctionCall('floor', [
        new IR\Variable('x')
    ]);
    
    $gen = new KotlinGenerator([]);
    $result = $gen->generate($call);
    
    expect($result)->toBe('Math.floor(x).toInt()');
});

test('KotlinGenerator generates dropLast', function () {
    $call = new IR\FunctionCall('substr', [
        new IR\Variable('s'),
        new IR\Literal(-1)
    ]);
    
    $gen = new KotlinGenerator([]);
    $result = $gen->generate($call);
    
    expect($result)->toBe('s.last().toString()');
});

test('KotlinGenerator generates if expression for ternary', function () {
    $ternary = new IR\Ternary(
        new IR\Variable('cond'),
        new IR\Literal('yes'),
        new IR\Literal('no')
    );
    
    $gen = new KotlinGenerator([]);
    $result = $gen->generate($ternary);
    
    expect($result)->toBe('if (cond) "yes" else "no"');
});

test('KotlinGenerator generates listOf for array literal', function () {
    $arr = new IR\ArrayLiteral([
        new IR\Literal('a'),
        new IR\Literal('b'),
    ]);
    
    $gen = new KotlinGenerator([]);
    $result = $gen->generate($arr);
    
    expect($result)->toBe('listOf("a", "b")');
});

test('KotlinGenerator generates Math.ceil', function () {
    $call = new IR\FunctionCall('ceil', [new IR\Variable('x')]);
    $gen = new KotlinGenerator([]);
    expect($gen->generate($call))->toBe('Math.ceil(x).toInt()');
});

test('KotlinGenerator generates Math.round', function () {
    $call = new IR\FunctionCall('round', [new IR\Variable('x')]);
    $gen = new KotlinGenerator([]);
    expect($gen->generate($call))->toBe('Math.round(x).toInt()');
});

test('KotlinGenerator generates .size for count', function () {
    $call = new IR\FunctionCall('count', [new IR\Variable('arr')]);
    $gen = new KotlinGenerator([]);
    expect($gen->generate($call))->toBe('arr.size');
});

test('KotlinGenerator generates .add for array_push', function () {
    $call = new IR\FunctionCall('array_push', [new IR\Variable('arr'), new IR\Variable('v')]);
    $gen = new KotlinGenerator([]);
    expect($gen->generate($call))->toBe('arr.add(v)');
});

test('KotlinGenerator generates json_decode', function () {
    $call = new IR\FunctionCall('json_decode', [new IR\Variable('s')]);
    $gen = new KotlinGenerator([]);
    expect($gen->generate($call))->toBe('org.json.JSONObject(s)');
});

test('KotlinGenerator generates json_encode', function () {
    $call = new IR\FunctionCall('json_encode', [new IR\Variable('v')]);
    $gen = new KotlinGenerator([]);
    expect($gen->generate($call))->toBe('v.toString()');
});

test('KotlinGenerator generates is_null', function () {
    $call = new IR\FunctionCall('is_null', [new IR\Variable('x')]);
    $gen = new KotlinGenerator([]);
    expect($gen->generate($call))->toBe('x == null');
});

test('KotlinGenerator generates is_array', function () {
    $call = new IR\FunctionCall('is_array', [new IR\Variable('x')]);
    $gen = new KotlinGenerator([]);
    expect($gen->generate($call))->toBe('x is Array<*>');
});

test('KotlinGenerator generates array type cast', function () {
    $cast = new IR\Cast('array', new IR\Variable('x'));
    $gen = new KotlinGenerator([]);
    expect($gen->generate($cast))->toBe('x as Array<*>');
});

test('KotlinGenerator generates object type cast', function () {
    $cast = new IR\Cast('object', new IR\Variable('x'));
    $gen = new KotlinGenerator([]);
    expect($gen->generate($cast))->toBe('x as Any');
});

test('KotlinGenerator generates filter for preg_split', function () {
    $call = new IR\FunctionCall('preg_split', [
        new IR\Literal('/[+\\-]/'),
        new IR\Variable('str')
    ]);
    
    $gen = new KotlinGenerator([]);
    $result = $gen->generate($call);
    
    expect($result)->toContain('.split(')
        ->and($result)->toContain('.toRegex()')
        ->and($result)->toContain('.filter { it.isNotEmpty() }');
});

test('KotlinGenerator generates explode', function () {
    $call = new IR\FunctionCall('explode', [
        new IR\Literal(','),
        new IR\Variable('s')
    ]);
    $gen = new KotlinGenerator([]);
    $result = $gen->generate($call);
    expect($result)->toBe('s.split(",")');
});

test('KotlinGenerator generates implode', function () {
    $call = new IR\FunctionCall('implode', [
        new IR\Literal(', '),
        new IR\Variable('arr')
    ]);
    $gen = new KotlinGenerator([]);
    $result = $gen->generate($call);
    expect($result)->toContain('arr.joinToString(separator: ", ")');
});

test('KotlinGenerator generates join', function () {
    $call = new IR\FunctionCall('join', [
        new IR\Literal(', '),
        new IR\Variable('arr')
    ]);
    $gen = new KotlinGenerator([]);
    $result = $gen->generate($call);
    expect($result)->toContain('arr.joinToString(separator: ", ")');
});

test('KotlinGenerator generates str_contains', function () {
    $call = new IR\FunctionCall('str_contains', [
        new IR\Variable('s'),
        new IR\Literal('needle')
    ]);
    $gen = new KotlinGenerator([]);
    $result = $gen->generate($call);
    expect($result)->toBe('s.contains("needle")');
});

test('KotlinGenerator generates str_starts_with', function () {
    $call = new IR\FunctionCall('str_starts_with', [
        new IR\Variable('s'),
        new IR\Literal('prefix')
    ]);
    $gen = new KotlinGenerator([]);
    $result = $gen->generate($call);
    expect($result)->toBe('s.startsWith("prefix")');
});

test('KotlinGenerator generates str_ends_with', function () {
    $call = new IR\FunctionCall('str_ends_with', [
        new IR\Variable('s'),
        new IR\Literal('suffix')
    ]);
    $gen = new KotlinGenerator([]);
    $result = $gen->generate($call);
    expect($result)->toBe('s.endsWith("suffix")');
});

test('KotlinGenerator generates preg_match', function () {
    $call = new IR\FunctionCall('preg_match', [
        new IR\Literal('/^Hello/'),
        new IR\Variable('s')
    ]);
    $gen = new KotlinGenerator([]);
    $result = $gen->generate($call);
    expect($result)->toContain('Regex("/^Hello/").containsMatchIn(s)');
});

test('KotlinGenerator generates array_reduce', function () {
    $call = new IR\FunctionCall('array_reduce', [
        new IR\Variable('arr'),
        new IR\Variable('initial')
    ]);
    $gen = new KotlinGenerator([]);
    $result = $gen->generate($call);
    expect($result)->toContain('arr.reduce(initial)');
});

test('KotlinGenerator generates array_unique', function () {
    $call = new IR\FunctionCall('array_unique', [
        new IR\Variable('arr')
    ]);
    $gen = new KotlinGenerator([]);
    $result = $gen->generate($call);
    expect($result)->toBe('arr.distinct()');
});

test('KotlinGenerator generates array_diff', function () {
    $call = new IR\FunctionCall('array_diff', [
        new IR\Variable('arr1'),
        new IR\Variable('arr2')
    ]);
    $gen = new KotlinGenerator([]);
    $result = $gen->generate($call);
    expect($result)->toBe('arr1.filter { it !in arr2 }');
});

test('KotlinGenerator generates array_combine', function () {
    $call = new IR\FunctionCall('array_combine', [
        new IR\Variable('keys'),
        new IR\Variable('vals')
    ]);
    $gen = new KotlinGenerator([]);
    $result = $gen->generate($call);
    expect($result)->toContain('keys.zip(vals).toMap()');
});

test('KotlinGenerator generates array_intersect', function () {
    $call = new IR\FunctionCall('array_intersect', [
        new IR\Variable('arr1'),
        new IR\Variable('arr2')
    ]);
    $gen = new KotlinGenerator([]);
    $result = $gen->generate($call);
    expect($result)->toBe('arr1.filter { it in arr2 }');
});

test('KotlinGenerator generates array_product', function () {
    $call = new IR\FunctionCall('array_product', [
        new IR\Variable('arr')
    ]);
    $gen = new KotlinGenerator([]);
    $result = $gen->generate($call);
    expect($result)->toContain('.fold(1)');
});
