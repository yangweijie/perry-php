<?php

use Perry\Generator\SwiftGenerator;
use Perry\Generator\JavaScriptGenerator;
use Perry\Generator\KotlinGenerator;
use Perry\Generator\DartGenerator;
use Perry\Generator\CSharpGenerator;
use Perry\IR;

// ============================================================
// While Loop
// ============================================================

test('While generates correctly in all generators', function () {
    $while = new IR\WhileStatement(
        new IR\BinaryOp('<', new IR\Variable('i'), new IR\Literal(10)),
        new IR\Assignment('i', new IR\BinaryOp('+', new IR\Variable('i'), new IR\Literal(1)))
    );

    expect((new SwiftGenerator)->generate($while))->toContain('while i < 10 {')
        ->and((new JavaScriptGenerator)->generate($while))->toContain('while (i < 10) {')
        ->and((new KotlinGenerator)->generate($while))->toContain('while (i < 10) {')
        ->and((new DartGenerator)->generate($while))->toContain('while (i < 10) {')
        ->and((new CSharpGenerator)->generate($while))->toContain('while (i < 10)');
});

// ============================================================
// For Loop
// ============================================================

test('For generates correctly in all generators', function () {
    $for = new IR\ForStatement(
        [new IR\Assignment('i', new IR\Literal(0))],
        [new IR\BinaryOp('<', new IR\Variable('i'), new IR\Literal(10))],
        [new IR\Assignment('i', new IR\BinaryOp('+', new IR\Variable('i'), new IR\Literal(1)))],
        new IR\Assignment('sum', new IR\BinaryOp('+', new IR\Variable('sum'), new IR\Variable('i')))
    );

    expect((new SwiftGenerator)->generate($for))->toContain('for var i = 0; i < 10;')
        ->and((new JavaScriptGenerator)->generate($for))->toContain('for (let i;')
        ->and((new JavaScriptGenerator)->generate($for))->toContain('i = 0; i < 10;')
        ->and((new KotlinGenerator)->generate($for))->toContain('for (var i = 0; i < 10;')
        ->and((new DartGenerator)->generate($for))->toContain('for (var i = 0; i < 10;')
        ->and((new CSharpGenerator)->generate($for))->toContain('for (var i = 0; i < 10;');
});

// ============================================================
// Foreach Loop
// ============================================================

test('Foreach generates correctly in all generators', function () {
    $foreach = new IR\ForeachStatement(
        new IR\Variable('item'),
        null,
        new IR\Variable('items'),
        new IR\FunctionCall('print', [new IR\Variable('item')])
    );

    expect((new SwiftGenerator)->generate($foreach))->toContain('for item in items {')
        ->and((new JavaScriptGenerator)->generate($foreach))->toContain('for (const item of items) {')
        ->and((new KotlinGenerator)->generate($foreach))->toContain('for (item in items) {')
        ->and((new DartGenerator)->generate($foreach))->toContain('for (var item in items) {')
        ->and((new CSharpGenerator)->generate($foreach))->toContain('foreach (var item in items)');
});

test('Foreach with key generates correctly', function () {
    $foreach = new IR\ForeachStatement(
        new IR\Variable('value'),
        new IR\Variable('key'),
        new IR\Variable('map'),
        new IR\Assignment('result', new IR\Variable('value'))
    );

    expect((new SwiftGenerator)->generate($foreach))->toContain('for key, value in map {')
        ->and((new JavaScriptGenerator)->generate($foreach))->toContain('Object.entries(map)')
        ->and((new KotlinGenerator)->generate($foreach))->toContain('for ((key, value) in map)')
        ->and((new DartGenerator)->generate($foreach))->toContain('for (var key in map.keys)')
        ->and((new CSharpGenerator)->generate($foreach))->toContain('foreach (var kvp in map)');
});

// ============================================================
// Break / Continue
// ============================================================

test('Break generates correctly', function () {
    $break = new IR\BreakStatement(1);

    expect((new SwiftGenerator)->generate($break))->toBe('break')
        ->and((new JavaScriptGenerator)->generate($break))->toBe('break')
        ->and((new KotlinGenerator)->generate($break))->toBe('break')
        ->and((new DartGenerator)->generate($break))->toBe('break')
        ->and((new CSharpGenerator)->generate($break))->toBe('break');
});

test('Continue generates correctly', function () {
    $continue = new IR\ContinueStatement(1);

    expect((new SwiftGenerator)->generate($continue))->toBe('continue')
        ->and((new JavaScriptGenerator)->generate($continue))->toBe('continue')
        ->and((new KotlinGenerator)->generate($continue))->toBe('continue')
        ->and((new DartGenerator)->generate($continue))->toBe('continue')
        ->and((new CSharpGenerator)->generate($continue))->toBe('continue');
});

// ============================================================
// Switch / Match
// ============================================================

test('Switch generates correctly', function () {
    $switch = new IR\SwitchStatement(
        new IR\Variable('x'),
        [
            new IR\CaseNode(new IR\Literal(1), new IR\Assignment('result', new IR\Literal('one'))),
            new IR\CaseNode(null, new IR\Assignment('result', new IR\Literal('other'))),
        ]
    );

    expect((new SwiftGenerator)->generate($switch))->toContain('switch x {')
        ->and((new SwiftGenerator)->generate($switch))->toContain('case 1:')
        ->and((new SwiftGenerator)->generate($switch))->toContain('default:');

    expect((new JavaScriptGenerator)->generate($switch))->toContain('switch (x) {')
        ->and((new JavaScriptGenerator)->generate($switch))->toContain('case 1:');

    expect((new KotlinGenerator)->generate($switch))->toContain('when (x) {')
        ->and((new KotlinGenerator)->generate($switch))->toContain('1 -> {');

    expect((new DartGenerator)->generate($switch))->toContain('switch (x) {')
        ->and((new DartGenerator)->generate($switch))->toContain('case 1:');

    expect((new CSharpGenerator)->generate($switch))->toContain('switch (x)')
        ->and((new CSharpGenerator)->generate($switch))->toContain('case 1:');
});

test('Match generates correctly', function () {
    $match = new IR\MatchExpression(
        new IR\Variable('status'),
        [
            ['condition' => new IR\Literal(200), 'body' => new IR\Literal('ok')],
            ['condition' => new IR\Literal(404), 'body' => new IR\Literal('not found')],
        ]
    );

    expect((new SwiftGenerator)->generate($match))->toContain('match status {')
        ->and((new SwiftGenerator)->generate($match))->toContain('case 200:');

    expect((new JavaScriptGenerator)->generate($match))->toContain('switch (status) {');

    expect((new KotlinGenerator)->generate($match))->toContain('when (status) {')
        ->and((new KotlinGenerator)->generate($match))->toContain('200 ->');

    expect((new DartGenerator)->generate($match))->toContain('switch (status)');
    expect((new CSharpGenerator)->generate($match))->toContain('switch');
});

// ============================================================
// Echo / Print
// ============================================================

test('Echo generates correctly', function () {
    $echo = new IR\EchoStatement([
        new IR\Literal('Hello'),
        new IR\Variable('name')
    ]);

    expect((new SwiftGenerator)->generate($echo))->toContain('print(')
        ->and((new JavaScriptGenerator)->generate($echo))->toContain('console.log(')
        ->and((new KotlinGenerator)->generate($echo))->toContain('println(')
        ->and((new DartGenerator)->generate($echo))->toContain('print(')
        ->and((new CSharpGenerator)->generate($echo))->toContain('Console.WriteLine(');
});

test('Print generates correctly', function () {
    $print = new IR\PrintStatement(new IR\Literal('test'));

    expect((new SwiftGenerator)->generate($print))->toBe('print("test")')
        ->and((new JavaScriptGenerator)->generate($print))->toBe('console.log("test")')
        ->and((new KotlinGenerator)->generate($print))->toBe('println("test")')
        ->and((new DartGenerator)->generate($print))->toBe('print("test")')
        ->and((new CSharpGenerator)->generate($print))->toBe('Console.WriteLine("test")');
});

// ============================================================
// Type Casting
// ============================================================

test('Cast generates correctly', function () {
    $castInt = new IR\Cast('int', new IR\Variable('x'));
    $castFloat = new IR\Cast('float', new IR\Variable('x'));
    $castString = new IR\Cast('string', new IR\Variable('x'));

    expect((new SwiftGenerator)->generate($castInt))->toBe('Int(x)')
        ->and((new SwiftGenerator)->generate($castFloat))->toBe('Double(x)')
        ->and((new SwiftGenerator)->generate($castString))->toBe('String(x)');

    expect((new JavaScriptGenerator)->generate($castInt))->toBe('parseInt(x)')
        ->and((new JavaScriptGenerator)->generate($castFloat))->toBe('parseFloat(x)')
        ->and((new JavaScriptGenerator)->generate($castString))->toBe('String(x)');

    expect((new KotlinGenerator)->generate($castInt))->toBe('x.toInt()')
        ->and((new KotlinGenerator)->generate($castFloat))->toBe('x.toDouble()')
        ->and((new KotlinGenerator)->generate($castString))->toBe('x.toString()');

    expect((new DartGenerator)->generate($castInt))->toBe('int.parse(x.toString())')
        ->and((new DartGenerator)->generate($castFloat))->toBe('double.parse(x.toString())')
        ->and((new DartGenerator)->generate($castString))->toBe('x.toString()');

    expect((new CSharpGenerator)->generate($castInt))->toBe('(int)(x)')
        ->and((new CSharpGenerator)->generate($castFloat))->toBe('(double)(x)')
        ->and((new CSharpGenerator)->generate($castString))->toBe('(x).ToString()');
});

// ============================================================
// Increment / Decrement
// ============================================================

test('Increment generates correctly', function () {
    $postInc = new IR\Increment('x', false);
    $preInc = new IR\Increment('x', true);

    expect((new SwiftGenerator)->generate($postInc))->toContain('+= 1')
        ->and((new SwiftGenerator)->generate($preInc))->toContain('+= 1');

    expect((new JavaScriptGenerator)->generate($postInc))->toBe('x++')
        ->and((new JavaScriptGenerator)->generate($preInc))->toBe('++x');

    expect((new KotlinGenerator)->generate($postInc))->toBe('x++')
        ->and((new KotlinGenerator)->generate($preInc))->toBe('++x');

    expect((new DartGenerator)->generate($postInc))->toBe('x++')
        ->and((new DartGenerator)->generate($preInc))->toBe('++x');

    expect((new CSharpGenerator)->generate($postInc))->toBe('x++')
        ->and((new CSharpGenerator)->generate($preInc))->toBe('++x');
});

test('Decrement generates correctly', function () {
    $postDec = new IR\Decrement('x', false);
    $preDec = new IR\Decrement('x', true);

    expect((new SwiftGenerator)->generate($postDec))->toContain('-= 1');

    expect((new JavaScriptGenerator)->generate($postDec))->toBe('x--')
        ->and((new JavaScriptGenerator)->generate($preDec))->toBe('--x');

    expect((new KotlinGenerator)->generate($postDec))->toBe('x--');
    expect((new DartGenerator)->generate($postDec))->toBe('x--');
    expect((new CSharpGenerator)->generate($postDec))->toBe('x--');
});

// ============================================================
// Compound Assignment
// ============================================================

test('Compound assignment operators generate correctly', function () {
    $plus = new IR\PlusAssign('x', new IR\Literal(5));
    $minus = new IR\MinusAssign('x', new IR\Literal(3));
    $mul = new IR\MulAssign('x', new IR\Literal(2));
    $div = new IR\DivAssign('x', new IR\Literal(4));
    $mod = new IR\ModAssign('x', new IR\Literal(3));

    foreach ([new SwiftGenerator, new JavaScriptGenerator, new KotlinGenerator, new DartGenerator, new CSharpGenerator] as $gen) {
        $prefix = $gen instanceof JavaScriptGenerator ? 'state.' : '';
        expect($gen->generate($plus))->toContain('+= 5')
            ->and($gen->generate($minus))->toContain('-= 3')
            ->and($gen->generate($mul))->toContain('*= 2')
            ->and($gen->generate($div))->toContain('/= 4')
            ->and($gen->generate($mod))->toContain('%= 3');
    }
});

// ============================================================
// Additional Binary Operators
// ============================================================

test('Pow generates correctly', function () {
    $pow = new IR\PowOp(new IR\Variable('base'), new IR\Variable('exp'));

    expect((new SwiftGenerator)->generate($pow))->toBe('pow(base, exp)')
        ->and((new JavaScriptGenerator)->generate($pow))->toBe('Math.pow(base, exp)')
        ->and((new KotlinGenerator)->generate($pow))->toContain('Math.pow(base, exp)')
        ->and((new DartGenerator)->generate($pow))->toContain('pow(base, exp)')
        ->and((new CSharpGenerator)->generate($pow))->toBe('Math.Pow(base, exp)');
});

test('Bitwise operators generate correctly', function () {
    $and = new IR\BitwiseAnd(new IR\Variable('a'), new IR\Variable('b'));
    $or = new IR\BitwiseOr(new IR\Variable('a'), new IR\Variable('b'));
    $xor = new IR\BitwiseXor(new IR\Variable('a'), new IR\Variable('b'));
    $shl = new IR\ShiftLeft(new IR\Variable('a'), new IR\Variable('b'));
    $shr = new IR\ShiftRight(new IR\Variable('a'), new IR\Variable('b'));

    foreach ([new SwiftGenerator, new JavaScriptGenerator, new DartGenerator, new CSharpGenerator] as $gen) {
        expect($gen->generate($and))->toContain('a & b')
            ->and($gen->generate($or))->toContain('a | b')
            ->and($gen->generate($xor))->toContain('a ^ b')
            ->and($gen->generate($shl))->toContain('a << b')
            ->and($gen->generate($shr))->toContain('a >> b');
    }

    expect((new KotlinGenerator)->generate($and))->toContain('a and b')
        ->and((new KotlinGenerator)->generate($or))->toContain('a or b')
        ->and((new KotlinGenerator)->generate($xor))->toContain('a xor b')
        ->and((new KotlinGenerator)->generate($shl))->toContain('a shl b')
        ->and((new KotlinGenerator)->generate($shr))->toContain('a shr b');
});

test('Spaceship operator generates correctly', function () {
    $spaceship = new IR\SpaceshipOp(new IR\Variable('a'), new IR\Variable('b'));

    foreach ([new SwiftGenerator, new JavaScriptGenerator, new KotlinGenerator, new DartGenerator, new CSharpGenerator] as $gen) {
        $result = $gen->generate($spaceship);
        expect($result)->toContain('a < b')
            ->and($result)->toContain('a > b')
            ->and($result)->toContain('-1')
            ->and($result)->toContain('1');
    }
});

test('Coalesce operator generates correctly', function () {
    $coalesce = new IR\CoalesceOp(new IR\Variable('x'), new IR\Literal('default'));

    expect((new SwiftGenerator)->generate($coalesce))->toBe('x ?? "default"')
        ->and((new JavaScriptGenerator)->generate($coalesce))->toBe('x ?? "default"')
        ->and((new DartGenerator)->generate($coalesce))->toBe('x ?? "default"')
        ->and((new CSharpGenerator)->generate($coalesce))->toBe('x ?? "default"');

    expect((new KotlinGenerator)->generate($coalesce))->toContain('?:');
});

test('Logical operators generate correctly', function () {
    $and = new IR\LogicalAnd(new IR\Variable('a'), new IR\Variable('b'));
    $or = new IR\LogicalOr(new IR\Variable('a'), new IR\Variable('b'));
    $xor = new IR\LogicalXor(new IR\Variable('a'), new IR\Variable('b'));

    foreach ([new SwiftGenerator, new JavaScriptGenerator, new KotlinGenerator, new DartGenerator, new CSharpGenerator] as $gen) {
        expect($gen->generate($and))->toContain('a && b')
            ->and($gen->generate($or))->toContain('a || b');
    }

    foreach ([new SwiftGenerator, new KotlinGenerator, new DartGenerator, new CSharpGenerator] as $gen) {
        $result = $gen->generate($xor);
        expect($result)->toContain('a != b');
    }

    expect((new JavaScriptGenerator)->generate($xor))->toContain('a !== b');
});

// ============================================================
// Additional Unary Operators
// ============================================================

test('UnaryPlus generates correctly', function () {
    $up = new IR\UnaryPlus(new IR\Variable('x'));

    expect((new SwiftGenerator)->generate($up))->toBe('+(x)')
        ->and((new JavaScriptGenerator)->generate($up))->toBe('+(x)')
        ->and((new KotlinGenerator)->generate($up))->toBe('+(x)')
        ->and((new DartGenerator)->generate($up))->toBe('+(x)')
        ->and((new CSharpGenerator)->generate($up))->toBe('+(x)');
});

test('BitwiseNot generates correctly', function () {
    $bn = new IR\BitwiseNot(new IR\Variable('x'));

    expect((new SwiftGenerator)->generate($bn))->toBe('~(x)')
        ->and((new JavaScriptGenerator)->generate($bn))->toBe('~(x)')
        ->and((new DartGenerator)->generate($bn))->toBe('~(x)')
        ->and((new CSharpGenerator)->generate($bn))->toBe('~(x)');

    expect((new KotlinGenerator)->generate($bn))->toBe('inv(x)');
});

// ============================================================
// Nullsafe Operations
// ============================================================

test('Nullsafe method call generates correctly', function () {
    $call = new IR\NullsafeMethodCall(
        new IR\Variable('obj'),
        'getValue',
        [new IR\Literal('key')]
    );

    expect((new SwiftGenerator)->generate($call))->toBe('obj?.getValue("key")')
        ->and((new JavaScriptGenerator)->generate($call))->toBe('obj?.getValue("key")')
        ->and((new KotlinGenerator)->generate($call))->toBe('obj?.getValue("key")')
        ->and((new DartGenerator)->generate($call))->toBe('obj?.getValue("key")')
        ->and((new CSharpGenerator)->generate($call))->toBe('obj?.getValue("key")');
});

test('Nullsafe property access generates correctly', function () {
    $prop = new IR\NullsafePropertyAccess(new IR\Variable('obj'), 'name');

    expect((new SwiftGenerator)->generate($prop))->toBe('obj?.name')
        ->and((new JavaScriptGenerator)->generate($prop))->toBe('obj?.name')
        ->and((new KotlinGenerator)->generate($prop))->toBe('obj?.name')
        ->and((new DartGenerator)->generate($prop))->toBe('obj?.name')
        ->and((new CSharpGenerator)->generate($prop))->toBe('obj?.name');
});

// ============================================================
// Exceptions
// ============================================================

test('Throw generates correctly', function () {
    $throw = new IR\ThrowStatement(new IR\FunctionCall('Exception', [new IR\Literal('error')]));

    expect((new SwiftGenerator)->generate($throw))->toContain('throw')
        ->and((new JavaScriptGenerator)->generate($throw))->toContain('throw')
        ->and((new KotlinGenerator)->generate($throw))->toContain('throw')
        ->and((new DartGenerator)->generate($throw))->toContain('throw')
        ->and((new CSharpGenerator)->generate($throw))->toContain('throw');
});

test('TryCatch generates correctly', function () {
    $tryCatch = new IR\TryCatchStatement(
        new IR\Assignment('x', new IR\Literal(1)),
        [
            new IR\CatchClause('Exception', 'e', new IR\Assignment('x', new IR\Literal(0))),
        ]
    );

    $swift = (new SwiftGenerator)->generate($tryCatch);
    $js = (new JavaScriptGenerator)->generate($tryCatch);
    $kt = (new KotlinGenerator)->generate($tryCatch);
    $dart = (new DartGenerator)->generate($tryCatch);
    $cs = (new CSharpGenerator)->generate($tryCatch);

    expect($swift)->toContain('do {')
        ->and($swift)->toContain('catch Exception')
        ->and($js)->toContain('try {')
        ->and($js)->toContain('catch (e)')
        ->and($kt)->toContain('try {')
        ->and($kt)->toContain('catch (e: Exception)')
        ->and($dart)->toContain('try {')
        ->and($dart)->toContain('catch (e)')
        ->and($cs)->toContain('try');
});

// ============================================================
// Static Operations
// ============================================================

test('StaticCall generates correctly', function () {
    $call = new IR\StaticCall('Math', 'max', [new IR\Variable('a'), new IR\Variable('b')]);

    expect((new SwiftGenerator)->generate($call))->toBe('Math.max(a, b)')
        ->and((new JavaScriptGenerator)->generate($call))->toBe('Math.max(a, b)')
        ->and((new KotlinGenerator)->generate($call))->toBe('Math.max(a, b)')
        ->and((new DartGenerator)->generate($call))->toBe('Math.max(a, b)')
        ->and((new CSharpGenerator)->generate($call))->toBe('Math.max(a, b)');
});

test('Static property access generates correctly', function () {
    $prop = new IR\StaticPropertyAccess('Math', 'PI');

    expect((new SwiftGenerator)->generate($prop))->toBe('Math.PI')
        ->and((new JavaScriptGenerator)->generate($prop))->toBe('Math.PI')
        ->and((new KotlinGenerator)->generate($prop))->toBe('Math.PI')
        ->and((new DartGenerator)->generate($prop))->toBe('Math.PI')
        ->and((new CSharpGenerator)->generate($prop))->toBe('Math.PI');
});

test('Class const fetch generates correctly', function () {
    $const = new IR\ClassConstFetch('HttpStatus', 'OK');

    expect((new SwiftGenerator)->generate($const))->toBe('HttpStatus.OK')
        ->and((new JavaScriptGenerator)->generate($const))->toBe('HttpStatus.OK')
        ->and((new KotlinGenerator)->generate($const))->toBe('HttpStatus.OK')
        ->and((new DartGenerator)->generate($const))->toBe('HttpStatus.OK')
        ->and((new CSharpGenerator)->generate($const))->toBe('HttpStatus.OK');
});

// ============================================================
// Include
// ============================================================

test('Include generates correctly', function () {
    $include = new IR\IncludeStatement('config.php');

    expect((new SwiftGenerator)->generate($include))->toContain("include 'config.php'")
        ->and((new JavaScriptGenerator)->generate($include))->toContain("include 'config.php'")
        ->and((new DartGenerator)->generate($include))->toContain("include 'config.php'");
});

// ============================================================
// Complex Nested Structures
// ============================================================

test('Nested while with break generates correct indentation', function () {
    $while = new IR\WhileStatement(
        new IR\Literal(true),
        new IR\IfStatement(
            new IR\BinaryOp('>', new IR\Variable('i'), new IR\Literal(5)),
            new IR\BreakStatement(1)
        )
    );

    $gen = new SwiftGenerator;
    $result = $gen->generate($while);

    expect($result)->toContain('while true {')
        ->and($result)->toContain('if i > 5 {')
        ->and($result)->toContain('break');
});

test('Foreach with method call body', function () {
    $foreach = new IR\ForeachStatement(
        new IR\Variable('item'),
        null,
        new IR\Variable('items'),
        new IR\MethodCall(new IR\Variable('item'), 'process', [])
    );

    $gen = new JavaScriptGenerator;
    $result = $gen->generate($foreach);

    expect($result)->toContain('for (const item of items) {')
        ->and($result)->toContain('item.process()');
});
