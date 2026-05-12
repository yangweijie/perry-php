<?php

declare(strict_types=1);

use Perry\Generator\CSharpGenerator;
use Perry\Generator\DartGenerator;
use Perry\Generator\JavaScriptGenerator;
use Perry\Generator\KotlinGenerator;
use Perry\Generator\SwiftGenerator;
use Perry\IR;

// ============================================================
// P5 Function Tests (abs, round, ceil, floor, max, min,
// array_intersect, array_product, array_count_values,
// array_combine, array_walk, is_null, is_numeric,
// time, uniqid, nl2br)
// ============================================================

// --- abs ---

test('SwiftGenerator generates abs', function () {
    $call = new IR\FunctionCall('abs', [new IR\Literal(-5)]);
    $gen = new SwiftGenerator([]);
    expect($gen->generate($call))->toBe('abs(-5)');
});

test('KotlinGenerator generates abs', function () {
    $call = new IR\FunctionCall('abs', [new IR\Literal(-5)]);
    $gen = new KotlinGenerator();
    expect($gen->generate($call))->toBe('kotlin.math.abs(-5)');
});

test('DartGenerator generates abs', function () {
    $call = new IR\FunctionCall('abs', [new IR\Literal(-5)]);
    $gen = new DartGenerator();
    expect($gen->generate($call))->toBe('-5.abs()');
});

test('JavaScriptGenerator generates abs', function () {
    $call = new IR\FunctionCall('abs', [new IR\Literal(-5)]);
    $gen = new JavaScriptGenerator();
    expect($gen->generate($call))->toBe('Math.abs(-5)');
});

test('CSharpGenerator generates abs', function () {
    $call = new IR\FunctionCall('abs', [new IR\Literal(-5)]);
    $gen = new CSharpGenerator();
    expect($gen->generate($call))->toBe('Math.Abs(-5)');
});

// --- round ---

test('SwiftGenerator generates round', function () {
    $call = new IR\FunctionCall('round', [new IR\Literal(3.7)]);
    $gen = new SwiftGenerator([]);
    expect($gen->generate($call))->toBe('round(3.7)');
});

test('KotlinGenerator generates round', function () {
    $call = new IR\FunctionCall('round', [new IR\Literal(3.7)]);
    $gen = new KotlinGenerator();
    expect($gen->generate($call))->toBe('Math.round(3.7).toInt()');
});

test('DartGenerator generates round', function () {
    $call = new IR\FunctionCall('round', [new IR\Literal(3.7)]);
    $gen = new DartGenerator();
    expect($gen->generate($call))->toBe('3.7.round()');
});

test('JavaScriptGenerator generates round', function () {
    $call = new IR\FunctionCall('round', [new IR\Literal(3.7)]);
    $gen = new JavaScriptGenerator();
    expect($gen->generate($call))->toBe('Math.round(3.7)');
});

test('CSharpGenerator generates round', function () {
    $call = new IR\FunctionCall('round', [new IR\Literal(3.7)]);
    $gen = new CSharpGenerator();
    expect($gen->generate($call))->toBe('Math.Round(3.7f)');
});

// --- ceil ---

test('SwiftGenerator generates ceil', function () {
    $call = new IR\FunctionCall('ceil', [new IR\Literal(3.7)]);
    $gen = new SwiftGenerator([]);
    expect($gen->generate($call))->toBe('ceil(3.7)');
});

test('KotlinGenerator generates ceil', function () {
    $call = new IR\FunctionCall('ceil', [new IR\Literal(3.7)]);
    $gen = new KotlinGenerator();
    expect($gen->generate($call))->toBe('Math.ceil(3.7).toInt()');
});

test('DartGenerator generates ceil', function () {
    $call = new IR\FunctionCall('ceil', [new IR\Literal(3.7)]);
    $gen = new DartGenerator();
    expect($gen->generate($call))->toBe('3.7.ceil()');
});

test('JavaScriptGenerator generates ceil', function () {
    $call = new IR\FunctionCall('ceil', [new IR\Literal(3.7)]);
    $gen = new JavaScriptGenerator();
    expect($gen->generate($call))->toBe('Math.ceil(3.7)');
});

test('CSharpGenerator generates ceil', function () {
    $call = new IR\FunctionCall('ceil', [new IR\Literal(3.7)]);
    $gen = new CSharpGenerator();
    expect($gen->generate($call))->toBe('Math.Ceiling(3.7f)');
});

// --- floor ---

test('SwiftGenerator generates floor', function () {
    $call = new IR\FunctionCall('floor', [new IR\Literal(3.7)]);
    $gen = new SwiftGenerator([]);
    expect($gen->generate($call))->toBe('floor(3.7)');
});

test('KotlinGenerator generates floor', function () {
    $call = new IR\FunctionCall('floor', [new IR\Literal(3.7)]);
    $gen = new KotlinGenerator();
    expect($gen->generate($call))->toBe('Math.floor(3.7).toInt()');
});

test('DartGenerator generates floor', function () {
    $call = new IR\FunctionCall('floor', [new IR\Literal(3.7)]);
    $gen = new DartGenerator();
    expect($gen->generate($call))->toBe('3.7.floor()');
});

test('JavaScriptGenerator generates floor', function () {
    $call = new IR\FunctionCall('floor', [new IR\Literal(3.7)]);
    $gen = new JavaScriptGenerator();
    expect($gen->generate($call))->toBe('Math.floor(3.7)');
});

test('CSharpGenerator generates floor', function () {
    $call = new IR\FunctionCall('floor', [new IR\Literal(3.7)]);
    $gen = new CSharpGenerator();
    expect($gen->generate($call))->toBe('Math.Floor(3.7f)');
});

// --- max ---

test('SwiftGenerator generates max', function () {
    $call = new IR\FunctionCall('max', [new IR\Literal(5), new IR\Literal(10)]);
    $gen = new SwiftGenerator([]);
    expect($gen->generate($call))->toBe('max(5, 10)');
});

test('KotlinGenerator generates max', function () {
    $call = new IR\FunctionCall('max', [new IR\Literal(5), new IR\Literal(10)]);
    $gen = new KotlinGenerator();
    expect($gen->generate($call))->toBe('kotlin.math.max(5, 10)');
});

test('DartGenerator generates max', function () {
    $call = new IR\FunctionCall('max', [new IR\Literal(5), new IR\Literal(10)]);
    $gen = new DartGenerator();
    expect($gen->generate($call))->toBe('(5 > 10) ? 5 : 10');
});

test('JavaScriptGenerator generates max', function () {
    $call = new IR\FunctionCall('max', [new IR\Literal(5), new IR\Literal(10)]);
    $gen = new JavaScriptGenerator();
    expect($gen->generate($call))->toBe('Math.max(5, 10)');
});

test('CSharpGenerator generates max', function () {
    $call = new IR\FunctionCall('max', [new IR\Literal(5), new IR\Literal(10)]);
    $gen = new CSharpGenerator();
    expect($gen->generate($call))->toBe('Math.Max(5, 10)');
});

// --- min ---

test('SwiftGenerator generates min', function () {
    $call = new IR\FunctionCall('min', [new IR\Literal(5), new IR\Literal(10)]);
    $gen = new SwiftGenerator([]);
    expect($gen->generate($call))->toBe('min(5, 10)');
});

test('KotlinGenerator generates min', function () {
    $call = new IR\FunctionCall('min', [new IR\Literal(5), new IR\Literal(10)]);
    $gen = new KotlinGenerator();
    expect($gen->generate($call))->toBe('kotlin.math.min(5, 10)');
});

test('DartGenerator generates min', function () {
    $call = new IR\FunctionCall('min', [new IR\Literal(5), new IR\Literal(10)]);
    $gen = new DartGenerator();
    expect($gen->generate($call))->toBe('(5 < 10) ? 5 : 10');
});

test('JavaScriptGenerator generates min', function () {
    $call = new IR\FunctionCall('min', [new IR\Literal(5), new IR\Literal(10)]);
    $gen = new JavaScriptGenerator();
    expect($gen->generate($call))->toBe('Math.min(5, 10)');
});

test('CSharpGenerator generates min', function () {
    $call = new IR\FunctionCall('min', [new IR\Literal(5), new IR\Literal(10)]);
    $gen = new CSharpGenerator();
    expect($gen->generate($call))->toBe('Math.Min(5, 10)');
});

// --- array_intersect ---

test('SwiftGenerator generates array_intersect', function () {
    $call = new IR\FunctionCall('array_intersect', [new IR\Variable('a'), new IR\Variable('b')]);
    $gen = new SwiftGenerator([]);
    expect($gen->generate($call))->toBe('(a as! [Any]).filter { (b as! [Any]).contains($0) }');
});

test('KotlinGenerator generates array_intersect', function () {
    $call = new IR\FunctionCall('array_intersect', [new IR\Variable('a'), new IR\Variable('b')]);
    $gen = new KotlinGenerator();
    expect($gen->generate($call))->toBe('a.filter { it in b }');
});

test('DartGenerator generates array_intersect', function () {
    $call = new IR\FunctionCall('array_intersect', [new IR\Variable('a'), new IR\Variable('b')]);
    $gen = new DartGenerator();
    expect($gen->generate($call))->toBe('a.where((it) => b.contains(it)).toList()');
});

test('JavaScriptGenerator generates array_intersect', function () {
    $call = new IR\FunctionCall('array_intersect', [new IR\Variable('a'), new IR\Variable('b')]);
    $gen = new JavaScriptGenerator();
    expect($gen->generate($call))->toBe('a.filter(it => b.includes(it))');
});

test('CSharpGenerator generates array_intersect', function () {
    $call = new IR\FunctionCall('array_intersect', [new IR\Variable('a'), new IR\Variable('b')]);
    $gen = new CSharpGenerator();
    expect($gen->generate($call))->toBe('a.Intersect(b).ToArray()');
});

// --- array_product ---

test('SwiftGenerator generates array_product', function () {
    $call = new IR\FunctionCall('array_product', [new IR\Variable('a')]);
    $gen = new SwiftGenerator([]);
    expect($gen->generate($call))->toBe('(a as! [Double]).reduce(1.0, *)');
});

test('KotlinGenerator generates array_product', function () {
    $call = new IR\FunctionCall('array_product', [new IR\Variable('a')]);
    $gen = new KotlinGenerator();
    expect($gen->generate($call))->toBe('a.fold(1) { acc, it -> acc * it }');
});

test('DartGenerator generates array_product', function () {
    $call = new IR\FunctionCall('array_product', [new IR\Variable('a')]);
    $gen = new DartGenerator();
    expect($gen->generate($call))->toBe('a.fold(1, (acc, it) => acc * it)');
});

test('JavaScriptGenerator generates array_product', function () {
    $call = new IR\FunctionCall('array_product', [new IR\Variable('a')]);
    $gen = new JavaScriptGenerator();
    expect($gen->generate($call))->toBe('a.reduce((acc, it) => acc * it, 1)');
});

test('CSharpGenerator generates array_product', function () {
    $call = new IR\FunctionCall('array_product', [new IR\Variable('a')]);
    $gen = new CSharpGenerator();
    expect($gen->generate($call))->toBe('a.Aggregate(1, (acc, it) => acc * it)');
});

// --- array_count_values ---

test('SwiftGenerator generates array_count_values', function () {
    $call = new IR\FunctionCall('array_count_values', [new IR\Variable('a')]);
    $gen = new SwiftGenerator([]);
    expect($gen->generate($call))->toBe('Dictionary(grouping: a as! [AnyHashable], by: { $0 }).mapValues { $0.count }');
});

test('KotlinGenerator generates array_count_values', function () {
    $call = new IR\FunctionCall('array_count_values', [new IR\Variable('a')]);
    $gen = new KotlinGenerator();
    expect($gen->generate($call))->toBe('a.groupBy { it }.mapValues { it.value.size }');
});

test('DartGenerator generates array_count_values', function () {
    $call = new IR\FunctionCall('array_count_values', [new IR\Variable('a')]);
    $gen = new DartGenerator();
    expect($gen->generate($call))->toBe('Map.from(a.fold(<dynamic, int>{}, (Map<dynamic, int> acc, e) => acc..update(e, (v) => v + 1, ifAbsent: () => 1)))');
});

test('JavaScriptGenerator generates array_count_values', function () {
    $call = new IR\FunctionCall('array_count_values', [new IR\Variable('a')]);
    $gen = new JavaScriptGenerator();
    expect($gen->generate($call))->toBe('a.reduce((acc, e) => { acc[e] = (acc[e] || 0) + 1; return acc; }, {})');
});

test('CSharpGenerator generates array_count_values', function () {
    $call = new IR\FunctionCall('array_count_values', [new IR\Variable('a')]);
    $gen = new CSharpGenerator();
    expect($gen->generate($call))->toBe('a.GroupBy(e => e).ToDictionary(g => g.Key, g => g.Count())');
});

// --- array_combine ---

test('SwiftGenerator generates array_combine', function () {
    $call = new IR\FunctionCall('array_combine', [new IR\Variable('k'), new IR\Variable('v')]);
    $gen = new SwiftGenerator([]);
    expect($gen->generate($call))->toBe('Dictionary(uniqueKeysWithValues: zip(k as! [Any], v as! [Any]))');
});

test('KotlinGenerator generates array_combine', function () {
    $call = new IR\FunctionCall('array_combine', [new IR\Variable('k'), new IR\Variable('v')]);
    $gen = new KotlinGenerator();
    expect($gen->generate($call))->toBe('k.zip(v).toMap()');
});

test('DartGenerator generates array_combine', function () {
    $call = new IR\FunctionCall('array_combine', [new IR\Variable('k'), new IR\Variable('v')]);
    $gen = new DartGenerator();
    expect($gen->generate($call))->toBe('Map.fromIterables(k, v)');
});

test('JavaScriptGenerator generates array_combine', function () {
    $call = new IR\FunctionCall('array_combine', [new IR\Variable('k'), new IR\Variable('v')]);
    $gen = new JavaScriptGenerator();
    expect($gen->generate($call))->toBe('Object.fromEntries(k.map((k, i) => [k, v[i]]))');
});

test('CSharpGenerator generates array_combine', function () {
    $call = new IR\FunctionCall('array_combine', [new IR\Variable('k'), new IR\Variable('v')]);
    $gen = new CSharpGenerator();
    expect($gen->generate($call))->toBe('k.Zip(v, (k, v) => new { k, v }).ToDictionary(x => x.k, x => x.v)');
});

// --- array_walk ---

test('SwiftGenerator generates array_walk', function () {
    $call = new IR\FunctionCall('array_walk', [new IR\Variable('arr'), new IR\Variable('func')]);
    $gen = new SwiftGenerator([]);
    expect($gen->generate($call))->toBe('(arr as! [Any]).forEach { func($0) }');
});

test('KotlinGenerator generates array_walk', function () {
    $call = new IR\FunctionCall('array_walk', [new IR\Variable('arr'), new IR\Variable('func')]);
    $gen = new KotlinGenerator();
    expect($gen->generate($call))->toBe('arr.forEach { func(it) }');
});

test('DartGenerator generates array_walk', function () {
    $call = new IR\FunctionCall('array_walk', [new IR\Variable('arr'), new IR\Variable('func')]);
    $gen = new DartGenerator();
    expect($gen->generate($call))->toBe('arr.forEach((e) => func(e))');
});

test('JavaScriptGenerator generates array_walk', function () {
    $call = new IR\FunctionCall('array_walk', [new IR\Variable('arr'), new IR\Variable('func')]);
    $gen = new JavaScriptGenerator();
    expect($gen->generate($call))->toBe('arr.forEach(func)');
});

test('CSharpGenerator generates array_walk', function () {
    $call = new IR\FunctionCall('array_walk', [new IR\Variable('arr'), new IR\Variable('func')]);
    $gen = new CSharpGenerator();
    expect($gen->generate($call))->toBe('arr.ToList().ForEach(e => func(e))');
});

// --- is_null ---

test('SwiftGenerator generates is_null', function () {
    $call = new IR\FunctionCall('is_null', [new IR\Variable('x')]);
    $gen = new SwiftGenerator([]);
    expect($gen->generate($call))->toBe('x == nil');
});

test('KotlinGenerator generates is_null', function () {
    $call = new IR\FunctionCall('is_null', [new IR\Variable('x')]);
    $gen = new KotlinGenerator();
    expect($gen->generate($call))->toBe('x == null');
});

test('DartGenerator generates is_null', function () {
    $call = new IR\FunctionCall('is_null', [new IR\Variable('x')]);
    $gen = new DartGenerator();
    expect($gen->generate($call))->toBe('x == null');
});

test('JavaScriptGenerator generates is_null', function () {
    $call = new IR\FunctionCall('is_null', [new IR\Variable('x')]);
    $gen = new JavaScriptGenerator();
    expect($gen->generate($call))->toBe('x === null');
});

test('CSharpGenerator generates is_null', function () {
    $call = new IR\FunctionCall('is_null', [new IR\Variable('x')]);
    $gen = new CSharpGenerator();
    expect($gen->generate($call))->toBe('x == null');
});

// --- is_numeric ---

test('SwiftGenerator generates is_numeric', function () {
    $call = new IR\FunctionCall('is_numeric', [new IR\Literal(42)]);
    $gen = new SwiftGenerator([]);
    expect($gen->generate($call))->toBe('42 is Int || 42 is Double');
});

test('KotlinGenerator generates is_numeric', function () {
    $call = new IR\FunctionCall('is_numeric', [new IR\Literal(42)]);
    $gen = new KotlinGenerator();
    expect($gen->generate($call))->toBe('42 is Number');
});

test('DartGenerator generates is_numeric', function () {
    $call = new IR\FunctionCall('is_numeric', [new IR\Literal(42)]);
    $gen = new DartGenerator();
    expect($gen->generate($call))->toBe('42 is num');
});

test('JavaScriptGenerator generates is_numeric', function () {
    $call = new IR\FunctionCall('is_numeric', [new IR\Literal(42)]);
    $gen = new JavaScriptGenerator();
    expect($gen->generate($call))->toBe("typeof 42 === 'number'");
});

test('CSharpGenerator generates is_numeric', function () {
    $call = new IR\FunctionCall('is_numeric', [new IR\Literal(42)]);
    $gen = new CSharpGenerator();
    expect($gen->generate($call))->toBe("42 is IConvertible && 42 is not string");
});

// --- time ---

test('SwiftGenerator generates time', function () {
    $call = new IR\FunctionCall('time', []);
    $gen = new SwiftGenerator([]);
    expect($gen->generate($call))->toBe('Int(Date().timeIntervalSince1970)');
});

test('KotlinGenerator generates time', function () {
    $call = new IR\FunctionCall('time', []);
    $gen = new KotlinGenerator();
    expect($gen->generate($call))->toBe('System.currentTimeMillis() / 1000');
});

test('DartGenerator generates time', function () {
    $call = new IR\FunctionCall('time', []);
    $gen = new DartGenerator();
    expect($gen->generate($call))->toBe("DateTime.now().millisecondsSinceEpoch ~/ 1000");
});

test('JavaScriptGenerator generates time', function () {
    $call = new IR\FunctionCall('time', []);
    $gen = new JavaScriptGenerator();
    expect($gen->generate($call))->toBe('Math.floor(Date.now() / 1000)');
});

test('CSharpGenerator generates time', function () {
    $call = new IR\FunctionCall('time', []);
    $gen = new CSharpGenerator();
    expect($gen->generate($call))->toBe('(int)DateTime.UtcNow.Subtract(new DateTime(1970, 1, 1)).TotalSeconds');
});

// --- uniqid ---

test('SwiftGenerator generates uniqid', function () {
    $call = new IR\FunctionCall('uniqid', []);
    $gen = new SwiftGenerator([]);
    expect($gen->generate($call))->toBe('UUID().uuidString');
});

test('KotlinGenerator generates uniqid', function () {
    $call = new IR\FunctionCall('uniqid', []);
    $gen = new KotlinGenerator();
    expect($gen->generate($call))->toBe('UUID.randomUUID().toString()');
});

test('DartGenerator generates uniqid', function () {
    $call = new IR\FunctionCall('uniqid', []);
    $gen = new DartGenerator();
    expect($gen->generate($call))->toBe('DateTime.now().millisecondsSinceEpoch.toString()');
});

test('JavaScriptGenerator generates uniqid', function () {
    $call = new IR\FunctionCall('uniqid', []);
    $gen = new JavaScriptGenerator();
    expect($gen->generate($call))->toBe('Date.now().toString(36) + Math.random().toString(36).substring(2)');
});

test('CSharpGenerator generates uniqid', function () {
    $call = new IR\FunctionCall('uniqid', []);
    $gen = new CSharpGenerator();
    expect($gen->generate($call))->toBe('Guid.NewGuid().ToString()');
});

// --- nl2br ---

test('SwiftGenerator generates nl2br', function () {
    $call = new IR\FunctionCall('nl2br', [new IR\Literal('hello')]);
    $gen = new SwiftGenerator([]);
    expect($gen->generate($call))->toBe('"hello".replacingOccurrences(of: "\\n", with: "<br>")');
});

test('KotlinGenerator generates nl2br', function () {
    $call = new IR\FunctionCall('nl2br', [new IR\Literal('hello')]);
    $gen = new KotlinGenerator();
    expect($gen->generate($call))->toBe('"hello".replace("\n", "<br>")');
});

test('DartGenerator generates nl2br', function () {
    $call = new IR\FunctionCall('nl2br', [new IR\Literal('hello')]);
    $gen = new DartGenerator();
    expect($gen->generate($call))->toBe('"hello".replaceAll(\'\\n\', \'<br>\')');
});

test('JavaScriptGenerator generates nl2br', function () {
    $call = new IR\FunctionCall('nl2br', [new IR\Literal('hello')]);
    $gen = new JavaScriptGenerator();
    expect($gen->generate($call))->toBe('"hello".replace(/\n/g, \'<br>\')');
});

test('CSharpGenerator generates nl2br', function () {
    $call = new IR\FunctionCall('nl2br', [new IR\Literal('hello')]);
    $gen = new CSharpGenerator();
    expect($gen->generate($call))->toBe('"hello".Replace("\n", "<br>")');
});
