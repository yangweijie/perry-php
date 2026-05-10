<?php

declare(strict_types=1);

use Perry\IR;
use Perry\IR\Type;
use Perry\IR\TypeInferer;

// ============================================================
// Type System Tests
// ============================================================

test('Type::int() creates int type', function () {
    $type = Type::int();
    expect($type->name)->toBe('int')
        ->and($type->isPrimitive())->toBeTrue()
        ->and($type->isNumber())->toBeTrue()
        ->and($type->isNullable())->toBeFalse();
});

test('Type::float() creates float type', function () {
    $type = Type::float();
    expect($type->name)->toBe('float')
        ->and($type->isPrimitive())->toBeTrue()
        ->and($type->isNumber())->toBeTrue();
});

test('Type::string() creates string type', function () {
    $type = Type::string();
    expect($type->name)->toBe('string')
        ->and($type->isPrimitive())->toBeTrue()
        ->and($type->isNumber())->toBeFalse();
});

test('Type::bool() creates bool type', function () {
    $type = Type::bool();
    expect($type->name)->toBe('bool')
        ->and($type->isPrimitive())->toBeTrue();
});

test('Type::array() creates array type', function () {
    $type = Type::array();
    expect($type->name)->toBe('array')
        ->and($type->isPrimitive())->toBeFalse();
});

test('Type::null() creates null type', function () {
    $type = Type::null();
    expect($type->name)->toBe('null')
        ->and($type->isPrimitive())->toBeTrue();
});

test('Type::class() creates class type', function () {
    $type = Type::class('Point');
    expect($type->name)->toBe('Point')
        ->and($type->className)->toBe('Point');
});

test('Type with nullable', function () {
    $type = Type::int()->withNullable(true);
    expect($type->name)->toBe('int')
        ->and($type->isNullable())->toBeTrue();
});

test('Type equals', function () {
    $t1 = Type::int();
    $t2 = Type::int();
    $t3 = Type::float();
    
    expect($t1->equals($t2))->toBeTrue()
        ->and($t1->equals($t3))->toBeFalse();
});

test('Type isAssignableTo', function () {
    expect(Type::int()->isAssignableTo(Type::int()))->toBeTrue()
        ->and(Type::int()->isAssignableTo(Type::float()))->toBeTrue()
        ->and(Type::float()->isAssignableTo(Type::int()))->toBeFalse()
        ->and(Type::null()->isAssignableTo(Type::int()->withNullable(true)))->toBeTrue()
        ->and(Type::any()->isAssignableTo(Type::int()))->toBeTrue();
});

test('Type __toString', function () {
    expect((string)Type::int())->toBe('int')
        ->and((string)Type::int()->withNullable(true))->toBe('int?')
        ->and((string)Type::class('Point'))->toBe('Point');
});

// ============================================================
// TypeInferer Tests
// ============================================================

test('TypeInferer infers literal int', function () {
    $inferer = new TypeInferer();
    $type = $inferer->infer(new IR\Literal(42));
    expect($type->name)->toBe('int');
});

test('TypeInferer infers literal float', function () {
    $inferer = new TypeInferer();
    $type = $inferer->infer(new IR\Literal(3.14));
    expect($type->name)->toBe('float');
});

test('TypeInferer infers literal string', function () {
    $inferer = new TypeInferer();
    $type = $inferer->infer(new IR\Literal('hello'));
    expect($type->name)->toBe('string');
});

test('TypeInferer infers literal bool', function () {
    $inferer = new TypeInferer();
    $type = $inferer->infer(new IR\Literal(true));
    expect($type->name)->toBe('bool');
});

test('TypeInferer infers literal null', function () {
    $inferer = new TypeInferer();
    $type = $inferer->infer(new IR\Literal(null));
    expect($type->name)->toBe('null');
});

test('TypeInferer infers variable type from environment', function () {
    $inferer = new TypeInferer();
    $inferer->setVariableType('x', Type::int());
    $type = $inferer->infer(new IR\Variable('x'));
    expect($type->name)->toBe('int');
});

test('TypeInferer infers unknown for undeclared variable', function () {
    $inferer = new TypeInferer();
    $type = $inferer->infer(new IR\Variable('unknown'));
    expect($type->name)->toBe('unknown');
});

test('TypeInferer infers int addition', function () {
    $inferer = new TypeInferer();
    $inferer->setVariableType('a', Type::int());
    $inferer->setVariableType('b', Type::int());
    $type = $inferer->infer(new IR\BinaryOp('+', new IR\Variable('a'), new IR\Variable('b')));
    expect($type->name)->toBe('int');
});

test('TypeInferer infers float addition with float operand', function () {
    $inferer = new TypeInferer();
    $inferer->setVariableType('a', Type::int());
    $inferer->setVariableType('b', Type::float());
    $type = $inferer->infer(new IR\BinaryOp('+', new IR\Variable('a'), new IR\Variable('b')));
    expect($type->name)->toBe('float');
});

test('TypeInferer infers string concatenation', function () {
    $inferer = new TypeInferer();
    $inferer->setVariableType('a', Type::string());
    $inferer->setVariableType('b', Type::string());
    $type = $inferer->infer(new IR\BinaryOp('.', new IR\Variable('a'), new IR\Variable('b')));
    expect($type->name)->toBe('string');
});

test('TypeInferer infers comparison returns bool', function () {
    $inferer = new TypeInferer();
    $type = $inferer->infer(new IR\BinaryOp('==', new IR\Variable('a'), new IR\Variable('b')));
    expect($type->name)->toBe('bool');
});

test('TypeInferer infers logical operations return bool', function () {
    $inferer = new TypeInferer();
    $type = $inferer->infer(new IR\BinaryOp('&&', new IR\Variable('a'), new IR\Variable('b')));
    expect($type->name)->toBe('bool');
});

test('TypeInferer infers unary not returns bool', function () {
    $inferer = new TypeInferer();
    $type = $inferer->infer(new IR\UnaryOp('!', new IR\Variable('x')));
    expect($type->name)->toBe('bool');
});

test('TypeInferer infers unary minus on number', function () {
    $inferer = new TypeInferer();
    $inferer->setVariableType('x', Type::int());
    $type = $inferer->infer(new IR\UnaryOp('-', new IR\Variable('x')));
    expect($type->name)->toBe('int');
});

test('TypeInferer infers assignment updates environment', function () {
    $inferer = new TypeInferer();
    $assign = new IR\Assignment('x', new IR\Literal(42));
    $type = $inferer->infer($assign);
    expect($type->name)->toBe('int');
    
    // Now x should be int in environment
    $varType = $inferer->infer(new IR\Variable('x'));
    expect($varType->name)->toBe('int');
});

test('TypeInferer infers array access returns any', function () {
    $inferer = new TypeInferer();
    $inferer->setVariableType('arr', Type::array());
    $type = $inferer->infer(new IR\ArrayAccess(new IR\Variable('arr'), new IR\Literal(0)));
    expect($type->name)->toBe('any');
});

test('TypeInferer infers array literal', function () {
    $inferer = new TypeInferer();
    $type = $inferer->infer(new IR\ArrayLiteral([new IR\Literal(1), new IR\Literal(2)]));
    expect($type->name)->toBe('array');
});

test('TypeInferer infers strlen returns int', function () {
    $inferer = new TypeInferer();
    $type = $inferer->infer(new IR\FunctionCall('strlen', [new IR\Variable('s')]));
    expect($type->name)->toBe('int');
});

test('TypeInferer infers substr returns string', function () {
    $inferer = new TypeInferer();
    $type = $inferer->infer(new IR\FunctionCall('substr', [new IR\Variable('s'), new IR\Literal(0), new IR\Literal(5)]));
    expect($type->name)->toBe('string');
});

test('TypeInferer infers count returns int', function () {
    $inferer = new TypeInferer();
    $type = $inferer->infer(new IR\FunctionCall('count', [new IR\Variable('arr')]));
    expect($type->name)->toBe('int');
});

test('TypeInferer infers is_null returns bool', function () {
    $inferer = new TypeInferer();
    $type = $inferer->infer(new IR\FunctionCall('is_null', [new IR\Variable('x')]));
    expect($type->name)->toBe('bool');
});

test('TypeInferer infers int() cast returns int', function () {
    $inferer = new TypeInferer();
    $type = $inferer->infer(new IR\FunctionCall('intval', [new IR\Variable('x')]));
    expect($type->name)->toBe('int');
});

test('TypeInferer infers floatval() cast returns float', function () {
    $inferer = new TypeInferer();
    $type = $inferer->infer(new IR\FunctionCall('floatval', [new IR\Variable('x')]));
    expect($type->name)->toBe('float');
});

test('TypeInferer infers strval() cast returns string', function () {
    $inferer = new TypeInferer();
    $type = $inferer->infer(new IR\FunctionCall('strval', [new IR\Variable('x')]));
    expect($type->name)->toBe('string');
});

test('TypeInferer infers ternary with matching types', function () {
    $inferer = new TypeInferer();
    $inferer->setVariableType('a', Type::int());
    $inferer->setVariableType('b', Type::int());
    $type = $inferer->infer(new IR\Ternary(
        new IR\Variable('cond'),
        new IR\Variable('a'),
        new IR\Variable('b')
    ));
    expect($type->name)->toBe('int');
});

test('TypeInferer infers ternary with mixed types returns any', function () {
    $inferer = new TypeInferer();
    $inferer->setVariableType('a', Type::int());
    $inferer->setVariableType('s', Type::string());
    $type = $inferer->infer(new IR\Ternary(
        new IR\Variable('cond'),
        new IR\Variable('a'),
        new IR\Variable('s')
    ));
    expect($type->name)->toBe('any');
});

test('TypeInferer infers int cast', function () {
    $inferer = new TypeInferer();
    $type = $inferer->infer(new IR\Cast('int', new IR\Variable('x')));
    expect($type->name)->toBe('int');
});

test('TypeInferer infers string cast', function () {
    $inferer = new TypeInferer();
    $type = $inferer->infer(new IR\Cast('string', new IR\Variable('x')));
    expect($type->name)->toBe('string');
});

test('TypeInferer infers new expression returns class type', function () {
    $inferer = new TypeInferer();
    $type = $inferer->infer(new IR\NewExpr('Point', []));
    expect($type->name)->toBe('Point');
});

test('TypeInferer infers array_push returns int', function () {
    $inferer = new TypeInferer();
    $type = $inferer->infer(new IR\FunctionCall('array_push', [new IR\Variable('arr'), new IR\Literal(1)]));
    expect($type->name)->toBe('int');
});
