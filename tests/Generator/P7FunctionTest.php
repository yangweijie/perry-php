<?php

declare(strict_types=1);

use Perry\Generator\CSharpGenerator;
use Perry\Generator\DartGenerator;
use Perry\Generator\JavaScriptGenerator;
use Perry\Generator\KotlinGenerator;
use Perry\Generator\SwiftGenerator;
use Perry\IR;

// ============================================================
// P7 Function Tests (decbin, dechex, decoct, bindec, hexdec,
// octdec, intdiv, fmod, hypot, deg2rad, rad2deg, is_finite,
// is_infinite, is_nan, is_scalar, array_key_first)
// ============================================================

// --- decbin ---

test('SwiftGenerator generates decbin', function () {
    $call = new IR\FunctionCall('decbin', [new IR\Variable('x')]);
    $gen = new SwiftGenerator([]);
    expect($gen->generate($call))->toBe('String(x, radix: 2)');
});

test('KotlinGenerator generates decbin', function () {
    $call = new IR\FunctionCall('decbin', [new IR\Variable('x')]);
    $gen = new KotlinGenerator();
    expect($gen->generate($call))->toBe('(x as Int).toString(2)');
});

test('DartGenerator generates decbin', function () {
    $call = new IR\FunctionCall('decbin', [new IR\Variable('x')]);
    $gen = new DartGenerator();
    expect($gen->generate($call))->toBe('x.toRadixString(2)');
});

test('JavaScriptGenerator generates decbin', function () {
    $call = new IR\FunctionCall('decbin', [new IR\Variable('x')]);
    $gen = new JavaScriptGenerator();
    expect($gen->generate($call))->toBe('(x >>> 0).toString(2)');
});

test('CSharpGenerator generates decbin', function () {
    $call = new IR\FunctionCall('decbin', [new IR\Variable('x')]);
    $gen = new CSharpGenerator();
    expect($gen->generate($call))->toBe('Convert.ToString(x, 2)');
});

// --- dechex ---

test('SwiftGenerator generates dechex', function () {
    $call = new IR\FunctionCall('dechex', [new IR\Variable('x')]);
    $gen = new SwiftGenerator([]);
    expect($gen->generate($call))->toBe('String(x, radix: 16)');
});

test('KotlinGenerator generates dechex', function () {
    $call = new IR\FunctionCall('dechex', [new IR\Variable('x')]);
    $gen = new KotlinGenerator();
    expect($gen->generate($call))->toBe('(x as Int).toString(16)');
});

test('DartGenerator generates dechex', function () {
    $call = new IR\FunctionCall('dechex', [new IR\Variable('x')]);
    $gen = new DartGenerator();
    expect($gen->generate($call))->toBe('x.toRadixString(16)');
});

test('JavaScriptGenerator generates dechex', function () {
    $call = new IR\FunctionCall('dechex', [new IR\Variable('x')]);
    $gen = new JavaScriptGenerator();
    expect($gen->generate($call))->toBe('(x >>> 0).toString(16)');
});

test('CSharpGenerator generates dechex', function () {
    $call = new IR\FunctionCall('dechex', [new IR\Variable('x')]);
    $gen = new CSharpGenerator();
    expect($gen->generate($call))->toBe('Convert.ToString(x, 16)');
});

// --- decoct ---

test('SwiftGenerator generates decoct', function () {
    $call = new IR\FunctionCall('decoct', [new IR\Variable('x')]);
    $gen = new SwiftGenerator([]);
    expect($gen->generate($call))->toBe('String(x, radix: 8)');
});

test('KotlinGenerator generates decoct', function () {
    $call = new IR\FunctionCall('decoct', [new IR\Variable('x')]);
    $gen = new KotlinGenerator();
    expect($gen->generate($call))->toBe('(x as Int).toString(8)');
});

test('DartGenerator generates decoct', function () {
    $call = new IR\FunctionCall('decoct', [new IR\Variable('x')]);
    $gen = new DartGenerator();
    expect($gen->generate($call))->toBe('x.toRadixString(8)');
});

test('JavaScriptGenerator generates decoct', function () {
    $call = new IR\FunctionCall('decoct', [new IR\Variable('x')]);
    $gen = new JavaScriptGenerator();
    expect($gen->generate($call))->toBe('(x >>> 0).toString(8)');
});

test('CSharpGenerator generates decoct', function () {
    $call = new IR\FunctionCall('decoct', [new IR\Variable('x')]);
    $gen = new CSharpGenerator();
    expect($gen->generate($call))->toBe('Convert.ToString(x, 8)');
});

// --- bindec ---

test('SwiftGenerator generates bindec', function () {
    $call = new IR\FunctionCall('bindec', [new IR\Variable('x')]);
    $gen = new SwiftGenerator([]);
    expect($gen->generate($call))->toBe('Int(x, radix: 2) ?? 0');
});

test('KotlinGenerator generates bindec', function () {
    $call = new IR\FunctionCall('bindec', [new IR\Variable('x')]);
    $gen = new KotlinGenerator();
    expect($gen->generate($call))->toBe('(x as String).toIntOrNull(2) ?: 0');
});

test('DartGenerator generates bindec', function () {
    $call = new IR\FunctionCall('bindec', [new IR\Variable('x')]);
    $gen = new DartGenerator();
    expect($gen->generate($call))->toBe('int.tryParse(x.toString(), radix: 2) ?? 0');
});

test('JavaScriptGenerator generates bindec', function () {
    $call = new IR\FunctionCall('bindec', [new IR\Variable('x')]);
    $gen = new JavaScriptGenerator();
    expect($gen->generate($call))->toBe('parseInt(x, 2) || 0');
});

test('CSharpGenerator generates bindec', function () {
    $call = new IR\FunctionCall('bindec', [new IR\Variable('x')]);
    $gen = new CSharpGenerator();
    expect($gen->generate($call))->toBe('Convert.ToInt32(x, 2)');
});

// --- hexdec ---

test('SwiftGenerator generates hexdec', function () {
    $call = new IR\FunctionCall('hexdec', [new IR\Variable('x')]);
    $gen = new SwiftGenerator([]);
    expect($gen->generate($call))->toBe('Int(x, radix: 16) ?? 0');
});

test('KotlinGenerator generates hexdec', function () {
    $call = new IR\FunctionCall('hexdec', [new IR\Variable('x')]);
    $gen = new KotlinGenerator();
    expect($gen->generate($call))->toBe('(x as String).toIntOrNull(16) ?: 0');
});

test('DartGenerator generates hexdec', function () {
    $call = new IR\FunctionCall('hexdec', [new IR\Variable('x')]);
    $gen = new DartGenerator();
    expect($gen->generate($call))->toBe('int.tryParse(x.toString(), radix: 16) ?? 0');
});

test('JavaScriptGenerator generates hexdec', function () {
    $call = new IR\FunctionCall('hexdec', [new IR\Variable('x')]);
    $gen = new JavaScriptGenerator();
    expect($gen->generate($call))->toBe('parseInt(x, 16) || 0');
});

test('CSharpGenerator generates hexdec', function () {
    $call = new IR\FunctionCall('hexdec', [new IR\Variable('x')]);
    $gen = new CSharpGenerator();
    expect($gen->generate($call))->toBe('Convert.ToInt32(x, 16)');
});

// --- octdec ---

test('SwiftGenerator generates octdec', function () {
    $call = new IR\FunctionCall('octdec', [new IR\Variable('x')]);
    $gen = new SwiftGenerator([]);
    expect($gen->generate($call))->toBe('Int(x, radix: 8) ?? 0');
});

test('KotlinGenerator generates octdec', function () {
    $call = new IR\FunctionCall('octdec', [new IR\Variable('x')]);
    $gen = new KotlinGenerator();
    expect($gen->generate($call))->toBe('(x as String).toIntOrNull(8) ?: 0');
});

test('DartGenerator generates octdec', function () {
    $call = new IR\FunctionCall('octdec', [new IR\Variable('x')]);
    $gen = new DartGenerator();
    expect($gen->generate($call))->toBe('int.tryParse(x.toString(), radix: 8) ?? 0');
});

test('JavaScriptGenerator generates octdec', function () {
    $call = new IR\FunctionCall('octdec', [new IR\Variable('x')]);
    $gen = new JavaScriptGenerator();
    expect($gen->generate($call))->toBe('parseInt(x, 8) || 0');
});

test('CSharpGenerator generates octdec', function () {
    $call = new IR\FunctionCall('octdec', [new IR\Variable('x')]);
    $gen = new CSharpGenerator();
    expect($gen->generate($call))->toBe('Convert.ToInt32(x, 8)');
});

// --- intdiv ---

test('SwiftGenerator generates intdiv', function () {
    $call = new IR\FunctionCall('intdiv', [new IR\Variable('x'), new IR\Variable('y')]);
    $gen = new SwiftGenerator([]);
    expect($gen->generate($call))->toBe('Int(x) / Int(y)');
});

test('KotlinGenerator generates intdiv', function () {
    $call = new IR\FunctionCall('intdiv', [new IR\Variable('x'), new IR\Variable('y')]);
    $gen = new KotlinGenerator();
    expect($gen->generate($call))->toBe('(x as Int) / (y as Int)');
});

test('DartGenerator generates intdiv', function () {
    $call = new IR\FunctionCall('intdiv', [new IR\Variable('x'), new IR\Variable('y')]);
    $gen = new DartGenerator();
    expect($gen->generate($call))->toBe('x ~/ y');
});

test('JavaScriptGenerator generates intdiv', function () {
    $call = new IR\FunctionCall('intdiv', [new IR\Variable('x'), new IR\Variable('y')]);
    $gen = new JavaScriptGenerator();
    expect($gen->generate($call))->toBe('Math.floor(x / y)');
});

test('CSharpGenerator generates intdiv', function () {
    $call = new IR\FunctionCall('intdiv', [new IR\Variable('x'), new IR\Variable('y')]);
    $gen = new CSharpGenerator();
    expect($gen->generate($call))->toBe('Convert.ToInt32(x) / Convert.ToInt32(y)');
});

// --- fmod ---

test('SwiftGenerator generates fmod', function () {
    $call = new IR\FunctionCall('fmod', [new IR\Variable('x'), new IR\Variable('y')]);
    $gen = new SwiftGenerator([]);
    expect($gen->generate($call))->toBe('(x as! Double).truncatingRemainder(dividingBy: y as! Double)');
});

test('KotlinGenerator generates fmod', function () {
    $call = new IR\FunctionCall('fmod', [new IR\Variable('x'), new IR\Variable('y')]);
    $gen = new KotlinGenerator();
    expect($gen->generate($call))->toBe('x % y');
});

test('DartGenerator generates fmod', function () {
    $call = new IR\FunctionCall('fmod', [new IR\Variable('x'), new IR\Variable('y')]);
    $gen = new DartGenerator();
    expect($gen->generate($call))->toBe('x % y');
});

test('JavaScriptGenerator generates fmod', function () {
    $call = new IR\FunctionCall('fmod', [new IR\Variable('x'), new IR\Variable('y')]);
    $gen = new JavaScriptGenerator();
    expect($gen->generate($call))->toBe('x % y');
});

test('CSharpGenerator generates fmod', function () {
    $call = new IR\FunctionCall('fmod', [new IR\Variable('x'), new IR\Variable('y')]);
    $gen = new CSharpGenerator();
    expect($gen->generate($call))->toBe('x % y');
});

// --- hypot ---

test('SwiftGenerator generates hypot', function () {
    $call = new IR\FunctionCall('hypot', [new IR\Variable('x'), new IR\Variable('y')]);
    $gen = new SwiftGenerator([]);
    expect($gen->generate($call))->toBe('hypot(x, y)');
});

test('KotlinGenerator generates hypot', function () {
    $call = new IR\FunctionCall('hypot', [new IR\Variable('x'), new IR\Variable('y')]);
    $gen = new KotlinGenerator();
    expect($gen->generate($call))->toBe('kotlin.math.hypot(x, y)');
});

test('DartGenerator generates hypot', function () {
    $call = new IR\FunctionCall('hypot', [new IR\Variable('x'), new IR\Variable('y')]);
    $gen = new DartGenerator();
    expect($gen->generate($call))->toBe('sqrt(x * x + y * y)');
});

test('JavaScriptGenerator generates hypot', function () {
    $call = new IR\FunctionCall('hypot', [new IR\Variable('x'), new IR\Variable('y')]);
    $gen = new JavaScriptGenerator();
    expect($gen->generate($call))->toBe('Math.hypot(x, y)');
});

test('CSharpGenerator generates hypot', function () {
    $call = new IR\FunctionCall('hypot', [new IR\Variable('x'), new IR\Variable('y')]);
    $gen = new CSharpGenerator();
    expect($gen->generate($call))->toBe('Math.Sqrt(x * x + y * y)');
});

// --- deg2rad ---

test('SwiftGenerator generates deg2rad', function () {
    $call = new IR\FunctionCall('deg2rad', [new IR\Variable('x')]);
    $gen = new SwiftGenerator([]);
    expect($gen->generate($call))->toBe('x * (.pi / 180.0)');
});

test('KotlinGenerator generates deg2rad', function () {
    $call = new IR\FunctionCall('deg2rad', [new IR\Variable('x')]);
    $gen = new KotlinGenerator();
    expect($gen->generate($call))->toBe('Math.toRadians(x)');
});

test('DartGenerator generates deg2rad', function () {
    $call = new IR\FunctionCall('deg2rad', [new IR\Variable('x')]);
    $gen = new DartGenerator();
    expect($gen->generate($call))->toBe('x * (pi / 180.0)');
});

test('JavaScriptGenerator generates deg2rad', function () {
    $call = new IR\FunctionCall('deg2rad', [new IR\Variable('x')]);
    $gen = new JavaScriptGenerator();
    expect($gen->generate($call))->toBe('x * (Math.PI / 180)');
});

test('CSharpGenerator generates deg2rad', function () {
    $call = new IR\FunctionCall('deg2rad', [new IR\Variable('x')]);
    $gen = new CSharpGenerator();
    expect($gen->generate($call))->toBe('x * (Math.PI / 180.0)');
});

// --- rad2deg ---

test('SwiftGenerator generates rad2deg', function () {
    $call = new IR\FunctionCall('rad2deg', [new IR\Variable('x')]);
    $gen = new SwiftGenerator([]);
    expect($gen->generate($call))->toBe('x * (180.0 / .pi)');
});

test('KotlinGenerator generates rad2deg', function () {
    $call = new IR\FunctionCall('rad2deg', [new IR\Variable('x')]);
    $gen = new KotlinGenerator();
    expect($gen->generate($call))->toBe('Math.toDegrees(x)');
});

test('DartGenerator generates rad2deg', function () {
    $call = new IR\FunctionCall('rad2deg', [new IR\Variable('x')]);
    $gen = new DartGenerator();
    expect($gen->generate($call))->toBe('x * (180.0 / pi)');
});

test('JavaScriptGenerator generates rad2deg', function () {
    $call = new IR\FunctionCall('rad2deg', [new IR\Variable('x')]);
    $gen = new JavaScriptGenerator();
    expect($gen->generate($call))->toBe('x * (180 / Math.PI)');
});

test('CSharpGenerator generates rad2deg', function () {
    $call = new IR\FunctionCall('rad2deg', [new IR\Variable('x')]);
    $gen = new CSharpGenerator();
    expect($gen->generate($call))->toBe('x * (180.0 / Math.PI)');
});

// --- is_finite ---

test('SwiftGenerator generates is_finite', function () {
    $call = new IR\FunctionCall('is_finite', [new IR\Variable('x')]);
    $gen = new SwiftGenerator([]);
    expect($gen->generate($call))->toBe('(x as? Double)?.isFinite ?? false');
});

test('KotlinGenerator generates is_finite', function () {
    $call = new IR\FunctionCall('is_finite', [new IR\Variable('x')]);
    $gen = new KotlinGenerator();
    expect($gen->generate($call))->toBe('(x as? Double)?.isFinite() ?: false');
});

test('DartGenerator generates is_finite', function () {
    $call = new IR\FunctionCall('is_finite', [new IR\Variable('x')]);
    $gen = new DartGenerator();
    expect($gen->generate($call))->toBe('x is double && x.isFinite');
});

test('JavaScriptGenerator generates is_finite', function () {
    $call = new IR\FunctionCall('is_finite', [new IR\Variable('x')]);
    $gen = new JavaScriptGenerator();
    expect($gen->generate($call))->toBe('typeof x === \'number\' && isFinite(x)');
});

test('CSharpGenerator generates is_finite', function () {
    $call = new IR\FunctionCall('is_finite', [new IR\Variable('x')]);
    $gen = new CSharpGenerator();
    expect($gen->generate($call))->toBe('double.IsFinite(Convert.ToDouble(x))');
});

// --- is_infinite ---

test('SwiftGenerator generates is_infinite', function () {
    $call = new IR\FunctionCall('is_infinite', [new IR\Variable('x')]);
    $gen = new SwiftGenerator([]);
    expect($gen->generate($call))->toBe('(x as? Double)?.isInfinite ?? false');
});

test('KotlinGenerator generates is_infinite', function () {
    $call = new IR\FunctionCall('is_infinite', [new IR\Variable('x')]);
    $gen = new KotlinGenerator();
    expect($gen->generate($call))->toBe('(x as? Double)?.isInfinite() ?: false');
});

test('DartGenerator generates is_infinite', function () {
    $call = new IR\FunctionCall('is_infinite', [new IR\Variable('x')]);
    $gen = new DartGenerator();
    expect($gen->generate($call))->toBe('x is double && x.isInfinite');
});

test('JavaScriptGenerator generates is_infinite', function () {
    $call = new IR\FunctionCall('is_infinite', [new IR\Variable('x')]);
    $gen = new JavaScriptGenerator();
    expect($gen->generate($call))->toBe('x === Infinity || x === -Infinity');
});

test('CSharpGenerator generates is_infinite', function () {
    $call = new IR\FunctionCall('is_infinite', [new IR\Variable('x')]);
    $gen = new CSharpGenerator();
    expect($gen->generate($call))->toBe('double.IsInfinity(Convert.ToDouble(x))');
});

// --- is_nan ---

test('SwiftGenerator generates is_nan', function () {
    $call = new IR\FunctionCall('is_nan', [new IR\Variable('x')]);
    $gen = new SwiftGenerator([]);
    expect($gen->generate($call))->toBe('(x as? Double)?.isNaN ?? false');
});

test('KotlinGenerator generates is_nan', function () {
    $call = new IR\FunctionCall('is_nan', [new IR\Variable('x')]);
    $gen = new KotlinGenerator();
    expect($gen->generate($call))->toBe('(x as? Double)?.isNaN() ?: false');
});

test('DartGenerator generates is_nan', function () {
    $call = new IR\FunctionCall('is_nan', [new IR\Variable('x')]);
    $gen = new DartGenerator();
    expect($gen->generate($call))->toBe('x is double && x.isNaN');
});

test('JavaScriptGenerator generates is_nan', function () {
    $call = new IR\FunctionCall('is_nan', [new IR\Variable('x')]);
    $gen = new JavaScriptGenerator();
    expect($gen->generate($call))->toBe('typeof x === \'number\' && isNaN(x)');
});

test('CSharpGenerator generates is_nan', function () {
    $call = new IR\FunctionCall('is_nan', [new IR\Variable('x')]);
    $gen = new CSharpGenerator();
    expect($gen->generate($call))->toBe('double.IsNaN(Convert.ToDouble(x))');
});

// --- is_scalar ---

test('SwiftGenerator generates is_scalar', function () {
    $call = new IR\FunctionCall('is_scalar', [new IR\Variable('x')]);
    $gen = new SwiftGenerator([]);
    expect($gen->generate($call))->toBe('(x is String) || (x is Int) || (x is Double) || (x is Bool)');
});

test('KotlinGenerator generates is_scalar', function () {
    $call = new IR\FunctionCall('is_scalar', [new IR\Variable('x')]);
    $gen = new KotlinGenerator();
    expect($gen->generate($call))->toBe('x is String || x is Int || x is Double || x is Boolean');
});

test('DartGenerator generates is_scalar', function () {
    $call = new IR\FunctionCall('is_scalar', [new IR\Variable('x')]);
    $gen = new DartGenerator();
    expect($gen->generate($call))->toBe('x is String || x is int || x is double || x is bool');
});

test('JavaScriptGenerator generates is_scalar', function () {
    $call = new IR\FunctionCall('is_scalar', [new IR\Variable('x')]);
    $gen = new JavaScriptGenerator();
    expect($gen->generate($call))->toBe('typeof x === \'string\' || typeof x === \'number\' || typeof x === \'boolean\'');
});

test('CSharpGenerator generates is_scalar', function () {
    $call = new IR\FunctionCall('is_scalar', [new IR\Variable('x')]);
    $gen = new CSharpGenerator();
    expect($gen->generate($call))->toBe('x is string || x is int || x is double || x is bool');
});

// --- array_key_first ---

test('SwiftGenerator generates array_key_first', function () {
    $call = new IR\FunctionCall('array_key_first', [new IR\Variable('x')]);
    $gen = new SwiftGenerator([]);
    expect($gen->generate($call))->toBe('(x as? [String: Any])?.keys.first');
});

test('KotlinGenerator generates array_key_first', function () {
    $call = new IR\FunctionCall('array_key_first', [new IR\Variable('x')]);
    $gen = new KotlinGenerator();
    expect($gen->generate($call))->toBe('(x as? Map<*, *>)?.keys.firstOrNull()');
});

test('DartGenerator generates array_key_first', function () {
    $call = new IR\FunctionCall('array_key_first', [new IR\Variable('x')]);
    $gen = new DartGenerator();
    expect($gen->generate($call))->toBe('(x as Map).keys.isNotEmpty ? (x as Map).keys.first : null');
});

test('JavaScriptGenerator generates array_key_first', function () {
    $call = new IR\FunctionCall('array_key_first', [new IR\Variable('x')]);
    $gen = new JavaScriptGenerator();
    expect($gen->generate($call))->toBe('Object.keys(x)[0] ?? undefined');
});

test('CSharpGenerator generates array_key_first', function () {
    $call = new IR\FunctionCall('array_key_first', [new IR\Variable('x')]);
    $gen = new CSharpGenerator();
    expect($gen->generate($call))->toBe('x.Keys.Cast<object>().FirstOrDefault()');
});
