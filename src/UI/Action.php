<?php

declare(strict_types=1);

namespace Perry\UI;

use Perry\IR\Builder;
use Perry\IR\Generator;
use Perry\IR\Program;

enum ActionType: string
{
    case SetValue = 'set';
    case Append = 'append';
    case Clear = 'clear';
    case Calculate = 'calculate';
    case Custom = 'custom';
    case Closure = 'closure';
}

final class Action
{
    private ?Program $ir = null;

    public function __construct(
        public readonly ActionType $type,
        public readonly ?Binding $target = null,
        public readonly mixed $value = null,
        public readonly ?string $customCode = null,
        public readonly ?\Closure $closure = null,
        public readonly array $closureBindings = [],
    ) {}

    public static function set(Binding $target, mixed $value): self
    {
        return new self(ActionType::SetValue, $target, $value);
    }

    public static function append(Binding $target, string $value): self
    {
        return new self(ActionType::Append, $target, $value);
    }

    public static function clear(Binding $target): self
    {
        return new self(ActionType::Clear, $target);
    }

    public static function calculate(Binding $display, Binding $operand1, Binding $operand2, Binding $operation): self
    {
        return new self(ActionType::Calculate, $display, null, null);
    }

    public static function custom(string $code): self
    {
        return new self(ActionType::Custom, customCode: $code);
    }

    public static function fromClosure(\Closure $closure, array $bindings = []): self
    {
        return new self(ActionType::Closure, closure: $closure, closureBindings: $bindings);
    }

    public function getIr(): Program
    {
        if ($this->ir === null) {
            $builder = new Builder();
            if ($this->closure) {
                $this->ir = $builder->buildFromClosure($this->closure);
            } else {
                $this->ir = new Program();
            }
        }
        return $this->ir;
    }

    public function generate(Generator $generator): string
    {
        if ($this->type === ActionType::Custom) {
            return $this->customCode ?? '';
        }

        if ($this->type === ActionType::Closure) {
            $code = $this->getIr()->accept($generator);
            return $this->replaceBindings($code);
        }

        return '';
    }

    private function replaceBindings(string $code): string
    {
        foreach ($this->closureBindings as $name => $value) {
            if (is_string($value)) {
                $replacement = '"' . addslashes($value) . '"';
            } elseif (is_float($value)) {
                $replacement = (string) $value;
                if (!str_contains($replacement, '.')) {
                    $replacement .= '.0';
                }
            } elseif (is_int($value)) {
                $replacement = (string) $value;
            } elseif (is_bool($value)) {
                $replacement = $value ? 'true' : 'false';
            } else {
                $replacement = (string) $value;
            }
            // Use preg_replace_callback to avoid $0 backreference in replacement string
            $code = preg_replace_callback(
                '/\b' . preg_quote($name, '/') . '\b/',
                fn(array $m) => $replacement,
                $code
            );
        }
        return $code;
    }
}
