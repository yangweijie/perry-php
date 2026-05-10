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

test('CSharpGenerator generates Math.Ceiling', function () {
    $call = new IR\FunctionCall('ceil', [new IR\Variable('x')]);
    $gen = new CSharpGenerator([]);
    expect($gen->generate($call))->toBe('Math.Ceiling(x)');
});

test('CSharpGenerator generates Math.Round', function () {
    $call = new IR\FunctionCall('round', [new IR\Variable('x')]);
    $gen = new CSharpGenerator([]);
    expect($gen->generate($call))->toBe('Math.Round(x)');
});

test('CSharpGenerator generates .Length for count', function () {
    $call = new IR\FunctionCall('count', [new IR\Variable('arr')]);
    $gen = new CSharpGenerator([]);
    expect($gen->generate($call))->toBe('arr.Length');
});

test('CSharpGenerator generates .Add for array_push', function () {
    $call = new IR\FunctionCall('array_push', [new IR\Variable('arr'), new IR\Variable('v')]);
    $gen = new CSharpGenerator([]);
    expect($gen->generate($call))->toBe('arr.Add(v)');
});

test('CSharpGenerator generates json_decode', function () {
    $call = new IR\FunctionCall('json_decode', [new IR\Variable('s')]);
    $gen = new CSharpGenerator([]);
    expect($gen->generate($call))->toBe('System.Text.Json.JsonSerializer.Deserialize(s)');
});

test('CSharpGenerator generates json_encode', function () {
    $call = new IR\FunctionCall('json_encode', [new IR\Variable('v')]);
    $gen = new CSharpGenerator([]);
    expect($gen->generate($call))->toBe('System.Text.Json.JsonSerializer.Serialize(v)');
});

test('CSharpGenerator generates is_null', function () {
    $call = new IR\FunctionCall('is_null', [new IR\Variable('x')]);
    $gen = new CSharpGenerator([]);
    expect($gen->generate($call))->toBe('x == null');
});

test('CSharpGenerator generates is_array', function () {
    $call = new IR\FunctionCall('is_array', [new IR\Variable('x')]);
    $gen = new CSharpGenerator([]);
    expect($gen->generate($call))->toBe('x is Array');
});

test('CSharpGenerator generates array type cast', function () {
    $cast = new IR\Cast('array', new IR\Variable('x'));
    $gen = new CSharpGenerator([]);
    expect($gen->generate($cast))->toBe('(x).ToArray()');
});

test('CSharpGenerator generates object type cast', function () {
    $cast = new IR\Cast('object', new IR\Variable('x'));
    $gen = new CSharpGenerator([]);
    expect($gen->generate($cast))->toBe('(x) as object');
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

test('CSharpGenerator generates explode', function () {
    $call = new IR\FunctionCall('explode', [
        new IR\Literal(','),
        new IR\Variable('s')
    ]);
    $gen = new CSharpGenerator([]);
    $result = $gen->generate($call);
    expect($result)->toBe('s.Split(",")');
});

test('CSharpGenerator generates implode', function () {
    $call = new IR\FunctionCall('implode', [
        new IR\Literal(', '),
        new IR\Variable('arr')
    ]);
    $gen = new CSharpGenerator([]);
    $result = $gen->generate($call);
    expect($result)->toBe('string.Join(", ", arr)');
});

test('CSharpGenerator generates join', function () {
    $call = new IR\FunctionCall('join', [
        new IR\Literal(', '),
        new IR\Variable('arr')
    ]);
    $gen = new CSharpGenerator([]);
    $result = $gen->generate($call);
    expect($result)->toBe('string.Join(", ", arr)');
});

test('CSharpGenerator generates str_contains', function () {
    $call = new IR\FunctionCall('str_contains', [
        new IR\Variable('s'),
        new IR\Literal('needle')
    ]);
    $gen = new CSharpGenerator([]);
    $result = $gen->generate($call);
    expect($result)->toBe('s.Contains("needle")');
});

test('CSharpGenerator generates str_starts_with', function () {
    $call = new IR\FunctionCall('str_starts_with', [
        new IR\Variable('s'),
        new IR\Literal('prefix')
    ]);
    $gen = new CSharpGenerator([]);
    $result = $gen->generate($call);
    expect($result)->toBe('s.StartsWith("prefix")');
});

test('CSharpGenerator generates str_ends_with', function () {
    $call = new IR\FunctionCall('str_ends_with', [
        new IR\Variable('s'),
        new IR\Literal('suffix')
    ]);
    $gen = new CSharpGenerator([]);
    $result = $gen->generate($call);
    expect($result)->toBe('s.EndsWith("suffix")');
});

test('CSharpGenerator generates preg_match', function () {
    $call = new IR\FunctionCall('preg_match', [
        new IR\Literal('/^Hello/'),
        new IR\Variable('s')
    ]);
    $gen = new CSharpGenerator([]);
    $result = $gen->generate($call);
    expect($result)->toBe('Regex.IsMatch(s, "/^Hello/")');
});

test('CSharpGenerator generates array_reduce', function () {
    $call = new IR\FunctionCall('array_reduce', [
        new IR\Variable('arr'),
        new IR\Variable('initial')
    ]);
    $gen = new CSharpGenerator([]);
    $result = $gen->generate($call);
    expect($result)->toContain('arr.Aggregate(initial, (acc, it) => acc + it)');
});

test('CSharpGenerator generates array_unique', function () {
    $call = new IR\FunctionCall('array_unique', [
        new IR\Variable('arr')
    ]);
    $gen = new CSharpGenerator([]);
    $result = $gen->generate($call);
    expect($result)->toBe('arr.Distinct().ToArray()');
});

test('CSharpGenerator generates array_diff', function () {
    $call = new IR\FunctionCall('array_diff', [
        new IR\Variable('arr1'),
        new IR\Variable('arr2')
    ]);
    $gen = new CSharpGenerator([]);
    $result = $gen->generate($call);
    expect($result)->toBe('arr1.Except(arr2).ToArray()');
});

test('CSharpGenerator generates array_combine', function () {
    $call = new IR\FunctionCall('array_combine', [
        new IR\Variable('keys'),
        new IR\Variable('vals')
    ]);
    $gen = new CSharpGenerator([]);
    $result = $gen->generate($call);
    expect($result)->toContain('keys.Zip(vals, (k, v) => new { k, v }).ToDictionary(x => x.k, x => x.v)');
});

test('CSharpGenerator generates array_intersect', function () {
    $call = new IR\FunctionCall('array_intersect', [
        new IR\Variable('arr1'),
        new IR\Variable('arr2')
    ]);
    $gen = new CSharpGenerator([]);
    $result = $gen->generate($call);
    expect($result)->toBe('arr1.Intersect(arr2).ToArray()');
});

test('CSharpGenerator generates array_product', function () {
    $call = new IR\FunctionCall('array_product', [
        new IR\Variable('arr')
    ]);
    $gen = new CSharpGenerator([]);
    $result = $gen->generate($call);
    expect($result)->toContain('arr.Aggregate(1, (acc, it) => acc * it)');
});
