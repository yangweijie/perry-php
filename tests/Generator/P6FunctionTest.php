<?php

declare(strict_types=1);

use Perry\Generator\CSharpGenerator;
use Perry\Generator\DartGenerator;
use Perry\Generator\JavaScriptGenerator;
use Perry\Generator\KotlinGenerator;
use Perry\Generator\SwiftGenerator;
use Perry\IR;

// ============================================================
// P6 Function Tests (addslashes, stripslashes, str_split,
// str_ireplace, substr_replace, array_change_key_case,
// array_replace, array_intersect_key, array_diff_key,
// array_udiff, pow, exp, pi, microtime, intval, floatval)
// ============================================================

// --- addslashes ---

test('SwiftGenerator generates addslashes', function () {
    $call = new IR\FunctionCall('addslashes', [new IR\Variable('x')]);
    $gen = new SwiftGenerator([]);
    expect($gen->generate($call))->toBe('x.replacingOccurrences(of: "\\\\", with: "\\\\\\\\").replacingOccurrences(of: "\'", with: "\\\\\'").replacingOccurrences(of: "\\"", with: "\\\\\\"")');
});

test('KotlinGenerator generates addslashes', function () {
    $call = new IR\FunctionCall('addslashes', [new IR\Variable('x')]);
    $gen = new KotlinGenerator();
    expect($gen->generate($call))->toBe('x.replace("\\\\", "\\\\\\\\").replace("\'", "\\\\\'").replace("\\"", "\\\\\\"")');
});

test('DartGenerator generates addslashes', function () {
    $call = new IR\FunctionCall('addslashes', [new IR\Variable('x')]);
    $gen = new DartGenerator();
    expect($gen->generate($call))->toBe('x.replaceAll("\\\\", "\\\\\\\\").replaceAll("\'", "\\\\\'").replaceAll("\\"", "\\\\\\"")');
});

test('JavaScriptGenerator generates addslashes', function () {
    $call = new IR\FunctionCall('addslashes', [new IR\Variable('x')]);
    $gen = new JavaScriptGenerator();
    expect($gen->generate($call))->toBe('x.replace(/\\\\/g, "\\\\\\\\").replace(/\'/g, "\\\\\'").replace(/\\x22/g, \'\\\\"\')');
});

test('CSharpGenerator generates addslashes', function () {
    $call = new IR\FunctionCall('addslashes', [new IR\Variable('x')]);
    $gen = new CSharpGenerator();
    expect($gen->generate($call))->toBe('x.Replace("\\\\", "\\\\\\\\").Replace("\'", "\\\\\'").Replace("\\"", "\\\\\\"")');
});

// --- stripslashes ---

test('SwiftGenerator generates stripslashes', function () {
    $call = new IR\FunctionCall('stripslashes', [new IR\Variable('x')]);
    $gen = new SwiftGenerator([]);
    expect($gen->generate($call))->toBe('x.replacingOccurrences(of: "\\\\\'", with: "\'").replacingOccurrences(of: "\\\\\\"", with: "\\"").replacingOccurrences(of: "\\\\\\\\", with: "\\\\")');
});

test('KotlinGenerator generates stripslashes', function () {
    $call = new IR\FunctionCall('stripslashes', [new IR\Variable('x')]);
    $gen = new KotlinGenerator();
    expect($gen->generate($call))->toBe('x.replace("\\\\\'", "\'").replace("\\\\\\"", "\\"").replace("\\\\\\\\", "\\\\")');
});

test('DartGenerator generates stripslashes', function () {
    $call = new IR\FunctionCall('stripslashes', [new IR\Variable('x')]);
    $gen = new DartGenerator();
    expect($gen->generate($call))->toBe('x.replaceAll("\\\\\'", "\'").replaceAll("\\\\\\"", "\\"").replaceAll("\\\\\\\\", "\\\\")');
});

test('JavaScriptGenerator generates stripslashes', function () {
    $call = new IR\FunctionCall('stripslashes', [new IR\Variable('x')]);
    $gen = new JavaScriptGenerator();
    expect($gen->generate($call))->toBe('x.replace(/\\\\\'/g, "\'").replace(/\\\\"/g, \'"\').replace(/\\\\\\\\/g, "\\\\")');
});

test('CSharpGenerator generates stripslashes', function () {
    $call = new IR\FunctionCall('stripslashes', [new IR\Variable('x')]);
    $gen = new CSharpGenerator();
    expect($gen->generate($call))->toBe('x.Replace("\\\\\'", "\'").Replace("\\\\\\"", "\\"").Replace("\\\\\\\\", "\\\\")');
});

// --- str_split ---

test('SwiftGenerator generates str_split', function () {
    $call = new IR\FunctionCall('str_split', [new IR\Variable('x')]);
    $gen = new SwiftGenerator([]);
    expect($gen->generate($call))->toBe('x.map { String($0) }');
});

test('KotlinGenerator generates str_split', function () {
    $call = new IR\FunctionCall('str_split', [new IR\Variable('x')]);
    $gen = new KotlinGenerator();
    expect($gen->generate($call))->toBe('x.map { it.toString() }');
});

test('DartGenerator generates str_split', function () {
    $call = new IR\FunctionCall('str_split', [new IR\Variable('x')]);
    $gen = new DartGenerator();
    expect($gen->generate($call))->toBe("x.split('')");
});

test('JavaScriptGenerator generates str_split', function () {
    $call = new IR\FunctionCall('str_split', [new IR\Variable('x')]);
    $gen = new JavaScriptGenerator();
    expect($gen->generate($call))->toBe("x.split('')");
});

test('CSharpGenerator generates str_split', function () {
    $call = new IR\FunctionCall('str_split', [new IR\Variable('x')]);
    $gen = new CSharpGenerator();
    expect($gen->generate($call))->toBe('x.Select(c => c.ToString()).ToArray()');
});

// --- str_ireplace ---

test('SwiftGenerator generates str_ireplace', function () {
    $call = new IR\FunctionCall('str_ireplace', [new IR\Variable('x'), new IR\Variable('y'), new IR\Variable('z')]);
    $gen = new SwiftGenerator([]);
    expect($gen->generate($call))->toBe('x.replacingOccurrences(of: y, with: z, options: .caseInsensitive)');
});

test('KotlinGenerator generates str_ireplace', function () {
    $call = new IR\FunctionCall('str_ireplace', [new IR\Variable('x'), new IR\Variable('y'), new IR\Variable('z')]);
    $gen = new KotlinGenerator();
    expect($gen->generate($call))->toBe('x.replace(Regex(y, RegexOption.IGNORE_CASE), z)');
});

test('DartGenerator generates str_ireplace', function () {
    $call = new IR\FunctionCall('str_ireplace', [new IR\Variable('x'), new IR\Variable('y'), new IR\Variable('z')]);
    $gen = new DartGenerator();
    expect($gen->generate($call))->toBe('x.replaceAll(RegExp(y, caseSensitive: false), z)');
});

test('JavaScriptGenerator generates str_ireplace', function () {
    $call = new IR\FunctionCall('str_ireplace', [new IR\Variable('x'), new IR\Variable('y'), new IR\Variable('z')]);
    $gen = new JavaScriptGenerator();
    expect($gen->generate($call))->toBe("x.replace(new RegExp(y, 'gi'), z)");
});

test('CSharpGenerator generates str_ireplace', function () {
    $call = new IR\FunctionCall('str_ireplace', [new IR\Variable('x'), new IR\Variable('y'), new IR\Variable('z')]);
    $gen = new CSharpGenerator();
    expect($gen->generate($call))->toBe('Regex.Replace(x, y, z, RegexOptions.IgnoreCase)');
});

// --- substr_replace ---

test('SwiftGenerator generates substr_replace', function () {
    $call = new IR\FunctionCall('substr_replace', [new IR\Variable('x'), new IR\Variable('y'), new IR\Variable('z')]);
    $gen = new SwiftGenerator([]);
    expect($gen->generate($call))->toBe('String(x.prefix(y)) + z');
});

test('KotlinGenerator generates substr_replace', function () {
    $call = new IR\FunctionCall('substr_replace', [new IR\Variable('x'), new IR\Variable('y'), new IR\Variable('z')]);
    $gen = new KotlinGenerator();
    expect($gen->generate($call))->toBe('x.take(y) + z');
});

test('DartGenerator generates substr_replace', function () {
    $call = new IR\FunctionCall('substr_replace', [new IR\Variable('x'), new IR\Variable('y'), new IR\Variable('z')]);
    $gen = new DartGenerator();
    expect($gen->generate($call))->toBe('x.substring(0, y) + z');
});

test('JavaScriptGenerator generates substr_replace', function () {
    $call = new IR\FunctionCall('substr_replace', [new IR\Variable('x'), new IR\Variable('y'), new IR\Variable('z')]);
    $gen = new JavaScriptGenerator();
    expect($gen->generate($call))->toBe('x.slice(0, y) + z');
});

test('CSharpGenerator generates substr_replace', function () {
    $call = new IR\FunctionCall('substr_replace', [new IR\Variable('x'), new IR\Variable('y'), new IR\Variable('z')]);
    $gen = new CSharpGenerator();
    expect($gen->generate($call))->toBe('x.Substring(0, y) + z');
});

// --- array_change_key_case ---

test('SwiftGenerator generates array_change_key_case', function () {
    $call = new IR\FunctionCall('array_change_key_case', [new IR\Variable('a')]);
    $gen = new SwiftGenerator([]);
    expect($gen->generate($call))->toBe('Dictionary(uniqueKeysWithValues: (a as! [String: Any]).map { ($0.key.lowercased(), $0.value) })');
});

test('KotlinGenerator generates array_change_key_case', function () {
    $call = new IR\FunctionCall('array_change_key_case', [new IR\Variable('a')]);
    $gen = new KotlinGenerator();
    expect($gen->generate($call))->toBe('a.entries.associate { it.key.lowercase() to it.value }');
});

test('DartGenerator generates array_change_key_case', function () {
    $call = new IR\FunctionCall('array_change_key_case', [new IR\Variable('a')]);
    $gen = new DartGenerator();
    expect($gen->generate($call))->toBe('Map.fromIterable(a.entries, key: (e) => e.key.toString().toLowerCase(), value: (e) => e.value)');
});

test('JavaScriptGenerator generates array_change_key_case', function () {
    $call = new IR\FunctionCall('array_change_key_case', [new IR\Variable('a')]);
    $gen = new JavaScriptGenerator();
    expect($gen->generate($call))->toBe('Object.fromEntries(Object.entries(a).map(([k, v]) => [k.toLowerCase(), v]))');
});

test('CSharpGenerator generates array_change_key_case', function () {
    $call = new IR\FunctionCall('array_change_key_case', [new IR\Variable('a')]);
    $gen = new CSharpGenerator();
    expect($gen->generate($call))->toBe('a.ToDictionary(kvp => kvp.Key.ToString().ToLower(), kvp => kvp.Value)');
});

// --- array_replace ---

test('SwiftGenerator generates array_replace', function () {
    $call = new IR\FunctionCall('array_replace', [new IR\Variable('a'), new IR\Variable('b')]);
    $gen = new SwiftGenerator([]);
    expect($gen->generate($call))->toBe('(a as! [String: Any]) + (b as! [String: Any])');
});

test('KotlinGenerator generates array_replace', function () {
    $call = new IR\FunctionCall('array_replace', [new IR\Variable('a'), new IR\Variable('b')]);
    $gen = new KotlinGenerator();
    expect($gen->generate($call))->toBe('a + b');
});

test('DartGenerator generates array_replace', function () {
    $call = new IR\FunctionCall('array_replace', [new IR\Variable('a'), new IR\Variable('b')]);
    $gen = new DartGenerator();
    expect($gen->generate($call))->toBe('{...a, ...b}');
});

test('JavaScriptGenerator generates array_replace', function () {
    $call = new IR\FunctionCall('array_replace', [new IR\Variable('a'), new IR\Variable('b')]);
    $gen = new JavaScriptGenerator();
    expect($gen->generate($call))->toBe('Object.assign({}, a, b)');
});

test('CSharpGenerator generates array_replace', function () {
    $call = new IR\FunctionCall('array_replace', [new IR\Variable('a'), new IR\Variable('b')]);
    $gen = new CSharpGenerator();
    expect($gen->generate($call))->toBe('a.Concat(b).GroupBy(kvp => kvp.Key).ToDictionary(g => g.Key, g => g.Last().Value)');
});

// --- array_intersect_key ---

test('SwiftGenerator generates array_intersect_key', function () {
    $call = new IR\FunctionCall('array_intersect_key', [new IR\Variable('a'), new IR\Variable('b')]);
    $gen = new SwiftGenerator([]);
    expect($gen->generate($call))->toBe('Dictionary(uniqueKeysWithValues: (a as! [String: Any]).filter { b[$0.key] != nil })');
});

test('KotlinGenerator generates array_intersect_key', function () {
    $call = new IR\FunctionCall('array_intersect_key', [new IR\Variable('a'), new IR\Variable('b')]);
    $gen = new KotlinGenerator();
    expect($gen->generate($call))->toBe('a.filterKeys { it in b.keys }');
});

test('DartGenerator generates array_intersect_key', function () {
    $call = new IR\FunctionCall('array_intersect_key', [new IR\Variable('a'), new IR\Variable('b')]);
    $gen = new DartGenerator();
    expect($gen->generate($call))->toBe('Map.fromIterable(a.entries.where((e) => b.containsKey(e.key)), key: (e) => e.key, value: (e) => e.value)');
});

test('JavaScriptGenerator generates array_intersect_key', function () {
    $call = new IR\FunctionCall('array_intersect_key', [new IR\Variable('a'), new IR\Variable('b')]);
    $gen = new JavaScriptGenerator();
    expect($gen->generate($call))->toBe('Object.fromEntries(Object.entries(a).filter(([k]) => k in b))');
});

test('CSharpGenerator generates array_intersect_key', function () {
    $call = new IR\FunctionCall('array_intersect_key', [new IR\Variable('a'), new IR\Variable('b')]);
    $gen = new CSharpGenerator();
    expect($gen->generate($call))->toBe('a.Where(kvp => b.ContainsKey(kvp.Key)).ToDictionary(kvp => kvp.Key, kvp => kvp.Value)');
});

// --- array_diff_key ---

test('SwiftGenerator generates array_diff_key', function () {
    $call = new IR\FunctionCall('array_diff_key', [new IR\Variable('a'), new IR\Variable('b')]);
    $gen = new SwiftGenerator([]);
    expect($gen->generate($call))->toBe('Dictionary(uniqueKeysWithValues: (a as! [String: Any]).filter { b[$0.key] == nil })');
});

test('KotlinGenerator generates array_diff_key', function () {
    $call = new IR\FunctionCall('array_diff_key', [new IR\Variable('a'), new IR\Variable('b')]);
    $gen = new KotlinGenerator();
    expect($gen->generate($call))->toBe('a.filterKeys { it !in b.keys }');
});

test('DartGenerator generates array_diff_key', function () {
    $call = new IR\FunctionCall('array_diff_key', [new IR\Variable('a'), new IR\Variable('b')]);
    $gen = new DartGenerator();
    expect($gen->generate($call))->toBe('Map.fromIterable(a.entries.where((e) => !b.containsKey(e.key)), key: (e) => e.key, value: (e) => e.value)');
});

test('JavaScriptGenerator generates array_diff_key', function () {
    $call = new IR\FunctionCall('array_diff_key', [new IR\Variable('a'), new IR\Variable('b')]);
    $gen = new JavaScriptGenerator();
    expect($gen->generate($call))->toBe('Object.fromEntries(Object.entries(a).filter(([k]) => !(k in b)))');
});

test('CSharpGenerator generates array_diff_key', function () {
    $call = new IR\FunctionCall('array_diff_key', [new IR\Variable('a'), new IR\Variable('b')]);
    $gen = new CSharpGenerator();
    expect($gen->generate($call))->toBe('a.Where(kvp => !b.ContainsKey(kvp.Key)).ToDictionary(kvp => kvp.Key, kvp => kvp.Value)');
});

// --- array_udiff ---

test('SwiftGenerator generates array_udiff', function () {
    $call = new IR\FunctionCall('array_udiff', [new IR\Variable('a'), new IR\Variable('b'), new IR\Variable('fn')]);
    $gen = new SwiftGenerator([]);
    expect($gen->generate($call))->toBe('(a as! [Any]).filter { a in !(b as! [Any]).contains { b in fn(a, b) == 0 } }');
});

test('KotlinGenerator generates array_udiff', function () {
    $call = new IR\FunctionCall('array_udiff', [new IR\Variable('a'), new IR\Variable('b'), new IR\Variable('fn')]);
    $gen = new KotlinGenerator();
    expect($gen->generate($call))->toBe('a.filter { a -> !b.any { b -> fn(a, b) == 0 } }');
});

test('DartGenerator generates array_udiff', function () {
    $call = new IR\FunctionCall('array_udiff', [new IR\Variable('a'), new IR\Variable('b'), new IR\Variable('fn')]);
    $gen = new DartGenerator();
    expect($gen->generate($call))->toBe('a.where((a) => !b.any((b) => fn(a, b) == 0)).toList()');
});

test('JavaScriptGenerator generates array_udiff', function () {
    $call = new IR\FunctionCall('array_udiff', [new IR\Variable('a'), new IR\Variable('b'), new IR\Variable('fn')]);
    $gen = new JavaScriptGenerator();
    expect($gen->generate($call))->toBe('a.filter(a => !b.some(b => fn(a, b) === 0))');
});

test('CSharpGenerator generates array_udiff', function () {
    $call = new IR\FunctionCall('array_udiff', [new IR\Variable('a'), new IR\Variable('b'), new IR\Variable('fn')]);
    $gen = new CSharpGenerator();
    expect($gen->generate($call))->toBe('a.Where(a => !b.Any(b => fn(a, b) == 0)).ToArray()');
});

// --- pow ---

test('SwiftGenerator generates pow', function () {
    $call = new IR\FunctionCall('pow', [new IR\Literal(2), new IR\Literal(3)]);
    $gen = new SwiftGenerator([]);
    expect($gen->generate($call))->toBe('pow(2, 3)');
});

test('KotlinGenerator generates pow', function () {
    $call = new IR\FunctionCall('pow', [new IR\Literal(2), new IR\Literal(3)]);
    $gen = new KotlinGenerator();
    expect($gen->generate($call))->toBe('Math.pow(2, 3)');
});

test('DartGenerator generates pow', function () {
    $call = new IR\FunctionCall('pow', [new IR\Literal(2), new IR\Literal(3)]);
    $gen = new DartGenerator();
    expect($gen->generate($call))->toBe('Math.pow(2, 3)');
});

test('JavaScriptGenerator generates pow', function () {
    $call = new IR\FunctionCall('pow', [new IR\Literal(2), new IR\Literal(3)]);
    $gen = new JavaScriptGenerator();
    expect($gen->generate($call))->toBe('Math.pow(2, 3)');
});

test('CSharpGenerator generates pow', function () {
    $call = new IR\FunctionCall('pow', [new IR\Literal(2), new IR\Literal(3)]);
    $gen = new CSharpGenerator();
    expect($gen->generate($call))->toBe('Math.Pow(2, 3)');
});

// --- exp ---

test('SwiftGenerator generates exp', function () {
    $call = new IR\FunctionCall('exp', [new IR\Literal(1)]);
    $gen = new SwiftGenerator([]);
    expect($gen->generate($call))->toBe('exp(1)');
});

test('KotlinGenerator generates exp', function () {
    $call = new IR\FunctionCall('exp', [new IR\Literal(1)]);
    $gen = new KotlinGenerator();
    expect($gen->generate($call))->toBe('Math.exp(1)');
});

test('DartGenerator generates exp', function () {
    $call = new IR\FunctionCall('exp', [new IR\Literal(1)]);
    $gen = new DartGenerator();
    expect($gen->generate($call))->toBe('Math.exp(1)');
});

test('JavaScriptGenerator generates exp', function () {
    $call = new IR\FunctionCall('exp', [new IR\Literal(1)]);
    $gen = new JavaScriptGenerator();
    expect($gen->generate($call))->toBe('Math.exp(1)');
});

test('CSharpGenerator generates exp', function () {
    $call = new IR\FunctionCall('exp', [new IR\Literal(1)]);
    $gen = new CSharpGenerator();
    expect($gen->generate($call))->toBe('Math.Exp(1)');
});

// --- pi ---

test('SwiftGenerator generates pi', function () {
    $call = new IR\FunctionCall('pi', []);
    $gen = new SwiftGenerator([]);
    expect($gen->generate($call))->toBe('Double.pi');
});

test('KotlinGenerator generates pi', function () {
    $call = new IR\FunctionCall('pi', []);
    $gen = new KotlinGenerator();
    expect($gen->generate($call))->toBe('Math.PI');
});

test('DartGenerator generates pi', function () {
    $call = new IR\FunctionCall('pi', []);
    $gen = new DartGenerator();
    expect($gen->generate($call))->toBe('pi');
});

test('JavaScriptGenerator generates pi', function () {
    $call = new IR\FunctionCall('pi', []);
    $gen = new JavaScriptGenerator();
    expect($gen->generate($call))->toBe('Math.PI');
});

test('CSharpGenerator generates pi', function () {
    $call = new IR\FunctionCall('pi', []);
    $gen = new CSharpGenerator();
    expect($gen->generate($call))->toBe('Math.PI');
});

// --- microtime ---

test('SwiftGenerator generates microtime', function () {
    $call = new IR\FunctionCall('microtime', []);
    $gen = new SwiftGenerator([]);
    expect($gen->generate($call))->toBe('Date().timeIntervalSince1970');
});

test('KotlinGenerator generates microtime', function () {
    $call = new IR\FunctionCall('microtime', []);
    $gen = new KotlinGenerator();
    expect($gen->generate($call))->toBe('System.currentTimeMillis() / 1000.0');
});

test('DartGenerator generates microtime', function () {
    $call = new IR\FunctionCall('microtime', []);
    $gen = new DartGenerator();
    expect($gen->generate($call))->toBe('DateTime.now().millisecondsSinceEpoch / 1000.0');
});

test('JavaScriptGenerator generates microtime', function () {
    $call = new IR\FunctionCall('microtime', []);
    $gen = new JavaScriptGenerator();
    expect($gen->generate($call))->toBe('Date.now() / 1000');
});

test('CSharpGenerator generates microtime', function () {
    $call = new IR\FunctionCall('microtime', []);
    $gen = new CSharpGenerator();
    expect($gen->generate($call))->toBe('(DateTime.UtcNow - new DateTime(1970, 1, 1)).TotalSeconds');
});

// --- intval ---

test('SwiftGenerator generates intval', function () {
    $call = new IR\FunctionCall('intval', [new IR\Literal(42)]);
    $gen = new SwiftGenerator([]);
    expect($gen->generate($call))->toBe('(Int(42) ?? 0)');
    $call = new IR\FunctionCall('intval', [new IR\Literal('42')]);
    $gen = new KotlinGenerator([]);
    expect($gen->generate($call))->toBe('((("42") as? Number)?.toInt() ?: 0)');
});

test('DartGenerator generates intval', function () {
    $call = new IR\FunctionCall('intval', [new IR\Literal(42)]);
    $gen = new DartGenerator();
    expect($gen->generate($call))->toBe('(int.tryParse(42.toString()) ?? 0)');
});

test('JavaScriptGenerator generates intval', function () {
    $call = new IR\FunctionCall('intval', [new IR\Literal(42)]);
    $gen = new JavaScriptGenerator();
    expect($gen->generate($call))->toBe('(parseInt(42, 10) || 0)');
});

test('CSharpGenerator generates intval', function () {
    $call = new IR\FunctionCall('intval', [new IR\Literal(42)]);
    $gen = new CSharpGenerator();
    expect($gen->generate($call))->toBe('Convert.ToInt32(42)');
});

// --- floatval ---

test('SwiftGenerator generates floatval', function () {
    $call = new IR\FunctionCall('floatval', [new IR\Literal(42)]);
    $gen = new SwiftGenerator([]);
    expect($gen->generate($call))->toBe('(Double(42) ?? 0.0)');
    $call = new IR\FunctionCall('floatval', [new IR\Literal('42')]);
    $gen = new KotlinGenerator([]);
    expect($gen->generate($call))->toBe('((("42") as? Number)?.toDouble() ?: 0.0)');
});

test('DartGenerator generates floatval', function () {
    $call = new IR\FunctionCall('floatval', [new IR\Literal(42)]);
    $gen = new DartGenerator();
    expect($gen->generate($call))->toBe('(double.tryParse(42.toString()) ?? 0.0)');
});

test('JavaScriptGenerator generates floatval', function () {
    $call = new IR\FunctionCall('floatval', [new IR\Literal(42)]);
    $gen = new JavaScriptGenerator();
    expect($gen->generate($call))->toBe('(parseFloat(42) || 0.0)');
});

test('CSharpGenerator generates floatval', function () {
    $call = new IR\FunctionCall('floatval', [new IR\Literal(42)]);
    $gen = new CSharpGenerator();
    expect($gen->generate($call))->toBe('Convert.ToDouble(42)');
});
