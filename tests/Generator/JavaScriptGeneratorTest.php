<?php

use Perry\Generator\JavaScriptGenerator;
use Perry\IR;

test('JavaScriptGenerator generates let declaration', function () {
    $literal = new IR\Literal(0);
    $assign = new IR\Assignment('count', $literal);
    
    $gen = new JavaScriptGenerator([]);
    $result = $gen->generate($assign);
    
    expect($result)->toBe('let count = 0');
});

test('JavaScriptGenerator generates state var assignment', function () {
    $literal = new IR\Literal('0');
    $assign = new IR\Assignment('display', $literal);
    
    $gen = new JavaScriptGenerator(['display']);
    $result = $gen->generate($assign);
    
    expect($result)->toBe('state.display = "0"');
});

test('JavaScriptGenerator generates parseFloat', function () {
    $call = new IR\FunctionCall('floatval', [
        new IR\Variable('x')
    ]);
    
    $gen = new JavaScriptGenerator([]);
    $result = $gen->generate($call);
    
    expect($result)->toBe('parseFloat(x)');
});

test('JavaScriptGenerator generates parseInt', function () {
    $call = new IR\FunctionCall('intval', [
        new IR\Variable('x')
    ]);
    
    $gen = new JavaScriptGenerator([]);
    $result = $gen->generate($call);
    
    expect($result)->toBe('parseInt(x)');
});

test('JavaScriptGenerator generates length', function () {
    $call = new IR\FunctionCall('strlen', [
        new IR\Variable('s')
    ]);
    
    $gen = new JavaScriptGenerator([]);
    $result = $gen->generate($call);
    
    expect($result)->toBe('s.length');
});

test('JavaScriptGenerator generates includes', function () {
    $call = new IR\FunctionCall('in_array', [
        new IR\Variable('val'),
        new IR\Variable('arr')
    ]);
    
    $gen = new JavaScriptGenerator([]);
    $result = $gen->generate($call);
    
    expect($result)->toBe('arr.includes(val)');
});

test('JavaScriptGenerator generates indexOf', function () {
    $call = new IR\FunctionCall('strpos', [
        new IR\Variable('s'),
        new IR\Literal('x')
    ]);
    
    $gen = new JavaScriptGenerator([]);
    $result = $gen->generate($call);
    
    expect($result)->toBe('(() => { const _i = s.indexOf("x"); return _i === -1 ? false : _i; })()');
});

test('JavaScriptGenerator generates Math.floor', function () {
    $call = new IR\FunctionCall('floor', [
        new IR\Variable('x')
    ]);
    
    $gen = new JavaScriptGenerator([]);
    $result = $gen->generate($call);
    
    expect($result)->toBe('Math.floor(x)');
});

test('JavaScriptGenerator generates substr slice', function () {
    $call = new IR\FunctionCall('substr', [
        new IR\Variable('s'),
        new IR\Literal(-1)
    ]);
    
    $gen = new JavaScriptGenerator([]);
    $result = $gen->generate($call);
    
    expect($result)->toBe('s.slice(-1)');
});

test('JavaScriptGenerator generates JSON.parse', function () {
    $call = new IR\FunctionCall('json_decode', [new IR\Variable('s')]);
    $gen = new JavaScriptGenerator([]);
    expect($gen->generate($call))->toBe('JSON.parse(s)');
});

test('JavaScriptGenerator generates JSON.stringify', function () {
    $call = new IR\FunctionCall('json_encode', [new IR\Variable('v')]);
    $gen = new JavaScriptGenerator([]);
    expect($gen->generate($call))->toBe('JSON.stringify(v)');
});

test('JavaScriptGenerator generates is_null', function () {
    $call = new IR\FunctionCall('is_null', [new IR\Variable('x')]);
    $gen = new JavaScriptGenerator([]);
    expect($gen->generate($call))->toBe('x === null');
});

test('JavaScriptGenerator generates is_array', function () {
    $call = new IR\FunctionCall('is_array', [new IR\Variable('x')]);
    $gen = new JavaScriptGenerator([]);
    expect($gen->generate($call))->toBe('Array.isArray(x)');
});

test('JavaScriptGenerator generates toFixed', function () {
    $call = new IR\FunctionCall('number_format', [
        new IR\Variable('n'),
        new IR\Literal(2)
    ]);
    
    $gen = new JavaScriptGenerator([]);
    $result = $gen->generate($call);
    
    expect($result)->toBe('n.toFixed(2)');
});
