<?php

declare(strict_types=1);

namespace Perry\UI;

/**
 * Opaque handle to a widget in the platform-specific UI tree.
 * Mirrors perry-ts: WidgetHandle = i64
 * 
 * In PHP we use string UUIDs for portability, but maintain the handle-based
 * abstraction so platform drivers can map to native handles.
 */
final readonly class WidgetHandle
{
    public function __construct(
        public string $id,
    ) {}

    public static function next(): self
    {
        return new self(bin2hex(random_bytes(8)));
    }

    public function equals(self $other): bool
    {
        return $this->id === $other->id;
    }

    public function __toString(): string
    {
        return $this->id;
    }
}
