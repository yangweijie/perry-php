<?php

declare(strict_types=1);

namespace Perry\UI;

final readonly class StateId
{
    public function __construct(
        public string $id,
        public string $name = '',
    ) {}

    public static function next(): self
    {
        return new self('state_' . bin2hex(random_bytes(4)));
    }
}
