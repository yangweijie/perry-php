<?php

declare(strict_types=1);

namespace Perry\Generator;

use Perry\IR;

/**
 * Abstract base for language generators (Swift, Kotlin, Dart, C#, JS).
 * Provides shared implementations for structurally identical IR node types.
 *
 * Subclasses override:
 * - generateLiteral() - string escaping, boolean/null syntax
 * - generateBinaryOp(), generateUnaryOp() - operator symbol mappings
 * - generateIf(), generateWhile(), generateFor(), generateForeach() - control flow
 * - generateSwitch(), generateMatch() - switch/match syntax
 * - generateFunctionCall() - PHP function → language specific calls
 * - generateAssignment(), generateIncrement(), generateDecrement() - variable handling
 */
abstract class AbstractGenerator implements IR\Generator
{
    protected int $indent = 0;
    protected array $declaredVars = [];
    protected array $stateVars = [];

    public function __construct(array $stateVars = [])
    {
        $this->stateVars = $stateVars;
    }

    public function generate(IR\Node $node): string
    {
        $this->resetState();
        return $node->accept($this);
    }

    public function generateProgram(IR\Program $node): string
    {
        $stmts = [];
        foreach ($node->statements as $stmt) {
            $stmts[] = $this->indent() . $stmt->accept($this);
        }
        return implode("\n", $stmts);
    }

    public function generateVariable(IR\Variable $node): string
    {
        return $node->name;
    }

    public function generateArrayAccess(IR\ArrayAccess $node): string
    {
        return $node->array->accept($this) . '[' . $node->key->accept($this) . ']';
    }

    public function generateMethodCall(IR\MethodCall $node): string
    {
        return $node->object->accept($this) . '.' . $node->method . '(' . $this->args($node->args) . ')';
    }

    public function generatePropertyAccess(IR\PropertyAccess $node): string
    {
        return $node->object->accept($this) . '.' . $node->property;
    }

    public function generateTernary(IR\Ternary $node): string
    {
        $cond = $node->condition->accept($this);
        $then = $node->ifTrue->accept($this);
        $else = $node->ifFalse->accept($this);
        return "{$cond} ? {$then} : {$else}";
    }

    public function generateReturn(IR\ReturnStatement $node): string
    {
        $val = $node->value ? $node->value->accept($this) : '';
        return $val !== '' ? "return {$val}" : 'return';
    }

    public function generateBreak(IR\BreakStatement $node): string
    {
        return 'break';
    }

    public function generateContinue(IR\ContinueStatement $node): string
    {
        return 'continue';
    }

    public function generateClosure(IR\Closure $node): string
    {
        $params = $this->closureParams($node);
        $body = $node->body instanceof IR\Program
            ? $this->generateProgram($node->body)
            : $node->body->accept($this);
        return "function({$params}) {\n{$body}\n{$this->indent()}}";
    }

    public function generateDeclaration(IR\Declaration $node): string
    {
        return "{$node->name} = {$node->value->accept($this)}";
    }

    public function generateNull(IR\NullLiteral $node): string
    {
        return 'null';
    }

    // --- Shared helpers ---

    protected function indent(): string
    {
        return str_repeat('    ', $this->indent);
    }

    protected function args(array $args): string
    {
        $parts = [];
        foreach ($args as $arg) {
            $parts[] = ($arg instanceof IR\Node) ? $arg->accept($this) : (string) $arg;
        }
        return implode(', ', $parts);
    }

    protected function closureParams(IR\Closure $node): string
    {
        $params = [];
        foreach ($node->params as $param) {
            $params[] = $param instanceof IR\Variable ? $param->name : (string) $param;
        }
        return implode(', ', $params);
    }

    protected function resetState(): void
    {
        $this->indent = 0;
        $this->declaredVars = [];
    }
}
