<?php

declare(strict_types=1);

namespace Perry\UI;

final class Binding
{
    public function __construct(
        public readonly string $name,
        public readonly mixed $initialValue,
    ) {}

    public function expr(string $expression): BoundExpression
    {
        return new BoundExpression($this, $expression);
    }
}

final class BoundExpression
{
    public function __construct(
        public readonly Binding $binding,
        public readonly string $expression,
    ) {}
}
