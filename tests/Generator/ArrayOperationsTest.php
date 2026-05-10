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
// Array Operations Tests (array_pop, array_unshift, array_key_exists, array_reduce, array_unique, array_diff)
// ============================================================

// --- array_pop ---

test('SwiftGenerator generates array_pop', function () {
    $array = new IR\Variable('arr');
    $node = new IR\ArrayPop($array);
    
    $gen = new SwiftGenerator();
    $result = $gen->generate($node);
    
    expect($result)->toBe('(arr as! [Any]).removeLast()');
});

test('KotlinGenerator generates array_pop', function () {
    $array = new IR\Variable('arr');
    $node = new IR\ArrayPop($array);
    
    $gen = new KotlinGenerator();
    $result = $gen->generate($node);
    
    expect($result)->toBe('arr.removeLast()');
});

test('DartGenerator generates array_pop', function () {
    $array = new IR\Variable('arr');
    $node = new IR\ArrayPop($array);
    
    $gen = new DartGenerator();
    $result = $gen->generate($node);
    
    expect($result)->toBe('arr.removeLast()');
});

test('JavaScriptGenerator generates array_pop', function () {
    $array = new IR\Variable('arr');
    $node = new IR\ArrayPop($array);
    
    $gen = new JavaScriptGenerator();
    $result = $gen->generate($node);
    
    expect($result)->toBe('arr.pop()');
});

test('CSharpGenerator generates array_pop', function () {
    $array = new IR\Variable('arr');
    $node = new IR\ArrayPop($array);
    
    $gen = new CSharpGenerator();
    $result = $gen->generate($node);
    
    expect($result)->toBe('arr[^1]');
});

// --- array_unshift ---

test('SwiftGenerator generates array_unshift', function () {
    $array = new IR\Variable('arr');
    $value = new IR\Literal(42);
    $node = new IR\ArrayUnshift($array, $value);
    
    $gen = new SwiftGenerator();
    $result = $gen->generate($node);
    
    expect($result)->toBe('(arr as! [Any]).insert(42, at: 0)');
});

test('KotlinGenerator generates array_unshift', function () {
    $array = new IR\Variable('arr');
    $value = new IR\Literal(42);
    $node = new IR\ArrayUnshift($array, $value);
    
    $gen = new KotlinGenerator();
    $result = $gen->generate($node);
    
    expect($result)->toBe('arr.add(0, 42)');
});

test('DartGenerator generates array_unshift', function () {
    $array = new IR\Variable('arr');
    $value = new IR\Literal(42);
    $node = new IR\ArrayUnshift($array, $value);
    
    $gen = new DartGenerator();
    $result = $gen->generate($node);
    
    expect($result)->toBe('arr.insert(0, 42)');
});

test('JavaScriptGenerator generates array_unshift', function () {
    $array = new IR\Variable('arr');
    $value = new IR\Literal(42);
    $node = new IR\ArrayUnshift($array, $value);
    
    $gen = new JavaScriptGenerator();
    $result = $gen->generate($node);
    
    expect($result)->toBe('arr.unshift(42)');
});

// --- array_key_exists ---

test('SwiftGenerator generates array_key_exists', function () {
    $key = new IR\Literal('foo');
    $array = new IR\Variable('arr');
    $node = new IR\ArrayKeyExists($key, $array);
    
    $gen = new SwiftGenerator();
    $result = $gen->generate($node);
    
    expect($result)->toBe('(arr as! [AnyHashable: Any]).keys.contains("foo")');
});

test('KotlinGenerator generates array_key_exists', function () {
    $key = new IR\Literal('foo');
    $array = new IR\Variable('arr');
    $node = new IR\ArrayKeyExists($key, $array);
    
    $gen = new KotlinGenerator();
    $result = $gen->generate($node);
    
    expect($result)->toBe('(arr is Map) && (arr.keys.contains("foo"))');
});

test('DartGenerator generates array_key_exists', function () {
    $key = new IR\Literal('foo');
    $array = new IR\Variable('arr');
    $node = new IR\ArrayKeyExists($key, $array);
    
    $gen = new DartGenerator();
    $result = $gen->generate($node);
    
    expect($result)->toBe('(arr is Map) && (arr.containsKey("foo"))');
});

test('JavaScriptGenerator generates array_key_exists', function () {
    $key = new IR\Literal('foo');
    $array = new IR\Variable('arr');
    $node = new IR\ArrayKeyExists($key, $array);
    
    $gen = new JavaScriptGenerator();
    $result = $gen->generate($node);
    
    expect($result)->toBe('Object.prototype.hasOwnProperty.call(arr, "foo")');
});

test('CSharpGenerator generates array_key_exists', function () {
    $key = new IR\Literal('foo');
    $array = new IR\Variable('arr');
    $node = new IR\ArrayKeyExists($key, $array);
    
    $gen = new CSharpGenerator();
    $result = $gen->generate($node);
    
    expect($result)->toBe('(arr is IDictionary dict) ? dict.Contains("foo") : false');
});

// --- array_reduce ---

test('SwiftGenerator generates array_reduce', function () {
    $array = new IR\Variable('arr');
    $initial = new IR\Literal(0);
    $node = new IR\ArrayReduce($array, $initial);
    
    $gen = new SwiftGenerator();
    $result = $gen->generate($node);
    
    expect($result)->toBe('(arr as! [Any]).reduce(0) { $0 + $1 }');
});

test('KotlinGenerator generates array_reduce', function () {
    $array = new IR\Variable('arr');
    $initial = new IR\Literal(0);
    $node = new IR\ArrayReduce($array, $initial);
    
    $gen = new KotlinGenerator();
    $result = $gen->generate($node);
    
    expect($result)->toBe('arr.reduce(0) { acc, it -> acc + it }');
});

test('DartGenerator generates array_reduce', function () {
    $array = new IR\Variable('arr');
    $initial = new IR\Literal(0);
    $node = new IR\ArrayReduce($array, $initial);
    
    $gen = new DartGenerator();
    $result = $gen->generate($node);
    
    expect($result)->toBe('arr.reduce((acc, it) => acc + it)');
});

test('JavaScriptGenerator generates array_reduce', function () {
    $array = new IR\Variable('arr');
    $initial = new IR\Literal(0);
    $node = new IR\ArrayReduce($array, $initial);
    
    $gen = new JavaScriptGenerator();
    $result = $gen->generate($node);
    
    expect($result)->toBe('arr.reduce((acc, it) => acc + it, 0)');
});

test('CSharpGenerator generates array_reduce', function () {
    $array = new IR\Variable('arr');
    $initial = new IR\Literal(0);
    $node = new IR\ArrayReduce($array, $initial);
    
    $gen = new CSharpGenerator();
    $result = $gen->generate($node);
    
    expect($result)->toBe('arr.Aggregate(0, (acc, it) => acc + it)');
});

// --- array_unique ---

test('SwiftGenerator generates array_unique', function () {
    $array = new IR\Variable('arr');
    $node = new IR\ArrayUnique($array);
    
    $gen = new SwiftGenerator();
    $result = $gen->generate($node);
    
    expect($result)->toBe('Array(Set(arr as! [Any]))');
});

test('KotlinGenerator generates array_unique', function () {
    $array = new IR\Variable('arr');
    $node = new IR\ArrayUnique($array);
    
    $gen = new KotlinGenerator();
    $result = $gen->generate($node);
    
    expect($result)->toBe('arr.distinct()');
});

test('DartGenerator generates array_unique', function () {
    $array = new IR\Variable('arr');
    $node = new IR\ArrayUnique($array);
    
    $gen = new DartGenerator();
    $result = $gen->generate($node);
    
    expect($result)->toBe('arr.toSet().toList()');
});

test('JavaScriptGenerator generates array_unique', function () {
    $array = new IR\Variable('arr');
    $node = new IR\ArrayUnique($array);
    
    $gen = new JavaScriptGenerator();
    $result = $gen->generate($node);
    
    expect($result)->toBe('[...new Set(arr)]');
});

test('CSharpGenerator generates array_unique', function () {
    $array = new IR\Variable('arr');
    $node = new IR\ArrayUnique($array);
    
    $gen = new CSharpGenerator();
    $result = $gen->generate($node);
    
    expect($result)->toBe('arr.Distinct().ToArray()');
});

// --- array_diff ---

test('SwiftGenerator generates array_diff', function () {
    $array = new IR\Variable('arr');
    $diff = new IR\Variable('other');
    $node = new IR\ArrayDiff($array, $diff);
    
    $gen = new SwiftGenerator();
    $result = $gen->generate($node);
    
    expect($result)->toBe('(arr as! [Any]).filter { !$0.isContainedIn(other as! [Any]) }');
});

test('KotlinGenerator generates array_diff', function () {
    $array = new IR\Variable('arr');
    $diff = new IR\Variable('other');
    $node = new IR\ArrayDiff($array, $diff);
    
    $gen = new KotlinGenerator();
    $result = $gen->generate($node);
    
    expect($result)->toBe('arr.filter { it !in other }');
});

test('DartGenerator generates array_diff', function () {
    $array = new IR\Variable('arr');
    $diff = new IR\Variable('other');
    $node = new IR\ArrayDiff($array, $diff);
    
    $gen = new DartGenerator();
    $result = $gen->generate($node);
    
    expect($result)->toBe('arr.where((it) => !other.contains(it)).toList()');
});

test('JavaScriptGenerator generates array_diff', function () {
    $array = new IR\Variable('arr');
    $diff = new IR\Variable('other');
    $node = new IR\ArrayDiff($array, $diff);
    
    $gen = new JavaScriptGenerator();
    $result = $gen->generate($node);
    
    expect($result)->toBe('arr.filter(it => !other.includes(it))');
});

test('CSharpGenerator generates array_diff', function () {
    $array = new IR\Variable('arr');
    $diff = new IR\Variable('other');
    $node = new IR\ArrayDiff($array, $diff);
    
    $gen = new CSharpGenerator();
    $result = $gen->generate($node);
    
    expect($result)->toBe('arr.Except(other).ToArray()');
});
