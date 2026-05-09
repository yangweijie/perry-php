<?php

declare(strict_types=1);

namespace Perry\IR;

abstract class Node
{
    abstract public function accept(Generator $generator): string;
}

final class Program extends Node
{
    /** @var Node[] */
    public array $statements = [];

    public function __construct(array $statements = [])
    {
        $this->statements = $statements;
    }

    public function accept(Generator $generator): string
    {
        return $generator->generateProgram($this);
    }
}

final class Assignment extends Node
{
    public function __construct(
        public readonly string $variable,
        public readonly Node $value,
    ) {}

    public function accept(Generator $generator): string
    {
        return $generator->generateAssignment($this);
    }
}

final class IfStatement extends Node
{
    public function __construct(
        public readonly Node $condition,
        public readonly Node $then,
        public readonly ?Node $else = null,
    ) {}

    public function accept(Generator $generator): string
    {
        return $generator->generateIf($this);
    }
}

final class BinaryOp extends Node
{
    public function __construct(
        public readonly string $op,
        public readonly Node $left,
        public readonly Node $right,
    ) {}

    public function accept(Generator $generator): string
    {
        return $generator->generateBinaryOp($this);
    }
}

final class UnaryOp extends Node
{
    public function __construct(
        public readonly string $op,
        public readonly Node $operand,
    ) {}

    public function accept(Generator $generator): string
    {
        return $generator->generateUnaryOp($this);
    }
}

final class Variable extends Node
{
    public function __construct(
        public readonly string $name,
    ) {}

    public function accept(Generator $generator): string
    {
        return $generator->generateVariable($this);
    }
}

final class Literal extends Node
{
    public function __construct(
        public readonly mixed $value,
    ) {}

    public function accept(Generator $generator): string
    {
        return $generator->generateLiteral($this);
    }
}

final class FunctionCall extends Node
{
    public function __construct(
        public readonly string $name,
        /** @var Node[] */
        public readonly array $args = [],
    ) {}

    public function accept(Generator $generator): string
    {
        return $generator->generateFunctionCall($this);
    }
}

final class ReturnStatement extends Node
{
    public function __construct(
        public readonly Node $value,
    ) {}

    public function accept(Generator $generator): string
    {
        return $generator->generateReturn($this);
    }
}

final class ArrayAccess extends Node
{
    public function __construct(
        public readonly Node $array,
        public readonly Node $index,
    ) {}

    public function accept(Generator $generator): string
    {
        return $generator->generateArrayAccess($this);
    }
}

final class MethodCall extends Node
{
    public function __construct(
        public readonly Node $object,
        public readonly string $method,
        /** @var Node[] */
        public readonly array $args = [],
    ) {}

    public function accept(Generator $generator): string
    {
        return $generator->generateMethodCall($this);
    }
}

final class PropertyAccess extends Node
{
    public function __construct(
        public readonly Node $object,
        public readonly string $property,
    ) {}

    public function accept(Generator $generator): string
    {
        return $generator->generatePropertyAccess($this);
    }
}

final class Ternary extends Node
{
    public function __construct(
        public readonly Node $condition,
        public readonly ?Node $then,
        public readonly Node $else,
    ) {}

    public function accept(Generator $generator): string
    {
        return $generator->generateTernary($this);
    }
}

final class ArrayLiteral extends Node
{
    /** @var Node[] */
    public array $items = [];

    public function __construct(array $items = [])
    {
        $this->items = $items;
    }

    public function accept(Generator $generator): string
    {
        return $generator->generateArrayLiteral($this);
    }
}

// ============================================================
// Loop Statements
// ============================================================

final class WhileStatement extends Node
{
    public function __construct(
        public readonly Node $condition,
        public readonly Node $body,
    ) {}

    public function accept(Generator $generator): string
    {
        return $generator->generateWhile($this);
    }
}

final class ForStatement extends Node
{
    /** @var Node[] */
    public array $init = [];
    /** @var Node[] */
    public array $cond = [];
    /** @var Node[] */
    public array $loop = [];
    public readonly Node $body;

    public function __construct(array $init, array $cond, array $loop, Node $body)
    {
        $this->init = $init;
        $this->cond = $cond;
        $this->loop = $loop;
        $this->body = $body;
    }

    public function accept(Generator $generator): string
    {
        return $generator->generateFor($this);
    }
}

final class ForeachStatement extends Node
{
    public function __construct(
        public readonly Node $valueVar,
        public readonly ?Node $keyVar,
        public readonly Node $iterable,
        public readonly Node $body,
    ) {}

    public function accept(Generator $generator): string
    {
        return $generator->generateForeach($this);
    }
}

// ============================================================
// Loop Control
// ============================================================

final class BreakStatement extends Node
{
    public function __construct(
        public readonly int $depth = 1,
    ) {}

    public function accept(Generator $generator): string
    {
        return $generator->generateBreak($this);
    }
}

final class ContinueStatement extends Node
{
    public function __construct(
        public readonly int $depth = 1,
    ) {}

    public function accept(Generator $generator): string
    {
        return $generator->generateContinue($this);
    }
}

// ============================================================
// Switch / Match
// ============================================================

final class SwitchStatement extends Node
{
    /** @var CaseNode[] */
    public array $cases = [];

    public function __construct(
        public readonly Node $condition,
        array $cases = [],
    ) {
        $this->cases = $cases;
    }

    public function accept(Generator $generator): string
    {
        return $generator->generateSwitch($this);
    }
}

final class CaseNode extends Node
{
    public function __construct(
        public readonly ?Node $condition, // null = default
        public readonly Node $body,
    ) {}

    public function accept(Generator $generator): string
    {
        return $generator->generateCase($this);
    }
}

final class MatchExpression extends Node
{
    /** @var array{condition: Node, body: Node}[] */
    public array $arms = [];

    public function __construct(
        public readonly Node $condition,
        array $arms = [],
    ) {
        $this->arms = $arms;
    }

    public function accept(Generator $generator): string
    {
        return $generator->generateMatch($this);
    }
}

// ============================================================
// Output
// ============================================================

final class EchoStatement extends Node
{
    /** @var Node[] */
    public array $values = [];

    public function __construct(array $values = [])
    {
        $this->values = $values;
    }

    public function accept(Generator $generator): string
    {
        return $generator->generateEcho($this);
    }
}

// ============================================================
// Type Casting
// ============================================================

final class Cast extends Node
{
    public function __construct(
        public readonly string $type, // int, float, string, bool, array, object
        public readonly Node $expr,
    ) {}

    public function accept(Generator $generator): string
    {
        return $generator->generateCast($this);
    }
}

// ============================================================
// Increment / Decrement
// ============================================================

final class Increment extends Node
{
    public function __construct(
        public readonly string $variable,
        public readonly bool $prefix = false,
    ) {}

    public function accept(Generator $generator): string
    {
        return $generator->generateIncrement($this);
    }
}

final class Decrement extends Node
{
    public function __construct(
        public readonly string $variable,
        public readonly bool $prefix = false,
    ) {}

    public function accept(Generator $generator): string
    {
        return $generator->generateDecrement($this);
    }
}

// ============================================================
// Additional Assignment Operators
// ============================================================

final class PlusAssign extends Node
{
    public function __construct(
        public readonly string $variable,
        public readonly Node $value,
    ) {}

    public function accept(Generator $generator): string
    {
        return $generator->generatePlusAssign($this);
    }
}

final class MinusAssign extends Node
{
    public function __construct(
        public readonly string $variable,
        public readonly Node $value,
    ) {}

    public function accept(Generator $generator): string
    {
        return $generator->generateMinusAssign($this);
    }
}

final class MulAssign extends Node
{
    public function __construct(
        public readonly string $variable,
        public readonly Node $value,
    ) {}

    public function accept(Generator $generator): string
    {
        return $generator->generateMulAssign($this);
    }
}

final class DivAssign extends Node
{
    public function __construct(
        public readonly string $variable,
        public readonly Node $value,
    ) {}

    public function accept(Generator $generator): string
    {
        return $generator->generateDivAssign($this);
    }
}

final class ModAssign extends Node
{
    public function __construct(
        public readonly string $variable,
        public readonly Node $value,
    ) {}

    public function accept(Generator $generator): string
    {
        return $generator->generateModAssign($this);
    }
}

// ============================================================
// Additional Binary Operators
// ============================================================

final class PowOp extends Node
{
    public function __construct(
        public readonly Node $left,
        public readonly Node $right,
    ) {}

    public function accept(Generator $generator): string
    {
        return $generator->generatePow($this);
    }
}

final class BitwiseAnd extends Node
{
    public function __construct(
        public readonly Node $left,
        public readonly Node $right,
    ) {}

    public function accept(Generator $generator): string
    {
        return $generator->generateBitwiseAnd($this);
    }
}

final class BitwiseOr extends Node
{
    public function __construct(
        public readonly Node $left,
        public readonly Node $right,
    ) {}

    public function accept(Generator $generator): string
    {
        return $generator->generateBitwiseOr($this);
    }
}

final class BitwiseXor extends Node
{
    public function __construct(
        public readonly Node $left,
        public readonly Node $right,
    ) {}

    public function accept(Generator $generator): string
    {
        return $generator->generateBitwiseXor($this);
    }
}

final class ShiftLeft extends Node
{
    public function __construct(
        public readonly Node $left,
        public readonly Node $right,
    ) {}

    public function accept(Generator $generator): string
    {
        return $generator->generateShiftLeft($this);
    }
}

final class ShiftRight extends Node
{
    public function __construct(
        public readonly Node $left,
        public readonly Node $right,
    ) {}

    public function accept(Generator $generator): string
    {
        return $generator->generateShiftRight($this);
    }
}

final class SpaceshipOp extends Node
{
    public function __construct(
        public readonly Node $left,
        public readonly Node $right,
    ) {}

    public function accept(Generator $generator): string
    {
        return $generator->generateSpaceship($this);
    }
}

final class CoalesceOp extends Node
{
    public function __construct(
        public readonly Node $left,
        public readonly Node $right,
    ) {}

    public function accept(Generator $generator): string
    {
        return $generator->generateCoalesce($this);
    }
}

final class LogicalAnd extends Node
{
    public function __construct(
        public readonly Node $left,
        public readonly Node $right,
    ) {}

    public function accept(Generator $generator): string
    {
        return $generator->generateLogicalAnd($this);
    }
}

final class LogicalOr extends Node
{
    public function __construct(
        public readonly Node $left,
        public readonly Node $right,
    ) {}

    public function accept(Generator $generator): string
    {
        return $generator->generateLogicalOr($this);
    }
}

final class LogicalXor extends Node
{
    public function __construct(
        public readonly Node $left,
        public readonly Node $right,
    ) {}

    public function accept(Generator $generator): string
    {
        return $generator->generateLogicalXor($this);
    }
}

// ============================================================
// Additional Unary Operators
// ============================================================

final class UnaryPlus extends Node
{
    public function __construct(
        public readonly Node $operand,
    ) {}

    public function accept(Generator $generator): string
    {
        return $generator->generateUnaryPlus($this);
    }
}

final class BitwiseNot extends Node
{
    public function __construct(
        public readonly Node $operand,
    ) {}

    public function accept(Generator $generator): string
    {
        return $generator->generateBitwiseNot($this);
    }
}

// ============================================================
// Nullsafe Operations
// ============================================================

final class NullsafeMethodCall extends Node
{
    public function __construct(
        public readonly Node $object,
        public readonly string $method,
        /** @var Node[] */
        public readonly array $args = [],
    ) {}

    public function accept(Generator $generator): string
    {
        return $generator->generateNullsafeMethodCall($this);
    }
}

final class NullsafePropertyAccess extends Node
{
    public function __construct(
        public readonly Node $object,
        public readonly string $property,
    ) {}

    public function accept(Generator $generator): string
    {
        return $generator->generateNullsafePropertyAccess($this);
    }
}

// ============================================================
// Exceptions
// ============================================================

final class ThrowStatement extends Node
{
    public function __construct(
        public readonly Node $expr,
    ) {}

    public function accept(Generator $generator): string
    {
        return $generator->generateThrow($this);
    }
}

final class TryCatchStatement extends Node
{
    /** @var CatchClause[] */
    public array $catches = [];
    public readonly ?Node $finally;

    public function __construct(
        public readonly Node $try,
        array $catches = [],
        ?Node $finally = null,
    ) {
        $this->catches = $catches;
        $this->finally = $finally;
    }

    public function accept(Generator $generator): string
    {
        return $generator->generateTryCatch($this);
    }
}

final class CatchClause extends Node
{
    public function __construct(
        public readonly string $type,
        public readonly string $variable,
        public readonly Node $body,
    ) {}

    public function accept(Generator $generator): string
    {
        return $generator->generateCatchClause($this);
    }
}

// ============================================================
// Static Operations
// ============================================================

final class StaticCall extends Node
{
    public function __construct(
        public readonly string $class,
        public readonly string $method,
        /** @var Node[] */
        public readonly array $args = [],
    ) {}

    public function accept(Generator $generator): string
    {
        return $generator->generateStaticCall($this);
    }
}

final class StaticPropertyAccess extends Node
{
    public function __construct(
        public readonly string $class,
        public readonly string $property,
    ) {}

    public function accept(Generator $generator): string
    {
        return $generator->generateStaticPropertyAccess($this);
    }
}

final class ClassConstFetch extends Node
{
    public function __construct(
        public readonly string $class,
        public readonly string $constant,
    ) {}

    public function accept(Generator $generator): string
    {
        return $generator->generateClassConstFetch($this);
    }
}

// ============================================================
// Include / Require
// ============================================================

final class IncludeStatement extends Node
{
    public function __construct(
        public readonly string $path,
        public readonly bool $once = false,
        public readonly bool $require = false,
    ) {}

    public function accept(Generator $generator): string
    {
        return $generator->generateInclude($this);
    }
}

// ============================================================
// Print (expression, not statement)
// ============================================================

final class PrintStatement extends Node
{
    public function __construct(
        public readonly Node $expr,
    ) {}

    public function accept(Generator $generator): string
    {
        return $generator->generatePrint($this);
    }
}
