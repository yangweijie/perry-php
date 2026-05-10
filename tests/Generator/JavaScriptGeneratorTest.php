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

test('JavaScriptGenerator generates ceil', function () {
    $call = new IR\FunctionCall('ceil', [new IR\Variable('x')]);
    $gen = new JavaScriptGenerator([]);
    expect($gen->generate($call))->toBe('Math.ceil(x)');
});

test('JavaScriptGenerator generates round', function () {
    $call = new IR\FunctionCall('round', [new IR\Variable('x')]);
    $gen = new JavaScriptGenerator([]);
    expect($gen->generate($call))->toBe('Math.round(x)');
});

test('JavaScriptGenerator generates array_push', function () {
    $call = new IR\FunctionCall('array_push', [new IR\Variable('arr'), new IR\Variable('v')]);
    $gen = new JavaScriptGenerator([]);
    expect($gen->generate($call))->toBe('arr.push(v)');
});

test('JavaScriptGenerator generates empty', function () {
    $call = new IR\FunctionCall('empty', [new IR\Variable('arr')]);
    $gen = new JavaScriptGenerator([]);
    expect($gen->generate($call))->toBe('!arr');
});

test('JavaScriptGenerator generates count', function () {
    $call = new IR\FunctionCall('count', [new IR\Variable('arr')]);
    $gen = new JavaScriptGenerator([]);
    expect($gen->generate($call))->toBe('arr.length');
});

test('JavaScriptGenerator generates strval', function () {
    $call = new IR\FunctionCall('strval', [new IR\Variable('x')]);
    $gen = new JavaScriptGenerator([]);
    expect($gen->generate($call))->toBe('String(x)');
});

test('JavaScriptGenerator generates ternary', function () {
    $ternary = new IR\Ternary(
        new IR\Variable('cond'),
        new IR\Literal('yes'),
        new IR\Literal('no')
    );
    
    $gen = new JavaScriptGenerator([]);
    $result = $gen->generate($ternary);
    
    expect($result)->toBe('cond ? "yes" : "no"');
});

test('JavaScriptGenerator generates if statement', function () {
    $condition = new IR\BinaryOp('===', new IR\Variable('x'), new IR\Literal(0));
    $then = new IR\Assignment('result', new IR\Literal('zero'));
    $if = new IR\IfStatement($condition, $then, null);
    
    $gen = new JavaScriptGenerator([]);
    $result = $gen->generate($if);
    
    expect($result)->toContain('if (x === 0) {')
        ->and($result)->toContain('result = "zero"')
        ->and($result)->toContain('}');
});

test('JavaScriptGenerator generates while loop', function () {
    $condition = new IR\BinaryOp('>', new IR\Variable('i'), new IR\Literal(0));
    $body = new IR\Assignment('i', new IR\BinaryOp('-', new IR\Variable('i'), new IR\Literal(1)));
    $while = new IR\WhileStatement($condition, $body);
    
    $gen = new JavaScriptGenerator([]);
    $result = $gen->generate($while);
    
    expect($result)->toContain('while (i > 0) {')
        ->and($result)->toContain('let i = i - 1')
        ->and($result)->toContain('}');
});

test('JavaScriptGenerator generates for loop', function () {
    $init = [new IR\Assignment('i', new IR\Literal(0))];
    $cond = [new IR\BinaryOp('<', new IR\Variable('i'), new IR\Literal(10))];
    $loop = [new IR\Increment('i', false)];
    $body = new IR\Assignment('sum', new IR\BinaryOp('+', new IR\Variable('sum'), new IR\Variable('i')));
    $for = new IR\ForStatement($init, $cond, $loop, $body);
    
    $gen = new JavaScriptGenerator([]);
    $result = $gen->generate($for);
    
    expect($result)->toContain('for (let i = 0; i < 10; i++) {')
        ->and($result)->toContain('let sum = sum + i')
        ->and($result)->toContain('}');
});

test('JavaScriptGenerator generates break', function () {
    $break = new IR\BreakStatement(1);
    $gen = new JavaScriptGenerator([]);
    expect($gen->generate($break))->toBe('break');
});

test('JavaScriptGenerator generates continue', function () {
    $continue = new IR\ContinueStatement(1);
    $gen = new JavaScriptGenerator([]);
    expect($gen->generate($continue))->toBe('continue');
});

test('JavaScriptGenerator generates switch statement', function () {
    $condition = new IR\Variable('x');
    $case1 = new IR\CaseNode(new IR\Literal(1), new IR\EchoStatement([new IR\Literal('one')]));
    $case2 = new IR\CaseNode(new IR\Literal(2), new IR\EchoStatement([new IR\Literal('two')]));
    $defaultCase = new IR\CaseNode(null, new IR\EchoStatement([new IR\Literal('other')]));
    $switch = new IR\SwitchStatement($condition, [$case1, $case2, $defaultCase]);
    
    $gen = new JavaScriptGenerator([]);
    $result = $gen->generate($switch);
    
    expect($result)->toContain('switch (x) {')
        ->and($result)->toContain('case 1:')
        ->and($result)->toContain('case 2:')
        ->and($result)->toContain('default:')
        ->and($result)->toContain('console.log("one")')
        ->and($result)->toContain('console.log("two")')
        ->and($result)->toContain('console.log("other")')
        ->and($result)->toContain('}');
});

test('JavaScriptGenerator generates match expression', function () {
    $arm1 = ['condition' => new IR\Literal(1), 'body' => new IR\Literal('one')];
    $arm2 = ['condition' => new IR\Literal(2), 'body' => new IR\Literal('two')];
    $match = new IR\MatchExpression(new IR\Variable('x'), [$arm1, $arm2]);
    
    $gen = new JavaScriptGenerator([]);
    $result = $gen->generate($match);
    
    expect($result)->toContain('switch (x) {')
        ->and($result)->toContain('case 1: return "one";')
        ->and($result)->toContain('case 2: return "two";')
        ->and($result)->toContain('}');
});

test('JavaScriptGenerator generates echo statement', function () {
    $echo = new IR\EchoStatement([new IR\Literal('Hello'), new IR\Variable('name')]);
    $gen = new JavaScriptGenerator([]);
    $result = $gen->generate($echo);
    // JavaScriptGenerator concatenates multiple echo args with spaces
    expect($result)->toBe('console.log("Hello" + " " + name)');
});

test('JavaScriptGenerator generates print statement', function () {
    $print = new IR\PrintStatement(new IR\Variable('x'));
    $gen = new JavaScriptGenerator([]);
    expect($gen->generate($print))->toBe('console.log(x)');
});

test('JavaScriptGenerator generates return', function () {
    $return = new IR\ReturnStatement(new IR\Variable('x'));
    $gen = new JavaScriptGenerator([]);
    expect($gen->generate($return))->toBe('return x');
});

test('JavaScriptGenerator generates array access', function () {
    $access = new IR\ArrayAccess(new IR\Variable('arr'), new IR\Variable('i'));
    $gen = new JavaScriptGenerator([]);
    expect($gen->generate($access))->toBe('arr[i]');
});

test('JavaScriptGenerator generates method call', function () {
    $call = new IR\MethodCall(new IR\Variable('obj'), 'method', [new IR\Variable('arg')]);
    $gen = new JavaScriptGenerator([]);
    expect($gen->generate($call))->toBe('obj.method(arg)');
});

test('JavaScriptGenerator generates property access', function () {
    $access = new IR\PropertyAccess(new IR\Variable('obj'), 'property');
    $gen = new JavaScriptGenerator([]);
    expect($gen->generate($access))->toBe('obj.property');
});

test('JavaScriptGenerator generates array literal', function () {
    $array = new IR\ArrayLiteral([new IR\Literal(1), new IR\Literal(2), new IR\Literal(3)]);
    $gen = new JavaScriptGenerator([]);
    expect($gen->generate($array))->toBe('[1, 2, 3]');
});

test('JavaScriptGenerator generates nullsafe method call', function () {
    $nullsafe = new IR\NullsafeMethodCall(new IR\Variable('obj'), 'method', [new IR\Variable('arg')]);
    $gen = new JavaScriptGenerator([]);
    expect($gen->generate($nullsafe))->toBe('obj?.method(arg)');
});

test('JavaScriptGenerator generates nullsafe property access', function () {
    $nullsafe = new IR\NullsafePropertyAccess(new IR\Variable('obj'), 'property');
    $gen = new JavaScriptGenerator([]);
    expect($gen->generate($nullsafe))->toBe('obj?.property');
});

test('JavaScriptGenerator generates throw', function () {
    $throw = new IR\ThrowStatement(new IR\Literal('Error occurred'));
    $gen = new JavaScriptGenerator([]);
    expect($gen->generate($throw))->toBe('throw "Error occurred"');
});

test('JavaScriptGenerator generates try-catch', function () {
    $catch = new IR\CatchClause('Exception', 'e', new IR\EchoStatement([new IR\Variable('e')]));
    $tryCatch = new IR\TryCatchStatement(new IR\EchoStatement([new IR\Literal('try')]), [$catch], null);
    $gen = new JavaScriptGenerator([]);
    $result = $gen->generate($tryCatch);
    expect($result)->toContain('try {')
        ->and($result)->toContain('} catch (e) {')
        ->and($result)->toContain('console.log(e)')
        ->and($result)->toContain('}');
});

test('JavaScriptGenerator generates static call', function () {
    $call = new IR\StaticCall('Math', 'sqrt', [new IR\Variable('x')]);
    $gen = new JavaScriptGenerator([]);
    expect($gen->generate($call))->toBe('Math.sqrt(x)');
});

test('JavaScriptGenerator generates static property access', function () {
    $access = new IR\StaticPropertyAccess('Math', 'PI');
    $gen = new JavaScriptGenerator([]);
    expect($gen->generate($access))->toBe('Math.PI');
});

test('JavaScriptGenerator generates class const fetch', function () {
    $fetch = new IR\ClassConstFetch('Constant', 'VALUE');
    $gen = new JavaScriptGenerator([]);
    expect($gen->generate($fetch))->toBe('Constant.VALUE');
});

test('JavaScriptGenerator generates include', function () {
    $include = new IR\IncludeStatement('module.js', false, false);
    $gen = new JavaScriptGenerator([]);
    // JavaScriptGenerator comments out include statements
    expect($gen->generate($include))->toBe('// include \'module.js\'');
});

test('JavaScriptGenerator generates explode', function () {
    $call = new IR\FunctionCall('explode', [
        new IR\Literal(','),
        new IR\Variable('s')
    ]);
    $gen = new JavaScriptGenerator([]);
    $result = $gen->generate($call);
    expect($result)->toBe('s.split(",")');
});

test('JavaScriptGenerator generates implode', function () {
    $call = new IR\FunctionCall('implode', [
        new IR\Literal(', '),
        new IR\Variable('arr')
    ]);
    $gen = new JavaScriptGenerator([]);
    $result = $gen->generate($call);
    expect($result)->toBe('arr.join(", ")');
});

test('JavaScriptGenerator generates join', function () {
    $call = new IR\FunctionCall('join', [
        new IR\Literal(', '),
        new IR\Variable('arr')
    ]);
    $gen = new JavaScriptGenerator([]);
    $result = $gen->generate($call);
    expect($result)->toBe('arr.join(", ")');
});

test('JavaScriptGenerator generates str_contains', function () {
    $call = new IR\FunctionCall('str_contains', [
        new IR\Variable('s'),
        new IR\Literal('needle')
    ]);
    $gen = new JavaScriptGenerator([]);
    $result = $gen->generate($call);
    expect($result)->toBe('s.includes("needle")');
});

test('JavaScriptGenerator generates str_starts_with', function () {
    $call = new IR\FunctionCall('str_starts_with', [
        new IR\Variable('s'),
        new IR\Literal('prefix')
    ]);
    $gen = new JavaScriptGenerator([]);
    $result = $gen->generate($call);
    expect($result)->toBe('s.startsWith("prefix")');
});

test('JavaScriptGenerator generates str_ends_with', function () {
    $call = new IR\FunctionCall('str_ends_with', [
        new IR\Variable('s'),
        new IR\Literal('suffix')
    ]);
    $gen = new JavaScriptGenerator([]);
    $result = $gen->generate($call);
    expect($result)->toBe('s.endsWith("suffix")');
});

test('JavaScriptGenerator generates preg_match', function () {
    $call = new IR\FunctionCall('preg_match', [
        new IR\Literal('/^Hello/'),
        new IR\Variable('s')
    ]);
    $gen = new JavaScriptGenerator([]);
    $result = $gen->generate($call);
    expect($result)->toBe('new RegExp("/^Hello/").test(s)');
});

test('JavaScriptGenerator generates array_reduce', function () {
    $call = new IR\FunctionCall('array_reduce', [
        new IR\Variable('arr'),
        new IR\Variable('initial')
    ]);
    $gen = new JavaScriptGenerator([]);
    $result = $gen->generate($call);
    expect($result)->toBe('arr.reduce((acc, it) => acc + it, initial)');
});

test('JavaScriptGenerator generates array_unique', function () {
    $call = new IR\FunctionCall('array_unique', [
        new IR\Variable('arr')
    ]);
    $gen = new JavaScriptGenerator([]);
    $result = $gen->generate($call);
    expect($result)->toBe('[...new Set(arr)]');
});

test('JavaScriptGenerator generates array_diff', function () {
    $call = new IR\FunctionCall('array_diff', [
        new IR\Variable('arr1'),
        new IR\Variable('arr2')
    ]);
    $gen = new JavaScriptGenerator([]);
    $result = $gen->generate($call);
    expect($result)->toBe('arr1.filter(it => !arr2.includes(it))');
});

test('JavaScriptGenerator generates array_combine', function () {
    $call = new IR\FunctionCall('array_combine', [
        new IR\Variable('keys'),
        new IR\Variable('vals')
    ]);
    $gen = new JavaScriptGenerator([]);
    $result = $gen->generate($call);
    expect($result)->toContain('Object.fromEntries(keys.map((k, i) => [k, vals[i]]))');
});

test('JavaScriptGenerator generates array_intersect', function () {
    $call = new IR\FunctionCall('array_intersect', [
        new IR\Variable('arr1'),
        new IR\Variable('arr2')
    ]);
    $gen = new JavaScriptGenerator([]);
    $result = $gen->generate($call);
    expect($result)->toBe('arr1.filter(it => arr2.includes(it))');
});

test('JavaScriptGenerator generates array_product', function () {
    $call = new IR\FunctionCall('array_product', [
        new IR\Variable('arr')
    ]);
    $gen = new JavaScriptGenerator([]);
    $result = $gen->generate($call);
    expect($result)->toBe('arr.reduce((acc, it) => acc * it, 1)');
});
