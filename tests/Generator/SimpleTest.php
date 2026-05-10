<?php

declare(strict_types=1);

use Perry\IR\FunctionLiteral;
use Perry\IR\MethodParameter;
use Perry\IR\BinaryOp;
use Perry\IR\Variable;
use Perry\IR\Literal;

test('simple FunctionLiteral test', function () {
    $param = new MethodParameter('x', 'Int');
    $body = new BinaryOp('+', new Variable('x'), new Literal(1));
    $func = new FunctionLiteral([$param], $body, [], true);
    expect($func)->toBeInstanceOf(FunctionLiteral::class);
});
