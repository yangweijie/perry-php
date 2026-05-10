<?php

declare(strict_types=1);

namespace Perry\IR;

/**
 * Represents a type in the Perry IR system.
 * Used for type inference and code generation with type annotations.
 */
final class Type
{
    public const TYPE_INT = 'int';
    public const TYPE_FLOAT = 'float';
    public const TYPE_STRING = 'string';
    public const TYPE_BOOL = 'bool';
    public const TYPE_ARRAY = 'array';
    public const TYPE_NULL = 'null';
    public const TYPE_VOID = 'void';
    public const TYPE_ANY = 'any';
    public const TYPE_UNKNOWN = 'unknown';

    public function __construct(
        public readonly string $name,
        public readonly bool $isNullable = false,
        public readonly ?string $className = null,  // For class types
    ) {}

    public static function int(): self
    {
        return new self(self::TYPE_INT);
    }

    public static function float(): self
    {
        return new self(self::TYPE_FLOAT);
    }

    public static function string(): self
    {
        return new self(self::TYPE_STRING);
    }

    public static function bool(): self
    {
        return new self(self::TYPE_BOOL);
    }

    public static function array(): self
    {
        return new self(self::TYPE_ARRAY);
    }

    public static function null(): self
    {
        return new self(self::TYPE_NULL);
    }

    public static function void(): self
    {
        return new self(self::TYPE_VOID);
    }

    public static function any(): self
    {
        return new self(self::TYPE_ANY);
    }

    public static function unknown(): self
    {
        return new self(self::TYPE_UNKNOWN);
    }

    public static function class(string $className): self
    {
        return new self($className, false, $className);
    }

    public function isNullable(): bool
    {
        return $this->isNullable;
    }

    public function withNullable(bool $nullable): self
    {
        if ($this->isNullable === $nullable) {
            return $this;
        }
        return new self($this->name, $nullable, $this->className);
    }

    public function isPrimitive(): bool
    {
        return in_array($this->name, [self::TYPE_INT, self::TYPE_FLOAT, self::TYPE_STRING, self::TYPE_BOOL, self::TYPE_NULL]);
    }

    public function isNumber(): bool
    {
        return in_array($this->name, [self::TYPE_INT, self::TYPE_FLOAT]);
    }

    public function equals(self $other): bool
    {
        return $this->name === $other->name && $this->isNullable === $other->isNullable && $this->className === $other->className;
    }

    public function isAssignableTo(self $target): bool
    {
        // Any type can be assigned to any target
        if ($this->name === self::TYPE_ANY || $this->name === self::TYPE_UNKNOWN) {
            return true;
        }
        // Null can be assigned to nullable types
        if ($this->name === self::TYPE_NULL && $target->isNullable) {
            return true;
        }
        // int can be assigned to float (widening)
        if ($this->name === self::TYPE_INT && $target->name === self::TYPE_FLOAT) {
            return true;
        }
        // float cannot be assigned to int (narrowing)
        if ($this->name === self::TYPE_FLOAT && $target->name === self::TYPE_INT) {
            return false;
        }
        return $this->equals($target);
    }

    public function __toString(): string
    {
        $suffix = $this->isNullable ? '?' : '';
        if ($this->className !== null) {
            return $this->className . $suffix;
        }
        return $this->name . $suffix;
    }
}
