<?php

declare(strict_types=1);

use Perry\Generator\CSharpGenerator;
use Perry\Generator\DartGenerator;
use Perry\Generator\JavaScriptGenerator;
use Perry\Generator\KotlinGenerator;
use Perry\Generator\SwiftGenerator;
use Perry\IR;

// ============================================================
// Extended Function Tests (chr, ord, strrev, str_shuffle, str_word_count,
// array_chunk, array_splice, array_pad, current, compact)
// ============================================================

// --- chr ---

test('SwiftGenerator generates chr', function () {
    $call = new IR\FunctionCall('chr', [new IR\Literal(65)]);
    $gen = new SwiftGenerator([]);
    expect($gen->generate($call))->toBe('String(UnicodeScalar(Int(65) ?? 0)!)');
});

test('KotlinGenerator generates chr', function () {
    $call = new IR\FunctionCall('chr', [new IR\Literal(65)]);
    $gen = new KotlinGenerator();
    expect($gen->generate($call))->toBe('(65).toChar()');
});

test('DartGenerator generates chr', function () {
    $call = new IR\FunctionCall('chr', [new IR\Literal(65)]);
    $gen = new DartGenerator();
    expect($gen->generate($call))->toBe('String.fromCharCode(65)');
});

test('JavaScriptGenerator generates chr', function () {
    $call = new IR\FunctionCall('chr', [new IR\Literal(65)]);
    $gen = new JavaScriptGenerator();
    expect($gen->generate($call))->toBe('String.fromCharCode(65)');
});

test('CSharpGenerator generates chr', function () {
    $call = new IR\FunctionCall('chr', [new IR\Literal(65)]);
    $gen = new CSharpGenerator();
    expect($gen->generate($call))->toBe('((char)(int)65).ToString()');
});

// --- ord ---

test('SwiftGenerator generates ord', function () {
    $call = new IR\FunctionCall('ord', [new IR\Literal('A')]);
    $gen = new SwiftGenerator([]);
    expect($gen->generate($call))->toBe('Int("A".unicodeScalars.first?.value ?? 0)');
});

test('KotlinGenerator generates ord', function () {
    $call = new IR\FunctionCall('ord', [new IR\Literal('A')]);
    $gen = new KotlinGenerator();
    expect($gen->generate($call))->toBe('"A".first().code');
});

test('DartGenerator generates ord', function () {
    $call = new IR\FunctionCall('ord', [new IR\Literal('A')]);
    $gen = new DartGenerator();
    expect($gen->generate($call))->toBe('"A".codeUnitAt(0)');
});

test('JavaScriptGenerator generates ord', function () {
    $call = new IR\FunctionCall('ord', [new IR\Literal('A')]);
    $gen = new JavaScriptGenerator();
    expect($gen->generate($call))->toBe('"A".charCodeAt(0)');
});

test('CSharpGenerator generates ord', function () {
    $call = new IR\FunctionCall('ord', [new IR\Literal('A')]);
    $gen = new CSharpGenerator();
    expect($gen->generate($call))->toBe('(int)"A"[0]');
});

// --- strrev ---

test('SwiftGenerator generates strrev', function () {
    $call = new IR\FunctionCall('strrev', [new IR\Variable('s')]);
    $gen = new SwiftGenerator([]);
    expect($gen->generate($call))->toBe('String(s.reversed())');
});

test('KotlinGenerator generates strrev', function () {
    $call = new IR\FunctionCall('strrev', [new IR\Variable('s')]);
    $gen = new KotlinGenerator();
    expect($gen->generate($call))->toBe('s.reversed()');
});

test('DartGenerator generates strrev', function () {
    $call = new IR\FunctionCall('strrev', [new IR\Variable('s')]);
    $gen = new DartGenerator();
    expect($gen->generate($call))->toBe("s.split('').reversed.join('')");
});

test('JavaScriptGenerator generates strrev', function () {
    $call = new IR\FunctionCall('strrev', [new IR\Variable('s')]);
    $gen = new JavaScriptGenerator();
    expect($gen->generate($call))->toBe("s.split('').reverse().join('')");
});

// --- str_shuffle ---

test('SwiftGenerator generates str_shuffle', function () {
    $call = new IR\FunctionCall('str_shuffle', [new IR\Variable('s')]);
    $gen = new SwiftGenerator([]);
    expect($gen->generate($call))->toBe('String(s.shuffled())');
});

test('KotlinGenerator generates str_shuffle', function () {
    $call = new IR\FunctionCall('str_shuffle', [new IR\Variable('s')]);
    $gen = new KotlinGenerator();
    expect($gen->generate($call))->toContain('shuffle');
});

test('DartGenerator generates str_shuffle', function () {
    $call = new IR\FunctionCall('str_shuffle', [new IR\Variable('s')]);
    $gen = new DartGenerator();
    expect($gen->generate($call))->toContain('shuffle');
});

test('JavaScriptGenerator generates str_shuffle', function () {
    $call = new IR\FunctionCall('str_shuffle', [new IR\Variable('s')]);
    $gen = new JavaScriptGenerator();
    expect($gen->generate($call))->toContain('Math.random');
});

// --- str_word_count ---

test('SwiftGenerator generates str_word_count', function () {
    $call = new IR\FunctionCall('str_word_count', [new IR\Variable('s')]);
    $gen = new SwiftGenerator([]);
    expect($gen->generate($call))->toContain('components(separatedBy:');
});

test('KotlinGenerator generates str_word_count', function () {
    $call = new IR\FunctionCall('str_word_count', [new IR\Variable('s')]);
    $gen = new KotlinGenerator();
    expect($gen->generate($call))->toContain('split');
});

test('DartGenerator generates str_word_count', function () {
    $call = new IR\FunctionCall('str_word_count', [new IR\Variable('s')]);
    $gen = new DartGenerator();
    expect($gen->generate($call))->toContain('RegExp');
});

test('JavaScriptGenerator generates str_word_count', function () {
    $call = new IR\FunctionCall('str_word_count', [new IR\Variable('s')]);
    $gen = new JavaScriptGenerator();
    expect($gen->generate($call))->toContain('split');
});

test('CSharpGenerator generates str_word_count', function () {
    $call = new IR\FunctionCall('str_word_count', [new IR\Variable('s')]);
    $gen = new CSharpGenerator();
    expect($gen->generate($call))->toContain('StringSplitOptions');
});

// --- array_chunk ---

test('SwiftGenerator generates array_chunk', function () {
    $call = new IR\FunctionCall('array_chunk', [new IR\Variable('arr'), new IR\Literal(3)]);
    $gen = new SwiftGenerator([]);
    expect($gen->generate($call))->toContain('stride');
});

test('KotlinGenerator generates array_chunk', function () {
    $call = new IR\FunctionCall('array_chunk', [new IR\Variable('arr'), new IR\Literal(3)]);
    $gen = new KotlinGenerator();
    expect($gen->generate($call))->toBe('arr.chunked(Int(3) ?: 1)');
});

test('DartGenerator generates array_chunk', function () {
    $call = new IR\FunctionCall('array_chunk', [new IR\Variable('arr'), new IR\Literal(3)]);
    $gen = new DartGenerator();
    expect($gen->generate($call))->toContain('sublist');
});

test('JavaScriptGenerator generates array_chunk', function () {
    $call = new IR\FunctionCall('array_chunk', [new IR\Variable('arr'), new IR\Literal(3)]);
    $gen = new JavaScriptGenerator();
    expect($gen->generate($call))->toContain('Array.from');
});

test('CSharpGenerator generates array_chunk', function () {
    $call = new IR\FunctionCall('array_chunk', [new IR\Variable('arr'), new IR\Literal(3)]);
    $gen = new CSharpGenerator();
    expect($gen->generate($call))->toContain('GroupBy');
});

// --- array_splice ---

test('SwiftGenerator generates array_splice', function () {
    $call = new IR\FunctionCall('array_splice', [new IR\Variable('arr'), new IR\Literal(2)]);
    $gen = new SwiftGenerator([]);
    expect($gen->generate($call))->toContain('..<');
});

test('KotlinGenerator generates array_splice', function () {
    $call = new IR\FunctionCall('array_splice', [new IR\Variable('arr'), new IR\Literal(2)]);
    $gen = new KotlinGenerator();
    expect($gen->generate($call))->toContain('slice');
});

test('DartGenerator generates array_splice', function () {
    $call = new IR\FunctionCall('array_splice', [new IR\Variable('arr'), new IR\Literal(2)]);
    $gen = new DartGenerator();
    expect($gen->generate($call))->toContain('sublist');
});

test('JavaScriptGenerator generates array_splice', function () {
    $call = new IR\FunctionCall('array_splice', [new IR\Variable('arr'), new IR\Literal(2)]);
    $gen = new JavaScriptGenerator();
    expect($gen->generate($call))->toContain('slice');
});

test('CSharpGenerator generates array_splice', function () {
    $call = new IR\FunctionCall('array_splice', [new IR\Variable('arr'), new IR\Literal(2)]);
    $gen = new CSharpGenerator();
    expect($gen->generate($call))->toContain('Concat');
});

// --- array_pad ---

test('SwiftGenerator generates array_pad', function () {
    $call = new IR\FunctionCall('array_pad', [new IR\Variable('arr'), new IR\Literal(5), new IR\Literal(0)]);
    $gen = new SwiftGenerator([]);
    expect($gen->generate($call))->toContain('padding(toLength:');
});

test('KotlinGenerator generates array_pad', function () {
    $call = new IR\FunctionCall('array_pad', [new IR\Variable('arr'), new IR\Literal(5), new IR\Literal(0)]);
    $gen = new KotlinGenerator();
    expect($gen->generate($call))->toContain('padTo');
});

test('DartGenerator generates array_pad', function () {
    $call = new IR\FunctionCall('array_pad', [new IR\Variable('arr'), new IR\Literal(5), new IR\Literal(0)]);
    $gen = new DartGenerator();
    expect($gen->generate($call))->toContain('List.filled');
});

test('JavaScriptGenerator generates array_pad', function () {
    $call = new IR\FunctionCall('array_pad', [new IR\Variable('arr'), new IR\Literal(5), new IR\Literal(0)]);
    $gen = new JavaScriptGenerator();
    expect($gen->generate($call))->toContain('Array(');
});

test('CSharpGenerator generates array_pad', function () {
    $call = new IR\FunctionCall('array_pad', [new IR\Variable('arr'), new IR\Literal(5), new IR\Literal(0)]);
    $gen = new CSharpGenerator();
    expect($gen->generate($call))->toContain('Enumerable.Repeat');
});

// --- current ---

test('SwiftGenerator generates current', function () {
    $call = new IR\FunctionCall('current', [new IR\Variable('arr')]);
    $gen = new SwiftGenerator([]);
    expect($gen->generate($call))->toBe('(arr as! [Any]).first');
});

test('KotlinGenerator generates current', function () {
    $call = new IR\FunctionCall('current', [new IR\Variable('arr')]);
    $gen = new KotlinGenerator();
    expect($gen->generate($call))->toBe('arr.firstOrNull()');
});

test('DartGenerator generates current', function () {
    $call = new IR\FunctionCall('current', [new IR\Variable('arr')]);
    $gen = new DartGenerator();
    expect($gen->generate($call))->toBe('arr.first');
});

test('JavaScriptGenerator generates current', function () {
    $call = new IR\FunctionCall('current', [new IR\Variable('arr')]);
    $gen = new JavaScriptGenerator();
    expect($gen->generate($call))->toBe('arr[0]');
});

test('CSharpGenerator generates current', function () {
    $call = new IR\FunctionCall('current', [new IR\Variable('arr')]);
    $gen = new CSharpGenerator();
    expect($gen->generate($call))->toBe('arr.FirstOrDefault()');
});

// --- compact ---

test('SwiftGenerator generates compact', function () {
    $call = new IR\FunctionCall('compact', [new IR\Variable('name'), new IR\Variable('age')]);
    $gen = new SwiftGenerator([]);
    $result = $gen->generate($call);
    expect($result)->toContain('name');
});

test('KotlinGenerator generates compact', function () {
    $call = new IR\FunctionCall('compact', [new IR\Variable('name'), new IR\Variable('age')]);
    $gen = new KotlinGenerator();
    $result = $gen->generate($call);
    expect($result)->toContain('name');
});

test('DartGenerator generates compact', function () {
    $call = new IR\FunctionCall('compact', [new IR\Variable('name'), new IR\Variable('age')]);
    $gen = new DartGenerator();
    $result = $gen->generate($call);
    expect($result)->toContain('name');
});

test('JavaScriptGenerator generates compact', function () {
    $call = new IR\FunctionCall('compact', [new IR\Variable('name'), new IR\Variable('age')]);
    $gen = new JavaScriptGenerator();
    $result = $gen->generate($call);
    expect($result)->toContain('name');
});

test('CSharpGenerator generates compact', function () {
    $call = new IR\FunctionCall('compact', [new IR\Variable('name'), new IR\Variable('age')]);
    $gen = new CSharpGenerator();
    $result = $gen->generate($call);
    expect($result)->toContain('name');
});
