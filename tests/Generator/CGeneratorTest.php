<?php

declare(strict_types=1);

use Perry\Generator\CGenerator;
use Perry\IR;

test('CGenerator generates variable assignment', function () {
    $literal = new IR\Literal('hello');
    $assign = new IR\Assignment('message', $literal);
    
    $gen = new CGenerator();
    $result = $gen->generate($assign);
    
    // CGenerator declares new variables with type
    expect($result)->toBe('const char* message = "hello"');
});

test('CGenerator generates new variable declaration', function () {
    $literal = new IR\Literal(42);
    $assign = new IR\Assignment('count', $literal);
    
    $gen = new CGenerator();
    $result = $gen->generate($assign);
    
    expect($result)->toBe('int count = 42');
});

test('CGenerator generates integer literal', function () {
    $literal = new IR\Literal(123);
    $gen = new CGenerator();
    expect($gen->generate($literal))->toBe('123');
});

test('CGenerator generates float literal', function () {
    $literal = new IR\Literal(3.14);
    $gen = new CGenerator();
    expect($gen->generate($literal))->toBe('3.14');
});

test('CGenerator generates string literal with escaping', function () {
    $literal = new IR\Literal('hello "world"');
    $gen = new CGenerator();
    expect($gen->generate($literal))->toBe('"hello \\"world\\""');
});

test('CGenerator generates boolean literals', function () {
    $true = new IR\Literal(true);
    $false = new IR\Literal(false);
    $gen = new CGenerator();
    expect($gen->generate($true))->toBe('TRUE');
    expect($gen->generate($false))->toBe('FALSE');
});

test('CGenerator generates null literal', function () {
    $literal = new IR\Literal(null);
    $gen = new CGenerator();
    expect($gen->generate($literal))->toBe('NULL');
});

test('CGenerator generates variable reference', function () {
    $var = new IR\Variable('x');
    $gen = new CGenerator();
    expect($gen->generate($var))->toBe('x');
});

test('CGenerator generates binary operations', function () {
    $add = new IR\BinaryOp('+', new IR\Variable('a'), new IR\Variable('b'));
    $sub = new IR\BinaryOp('-', new IR\Variable('a'), new IR\Variable('b'));
    $mul = new IR\BinaryOp('*', new IR\Variable('a'), new IR\Variable('b'));
    $div = new IR\BinaryOp('/', new IR\Variable('a'), new IR\Variable('b'));
    $mod = new IR\BinaryOp('%', new IR\Variable('a'), new IR\Variable('b'));
    $eq = new IR\BinaryOp('==', new IR\Variable('a'), new IR\Variable('b'));
    $ne = new IR\BinaryOp('!=', new IR\Variable('a'), new IR\Variable('b'));
    $lt = new IR\BinaryOp('<', new IR\Variable('a'), new IR\Variable('b'));
    $gt = new IR\BinaryOp('>', new IR\Variable('a'), new IR\Variable('b'));
    $le = new IR\BinaryOp('<=', new IR\Variable('a'), new IR\Variable('b'));
    $ge = new IR\BinaryOp('>=', new IR\Variable('a'), new IR\Variable('b'));
    $and = new IR\BinaryOp('&&', new IR\Variable('a'), new IR\Variable('b'));
    $or = new IR\BinaryOp('||', new IR\Variable('a'), new IR\Variable('b'));
    
    $gen = new CGenerator();
    expect($gen->generate($add))->toBe('(a + b)');
    expect($gen->generate($sub))->toBe('(a - b)');
    expect($gen->generate($mul))->toBe('(a * b)');
    expect($gen->generate($div))->toBe('(a / b)');
    expect($gen->generate($mod))->toBe('(a % b)');
    expect($gen->generate($eq))->toBe('(a == b)');
    expect($gen->generate($ne))->toBe('(a != b)');
    expect($gen->generate($lt))->toBe('(a < b)');
    expect($gen->generate($gt))->toBe('(a > b)');
    expect($gen->generate($le))->toBe('(a <= b)');
    expect($gen->generate($ge))->toBe('(a >= b)');
    expect($gen->generate($and))->toBe('(a && b)');
    expect($gen->generate($or))->toBe('(a || b)');
});

test('CGenerator generates unary operations', function () {
    $not = new IR\UnaryOp('!', new IR\Variable('x'));
    $neg = new IR\UnaryOp('-', new IR\Variable('x'));
    $bitnot = new IR\BitwiseNot(new IR\Variable('x'));
    
    $gen = new CGenerator();
    expect($gen->generate($not))->toBe('!x');
    expect($gen->generate($neg))->toBe('-x');
    expect($gen->generate($bitnot))->toBe('(~x)');
});

test('CGenerator generates if statement', function () {
    $condition = new IR\BinaryOp('==', new IR\Variable('x'), new IR\Literal(0));
    $then = new IR\Assignment('result', new IR\Literal('zero'));
    $if = new IR\IfStatement($condition, $then, null);
    
    $gen = new CGenerator();
    $result = $gen->generate($if);
    
    // CGenerator adds extra parentheses around conditions
    expect($result)->toContain('if ((x == 0)) {')
        ->and($result)->toContain('const char* result = "zero"')
        ->and($result)->toContain('}');
});

test('CGenerator generates if-else statement', function () {
    $condition = new IR\BinaryOp('==', new IR\Variable('x'), new IR\Literal(0));
    $then = new IR\Assignment('result', new IR\Literal('zero'));
    $else = new IR\Assignment('result', new IR\Literal('not-zero'));
    $if = new IR\IfStatement($condition, $then, $else);
    
    $gen = new CGenerator();
    $result = $gen->generate($if);
    
    expect($result)->toContain('if ((x == 0)) {')
        ->and($result)->toContain('const char* result = "zero"')
        ->and($result)->toContain('} else {')
        ->and($result)->toContain('result = "not-zero"')
        ->and($result)->toContain('}');
});

test('CGenerator generates while loop', function () {
    $condition = new IR\BinaryOp('>', new IR\Variable('i'), new IR\Literal(0));
    $body = new IR\Assignment('i', new IR\BinaryOp('-', new IR\Variable('i'), new IR\Literal(1)));
    $while = new IR\WhileStatement($condition, $body);
    
    $gen = new CGenerator();
    $result = $gen->generate($while);
    
    expect($result)->toContain('while ((i > 0)) {')
        ->and($result)->toContain('int i = (i - 1)')
        ->and($result)->toContain('}');
});

test('CGenerator generates for loop', function () {
    $init = [new IR\Assignment('i', new IR\Literal(0))];
    $cond = [new IR\BinaryOp('<', new IR\Variable('i'), new IR\Literal(10))];
    $loop = [new IR\Increment('i', false)];
    $body = new IR\Assignment('sum', new IR\BinaryOp('+', new IR\Variable('sum'), new IR\Variable('i')));
    $for = new IR\ForStatement($init, $cond, $loop, $body);
    
    $gen = new CGenerator();
    $result = $gen->generate($for);
    
    // CGenerator adds extra parentheses around condition and loop expressions
    expect($result)->toContain('for (int i = 0; (i < 10); (i++)) {')
        ->and($result)->toContain('int sum = (sum + i)')
        ->and($result)->toContain('}');
});

test('CGenerator generates foreach loop', function () {
    $valueVar = new IR\Variable('item');
    $iterable = new IR\Variable('arr');
    $body = new IR\EchoStatement([new IR\Variable('item')]);
    $foreach = new IR\ForeachStatement($valueVar, null, $iterable, $body);
    
    $gen = new CGenerator();
    $result = $gen->generate($foreach);
    
    expect($result)->toContain('for (int _i = 0; _i < arr_len; _i++) {')
        ->and($result)->toContain('item = arr[_i];')
        ->and($result)->toContain('g_print("%s\\n", item)')
        ->and($result)->toContain('}');
});

test('CGenerator generates break', function () {
    $break = new IR\BreakStatement(1);
    $gen = new CGenerator();
    expect($gen->generate($break))->toBe('break');
});

test('CGenerator generates continue', function () {
    $continue = new IR\ContinueStatement(1);
    $gen = new CGenerator();
    expect($gen->generate($continue))->toBe('continue');
});

test('CGenerator generates switch statement', function () {
    $condition = new IR\Variable('x');
    $case1 = new IR\CaseNode(new IR\Literal(1), new IR\EchoStatement([new IR\Literal('one')]));
    $case2 = new IR\CaseNode(new IR\Literal(2), new IR\EchoStatement([new IR\Literal('two')]));
    $defaultCase = new IR\CaseNode(null, new IR\EchoStatement([new IR\Literal('other')]));
    $switch = new IR\SwitchStatement($condition, [$case1, $case2, $defaultCase]);
    
    $gen = new CGenerator();
    $result = $gen->generate($switch);
    
    expect($result)->toContain('switch (x) {')
        ->and($result)->toContain('case 1:')
        ->and($result)->toContain('case 2:')
        ->and($result)->toContain('default:')
        ->and($result)->toContain('}');
});

test('CGenerator generates ternary', function () {
    $ternary = new IR\Ternary(
        new IR\Variable('cond'),
        new IR\Literal('yes'),
        new IR\Literal('no')
    );
    
    $gen = new CGenerator();
    $result = $gen->generate($ternary);
    
    expect($result)->toBe('(cond ? "yes" : "no")');
});

test('CGenerator generates echo statement', function () {
    $echo = new IR\EchoStatement([new IR\Literal('Hello'), new IR\Variable('name')]);
    $gen = new CGenerator();
    $result = $gen->generate($echo);
    expect($result)->toBe('g_print("%s %s\\n", "Hello", name)');
});

test('CGenerator generates print statement', function () {
    $print = new IR\PrintStatement(new IR\Variable('x'));
    $gen = new CGenerator();
    expect($gen->generate($print))->toBe('g_print("%s\\n", x)');
});

test('CGenerator generates type cast', function () {
    $intCast = new IR\Cast('int', new IR\Variable('x'));
    $floatCast = new IR\Cast('float', new IR\Variable('x'));
    $stringCast = new IR\Cast('string', new IR\Variable('x'));
    $boolCast = new IR\Cast('bool', new IR\Variable('x'));
    
    $gen = new CGenerator();
    expect($gen->generate($intCast))->toBe('(int)x');
    expect($gen->generate($floatCast))->toBe('(double)x');
    expect($gen->generate($stringCast))->toBe('(char*)x');
    expect($gen->generate($boolCast))->toBe('(gboolean)x');
});

test('CGenerator generates increment', function () {
    $inc = new IR\Increment('x', false);
    $preInc = new IR\Increment('x', true);
    $gen = new CGenerator();
    expect($gen->generate($inc))->toBe('(x++)');
    expect($gen->generate($preInc))->toBe('(++x)');
});

test('CGenerator generates decrement', function () {
    $dec = new IR\Decrement('x', false);
    $preDec = new IR\Decrement('x', true);
    $gen = new CGenerator();
    expect($gen->generate($dec))->toBe('(x--)');
    expect($gen->generate($preDec))->toBe('(--x)');
});

test('CGenerator generates compound assignment', function () {
    $plus = new IR\PlusAssign('x', new IR\Literal(1));
    $minus = new IR\MinusAssign('x', new IR\Literal(1));
    $mul = new IR\MulAssign('x', new IR\Literal(2));
    $div = new IR\DivAssign('x', new IR\Literal(2));
    $mod = new IR\ModAssign('x', new IR\Literal(3));
    
    $gen = new CGenerator();
    expect($gen->generate($plus))->toBe('x += 1');
    expect($gen->generate($minus))->toBe('x -= 1');
    expect($gen->generate($mul))->toBe('x *= 2');
    expect($gen->generate($div))->toBe('x /= 2');
    expect($gen->generate($mod))->toBe('x %= 3');
});

test('CGenerator generates pow', function () {
    $pow = new IR\PowOp(new IR\Variable('base'), new IR\Variable('exp'));
    $gen = new CGenerator();
    expect($gen->generate($pow))->toBe('pow(base, exp)');
});

test('CGenerator generates bitwise operations', function () {
    $and = new IR\BitwiseAnd(new IR\Variable('a'), new IR\Variable('b'));
    $or = new IR\BitwiseOr(new IR\Variable('a'), new IR\Variable('b'));
    $xor = new IR\BitwiseXor(new IR\Variable('a'), new IR\Variable('b'));
    $shiftLeft = new IR\ShiftLeft(new IR\Variable('a'), new IR\Literal(1));
    $shiftRight = new IR\ShiftRight(new IR\Variable('a'), new IR\Literal(1));
    
    $gen = new CGenerator();
    expect($gen->generate($and))->toBe('(a & b)');
    expect($gen->generate($or))->toBe('(a | b)');
    expect($gen->generate($xor))->toBe('(a ^ b)');
    expect($gen->generate($shiftLeft))->toBe('(a << 1)');
    expect($gen->generate($shiftRight))->toBe('(a >> 1)');
});

test('CGenerator generates spaceship operator', function () {
    $spaceship = new IR\SpaceshipOp(new IR\Variable('a'), new IR\Variable('b'));
    $gen = new CGenerator();
    // CGenerator adds extra parentheses around comparisons
    expect($gen->generate($spaceship))->toBe('((a < b) ? -1 : ((a > b) ? 1 : 0))');
});

test('CGenerator generates coalesce operator', function () {
    $coalesce = new IR\CoalesceOp(new IR\Variable('a'), new IR\Variable('b'));
    $gen = new CGenerator();
    expect($gen->generate($coalesce))->toBe('((a) ? (a) : (b))');
});

test('CGenerator generates logical xor', function () {
    $xor = new IR\LogicalXor(new IR\Variable('a'), new IR\Variable('b'));
    $gen = new CGenerator();
    expect($gen->generate($xor))->toBe('(a != b)');
});

test('CGenerator generates unary plus', function () {
    $plus = new IR\UnaryPlus(new IR\Variable('x'));
    $gen = new CGenerator();
    expect($gen->generate($plus))->toBe('+x');
});

test('CGenerator generates array access', function () {
    $access = new IR\ArrayAccess(new IR\Variable('arr'), new IR\Variable('i'));
    $gen = new CGenerator();
    expect($gen->generate($access))->toBe('arr[i]');
});

test('CGenerator generates method call', function () {
    $call = new IR\MethodCall(new IR\Variable('obj'), 'method', [new IR\Variable('arg')]);
    $gen = new CGenerator();
    expect($gen->generate($call))->toBe('obj->method(arg)');
});

test('CGenerator generates property access', function () {
    $access = new IR\PropertyAccess(new IR\Variable('obj'), 'property');
    $gen = new CGenerator();
    expect($gen->generate($access))->toBe('obj->property');
});

test('CGenerator generates array literal', function () {
    $array = new IR\ArrayLiteral([new IR\Literal(1), new IR\Literal(2), new IR\Literal(3)]);
    $gen = new CGenerator();
    expect($gen->generate($array))->toBe('{}');
});

test('CGenerator generates nullsafe method call', function () {
    $nullsafe = new IR\NullsafeMethodCall(new IR\Variable('obj'), 'method', [new IR\Variable('arg')]);
    $gen = new CGenerator();
    expect($gen->generate($nullsafe))->toBe('(obj ? obj->method(arg) : NULL)');
});

test('CGenerator generates nullsafe property access', function () {
    $nullsafe = new IR\NullsafePropertyAccess(new IR\Variable('obj'), 'property');
    $gen = new CGenerator();
    expect($gen->generate($nullsafe))->toBe('(obj ? obj->property : NULL)');
});

test('CGenerator generates throw', function () {
    $throw = new IR\ThrowStatement(new IR\Literal('Error occurred'));
    $gen = new CGenerator();
    expect($gen->generate($throw))->toBe('g_error("%s", "Error occurred")');
});

test('CGenerator generates try-catch', function () {
    $catch = new IR\CatchClause('Exception', 'e', new IR\EchoStatement([new IR\Variable('e')]));
    $tryCatch = new IR\TryCatchStatement(new IR\EchoStatement([new IR\Literal('try')]), [$catch], null);
    $gen = new CGenerator();
    $result = $gen->generate($tryCatch);
    expect($result)->toContain('GError *e = NULL;');
});

test('CGenerator generates static call', function () {
    $call = new IR\StaticCall('Math', 'sqrt', [new IR\Variable('x')]);
    $gen = new CGenerator();
    expect($gen->generate($call))->toBe('Math::sqrt(x)');
});

test('CGenerator generates static property access', function () {
    $access = new IR\StaticPropertyAccess('Math', 'PI');
    $gen = new CGenerator();
    expect($gen->generate($access))->toBe('Math::PI');
});

test('CGenerator generates class const fetch', function () {
    $fetch = new IR\ClassConstFetch('Constant', 'VALUE');
    $gen = new CGenerator();
    expect($gen->generate($fetch))->toBe('Constant::VALUE');
});

test('CGenerator generates include', function () {
    $include = new IR\IncludeStatement('stdio.h', false, false);
    $gen = new CGenerator();
    expect($gen->generate($include))->toBe('#include "stdio.h"');
});

test('CGenerator generates return', function () {
    $return = new IR\ReturnStatement(new IR\Variable('x'));
    $gen = new CGenerator();
    expect($gen->generate($return))->toBe('return x');
});

test('CGenerator generates function call', function () {
    $call = new IR\FunctionCall('printf', [new IR\Literal('%d'), new IR\Variable('x')]);
    $gen = new CGenerator();
    // CGenerator keeps string literals as-is (with quotes)
    expect($gen->generate($call))->toBe('printf("%d", x)');
});

test('CGenerator generates program', function () {
    $program = new IR\Program([
        new IR\Assignment('x', new IR\Literal(10)),
        new IR\EchoStatement([new IR\Variable('x')]),
    ]);
    $gen = new CGenerator();
    $result = $gen->generate($program);
    expect($result)->toContain('int x = 10')
        ->and($result)->toContain('g_print("%s\\n", x)');
});

// ============================================================
// Class / Object Support Tests
// ============================================================

test('CGenerator generates property declaration', function () {
    $prop = new IR\PropertyDeclaration('name', 'char*', null, 'private');
    $gen = new CGenerator();
    expect($gen->generate($prop))->toBe('char* name');
});

test('CGenerator generates property declaration with default', function () {
    $prop = new IR\PropertyDeclaration('count', 'int', new IR\Literal(0), 'public');
    $gen = new CGenerator();
    expect($gen->generate($prop))->toBe('int count');
});

test('CGenerator generates method parameter', function () {
    $param = new IR\MethodParameter('x', 'int', null);
    $gen = new CGenerator();
    expect($gen->generate($param))->toBe('int x');
});

test('CGenerator generates method declaration (no body)', function () {
    $param = new IR\MethodParameter('x', 'int', null);
    $method = new IR\MethodDeclaration('add', [$param], null, 'int', 'public', false);
    $gen = new CGenerator();
    expect($gen->generate($method))->toBe('int add(int x);');
});

test('CGenerator generates method declaration (with body)', function () {
    $param = new IR\MethodParameter('x', 'int', null);
    $body = new IR\ReturnStatement(new IR\Variable('x'));
    $method = new IR\MethodDeclaration('getValue', [$param], $body, 'int', 'public', false);
    $gen = new CGenerator();
    $result = $gen->generate($method);
    expect($result)->toContain('int getValue(int x) {')
        ->and($result)->toContain('return x')
        ->and($result)->toContain('}');
});

test('CGenerator generates static method declaration', function () {
    $method = new IR\MethodDeclaration('helper', [], null, 'void', 'public', true);
    $gen = new CGenerator();
    expect($gen->generate($method))->toBe('static void helper();');
});

test('CGenerator generates class declaration', function () {
    $prop = new IR\PropertyDeclaration('x', 'int', null, 'public');
    $param = new IR\MethodParameter('val', 'int', null);
    $body = new IR\Assignment('x', new IR\Variable('val'));
    $method = new IR\MethodDeclaration('setX', [$param], $body, 'void', 'public', false);
    $class = new IR\ClassDeclaration('Point', [$prop], [$method], null, []);
    $gen = new CGenerator();
    $result = $gen->generate($class);
    
    expect($result)->toContain('typedef struct Point {')
        ->and($result)->toContain('int x;')
        ->and($result)->toContain('} Point;')
        ->and($result)->toContain('void setX(int val)');
});

test('CGenerator generates new expression', function () {
    $new = new IR\NewExpr('Point', []);
    $gen = new CGenerator();
    expect($gen->generate($new))->toBe('calloc(1, sizeof(struct Point))');
});

test('CGenerator generates class with inheritance', function () {
    $prop = new IR\PropertyDeclaration('x', 'int', null, 'public');
    $class = new IR\ClassDeclaration('Circle', [$prop], [], 'Shape', []);
    $gen = new CGenerator();
    $result = $gen->generate($class);
    
    // C doesn't support inheritance directly; struct is standalone
    expect($result)->toContain('typedef struct Circle {')
        ->and($result)->toContain('int x;')
        ->and($result)->toContain('} Circle;');
});
