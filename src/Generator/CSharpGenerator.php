<?php

declare(strict_types=1);

namespace Perry\Generator;

use Perry\IR;

class CSharpGenerator implements IR\Generator
{
    private int $indent = 0;
    private array $declaredVars = [];
    private array $stateVars = [];

    public function __construct(array $stateVars = [])
    {
        $this->stateVars = $stateVars;
    }

    public function generate(IR\Node $node): string
    {
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

    public function generateAssignment(IR\Assignment $node): string
    {
        if (in_array($node->variable, $this->stateVars)) {
            return "{$node->variable} = {$node->value->accept($this)};";
        }
        if (!in_array($node->variable, $this->declaredVars)) {
            $this->declaredVars[] = $node->variable;
            return "var {$node->variable} = {$node->value->accept($this)};";
        }
        return "{$node->variable} = {$node->value->accept($this)};";
    }

    public function generateIf(IR\IfStatement $node): string
    {
        $result = "if ({$node->condition->accept($this)})\n";
        $this->indent++;
        $result .= $this->indent() . "{\n";
        $this->indent++;
        $result .= $this->indent() . $node->then->accept($this) . "\n";
        $this->indent--;
        $result .= $this->indent() . "}";

        if ($node->else) {
            $result .= " else\n";
            $result .= $this->indent() . "{\n";
            $this->indent++;
            $result .= $this->indent() . $node->else->accept($this) . "\n";
            $this->indent--;
            $result .= $this->indent() . "}";
        }

        $this->indent--;
        return $result;
    }

    public function generateBinaryOp(IR\BinaryOp $node): string
    {
        $op = match ($node->op) {
            '===' => '==',
            '!==' => '!=',
            '.' => '+',
            '&&' => '&&',
            '||' => '||',
            default => $node->op,
        };

        if ($node->left instanceof IR\FunctionCall && $node->left->name === 'strpos') {
            if ($op === '==') {
                if ($node->right instanceof IR\Literal && $node->right->value === false) {
                    return "{$node->left->accept($this)} == -1";
                }
            } elseif ($op === '!=') {
                if ($node->right instanceof IR\Literal && $node->right->value === false) {
                    return "{$node->left->accept($this)} != -1";
                }
            }
        }

        return "{$node->left->accept($this)} {$op} {$node->right->accept($this)}";
    }

    public function generateUnaryOp(IR\UnaryOp $node): string
    {
        // Handle !strpos() - C# can't use ! on int, need to use == -1
        if ($node->op === '!' && $node->operand instanceof IR\FunctionCall && $node->operand->name === 'strpos') {
            return "{$node->operand->accept($this)} == -1";
        }
        return "{$node->op}{$node->operand->accept($this)}";
    }

    public function generateVariable(IR\Variable $node): string
    {
        return $node->name;
    }

    public function generateLiteral(IR\Literal $node): string
    {
        if (is_string($node->value)) {
            return '"' . addslashes($node->value) . '"';
        }
        if (is_bool($node->value)) {
            return $node->value ? 'true' : 'false';
        }
        if (is_null($node->value)) {
            return 'null';
        }
        if (is_float($node->value)) {
            $str = (string) $node->value;
            return str_contains($str, '.') ? $str . 'f' : $str . '.0f';
        }
        return (string) $node->value;
    }

    public function generateFunctionCall(IR\FunctionCall $node): string
    {
        $args = array_map(fn($arg) => $arg->accept($this), $node->args);

        $csFunc = match ($node->name) {
            'substr' => $this->generateSubstr($args),
            'floatval', 'doubleval' => "Convert.ToDouble({$args[0]})",
            'intval', 'int' => "Convert.ToInt32({$args[0]})",
            'strval' => "{$args[0]}.ToString()",
            'strlen' => "{$args[0]}.Length",
            'strpos' => "{$args[0]}.IndexOf({$args[1]})",
            'in_array' => "{$args[1]}.Contains({$args[0]})",
            'empty' => "string.IsNullOrEmpty({$args[0]})",
            'number_format' => "{$args[0]}.ToString(\"F{$args[1]}\")",
            'preg_split' => $this->generatePregSplit($args),
            'end' => "{$args[0]}.Last()",
            'floor' => "Math.Floor({$args[0]})",
            'array' => 'new[] { ' . implode(', ', $args) . ' }',
            default => "{$node->name}(" . implode(', ', $args) . ")",
        };

        return $csFunc;
    }

    private function generateSubstr(array $args): string
    {
        if (count($args) === 2) {
            if ($args[1] === '-1' || $args[1] === '(-1)') {
                return "{$args[0]}[{$args[0]}.Length - 1].ToString()";
            }
            if (str_starts_with($args[1], '-')) {
                $offset = ltrim($args[1], '-');
                return "{$args[0]}.Substring(0, {$args[0]}.Length - {$offset})";
            }
            return "{$args[0]}.Substring({$args[1]})";
        }
        if (count($args) === 3) {
            if ($args[2] === '-1' || $args[2] === '(-1)') {
                return "{$args[0]}.Substring(0, {$args[0]}.Length - 1)";
            }
            return "{$args[0]}.Substring({$args[1]}, {$args[2]})";
        }
        return "substr(" . implode(', ', $args) . ")";
    }

    private function generatePregSplit(array $args): string
    {
        $pattern = $args[0] instanceof IR\Literal ? $args[0]->value : '';
        $chars = $this->extractCharsFromRegex($pattern);
        $escaped = addcslashes($chars, '"\\');
        return "Regex.Split({$args[1]}, \"[{$escaped}]\")";
    }

    private function extractCharsFromRegex(string $pattern): string
    {
        $pattern = trim($pattern, '/');
        if (preg_match('/^\[\^?(.+)\]$/', $pattern, $m)) {
            return str_replace('\\-', '-', $m[1]);
        }
        $chars = '';
        $i = 0;
        $len = strlen($pattern);
        while ($i < $len) {
            if ($pattern[$i] === '\\' && $i + 1 < $len) {
                $i += 2;
            } else {
                $chars .= $pattern[$i];
                $i++;
            }
        }
        return $chars;
    }

    public function generateReturn(IR\ReturnStatement $node): string
    {
        return "return {$node->value->accept($this)}";
    }

    public function generateArrayAccess(IR\ArrayAccess $node): string
    {
        return "{$node->array->accept($this)}[{$node->index->accept($this)}]";
    }

    public function generateMethodCall(IR\MethodCall $node): string
    {
        $args = array_map(fn($arg) => $arg->accept($this), $node->args);
        return "{$node->object->accept($this)}.{$node->method}(" . implode(', ', $args) . ")";
    }

    public function generatePropertyAccess(IR\PropertyAccess $node): string
    {
        return "{$node->object->accept($this)}.{$node->property}";
    }

    public function generateTernary(IR\Ternary $node): string
    {
        $condition = $node->condition->accept($this);
        $then = $node->then ? $node->then->accept($this) : 'null';
        $else = $node->else->accept($this);
        return "{$condition} ? {$then} : {$else}";
    }

    public function generateArrayLiteral(IR\ArrayLiteral $node): string
    {
        $items = array_map(fn($item) => $item->accept($this), $node->items);
        return 'new[] { ' . implode(', ', $items) . ' }';
    }

    // ============================================================
    // Loops
    // ============================================================

    public function generateWhile(IR\WhileStatement $node): string
    {
        $result = "while ({$node->condition->accept($this)})\n";
        $result .= $this->indent() . "{\n";
        $this->indent++;
        $result .= $this->indent() . $node->body->accept($this) . "\n";
        $this->indent--;
        $result .= $this->indent() . "}";
        return $result;
    }

    public function generateFor(IR\ForStatement $node): string
    {
        $init = !empty($node->init) ? rtrim(implode('; ', array_map(fn($n) => rtrim($n->accept($this), ';'), $node->init)), ';') : '';
        $cond = !empty($node->cond) ? rtrim(implode('; ', array_map(fn($n) => rtrim($n->accept($this), ';'), $node->cond)), ';') : '';
        $loop = !empty($node->loop) ? rtrim(implode('; ', array_map(fn($n) => rtrim($n->accept($this), ';'), $node->loop)), ';') : '';

        $result = "for ({$init}; {$cond}; {$loop})\n";
        $result .= $this->indent() . "{\n";
        $this->indent++;
        $result .= $this->indent() . $node->body->accept($this) . "\n";
        $this->indent--;
        $result .= $this->indent() . "}";
        return $result;
    }

    public function generateForeach(IR\ForeachStatement $node): string
    {
        $value = $node->valueVar->accept($this);
        $iterable = $node->iterable->accept($this);

        if ($node->keyVar) {
            $key = $node->keyVar->accept($this);
            $result = "foreach (var kvp in {$iterable})\n";
            $result .= $this->indent() . "{\n";
            $this->indent++;
            $result .= $this->indent() . "var {$key} = kvp.Key;\n";
            $result .= $this->indent() . "var {$value} = kvp.Value;\n";
            $result .= $this->indent() . $node->body->accept($this) . "\n";
            $this->indent--;
        } else {
            $result = "foreach (var {$value} in {$iterable})\n";
            $result .= $this->indent() . "{\n";
            $this->indent++;
            $result .= $this->indent() . $node->body->accept($this) . "\n";
            $this->indent--;
        }
        $result .= $this->indent() . "}";
        return $result;
    }

    // ============================================================
    // Loop Control
    // ============================================================

    public function generateBreak(IR\BreakStatement $node): string
    {
        return 'break';
    }

    public function generateContinue(IR\ContinueStatement $node): string
    {
        return 'continue';
    }

    // ============================================================
    // Switch / Match
    // ============================================================

    public function generateSwitch(IR\SwitchStatement $node): string
    {
        $condition = $node->condition->accept($this);
        $result = "switch ({$condition})\n";
        $result .= $this->indent() . "{\n";
        $this->indent++;
        foreach ($node->cases as $case) {
            $result .= $this->indent() . $case->accept($this) . "\n";
        }
        $this->indent--;
        $result .= $this->indent() . "}";
        return $result;
    }

    public function generateCase(IR\CaseNode $node): string
    {
        $label = $node->condition ? "case {$node->condition->accept($this)}:" : 'default:';
        $body = $node->body->accept($this);
        return "{$label}\n{$this->indent()}    {$body}\n{$this->indent()}    break;";
    }

    public function generateMatch(IR\MatchExpression $node): string
    {
        // C# 8+ switch expression
        $condition = $node->condition->accept($this);
        $result = "{$condition} switch\n";
        $result .= $this->indent() . "{\n";
        $this->indent++;
        foreach ($node->arms as $arm) {
            $cond = $arm['condition']->accept($this);
            $body = $arm['body']->accept($this);
            $result .= $this->indent() . "{$cond} => {$body},\n";
        }
        $this->indent--;
        $result .= $this->indent() . "}";
        return $result;
    }

    // ============================================================
    // Output
    // ============================================================

    public function generateEcho(IR\EchoStatement $node): string
    {
        $values = array_map(fn($v) => $v->accept($this), $node->values);
        return 'Console.WriteLine(' . implode(' + " " + ', $values) . ')';
    }

    public function generatePrint(IR\PrintStatement $node): string
    {
        return "Console.WriteLine({$node->expr->accept($this)})";
    }

    // ============================================================
    // Type Casting
    // ============================================================

    public function generateCast(IR\Cast $node): string
    {
        $expr = $node->expr->accept($this);
        return match ($node->type) {
            'int', 'integer' => "(int)({$expr})",
            'float', 'double' => "(double)({$expr})",
            'string' => "({$expr}).ToString()",
            'bool', 'boolean' => "(bool)({$expr})",
            default => $expr,
        };
    }

    // ============================================================
    // Increment / Decrement
    // ============================================================

    public function generateIncrement(IR\Increment $node): string
    {
        return ($node->prefix ? "++{$node->variable}" : "{$node->variable}++") . ';';
    }

    public function generateDecrement(IR\Decrement $node): string
    {
        return ($node->prefix ? "--{$node->variable}" : "{$node->variable}--") . ';';
    }

    // ============================================================
    // Compound Assignment
    // ============================================================

    public function generatePlusAssign(IR\PlusAssign $node): string
    {
        $var = $this->generateVariable(new IR\Variable($node->variable));
        return "{$var} += {$node->value->accept($this)}";
    }

    public function generateMinusAssign(IR\MinusAssign $node): string
    {
        $var = $this->generateVariable(new IR\Variable($node->variable));
        return "{$var} -= {$node->value->accept($this)}";
    }

    public function generateMulAssign(IR\MulAssign $node): string
    {
        $var = $this->generateVariable(new IR\Variable($node->variable));
        return "{$var} *= {$node->value->accept($this)}";
    }

    public function generateDivAssign(IR\DivAssign $node): string
    {
        $var = $this->generateVariable(new IR\Variable($node->variable));
        return "{$var} /= {$node->value->accept($this)}";
    }

    public function generateModAssign(IR\ModAssign $node): string
    {
        $var = $this->generateVariable(new IR\Variable($node->variable));
        return "{$var} %= {$node->value->accept($this)}";
    }

    // ============================================================
    // Additional Binary Operators
    // ============================================================

    public function generatePow(IR\PowOp $node): string
    {
        return "Math.Pow({$node->left->accept($this)}, {$node->right->accept($this)})";
    }

    public function generateBitwiseAnd(IR\BitwiseAnd $node): string
    {
        return "{$node->left->accept($this)} & {$node->right->accept($this)}";
    }

    public function generateBitwiseOr(IR\BitwiseOr $node): string
    {
        return "{$node->left->accept($this)} | {$node->right->accept($this)}";
    }

    public function generateBitwiseXor(IR\BitwiseXor $node): string
    {
        return "{$node->left->accept($this)} ^ {$node->right->accept($this)}";
    }

    public function generateShiftLeft(IR\ShiftLeft $node): string
    {
        return "{$node->left->accept($this)} << {$node->right->accept($this)}";
    }

    public function generateShiftRight(IR\ShiftRight $node): string
    {
        return "{$node->left->accept($this)} >> {$node->right->accept($this)}";
    }

    public function generateSpaceship(IR\SpaceshipOp $node): string
    {
        $left = $node->left->accept($this);
        $right = $node->right->accept($this);
        return "({$left} < {$right} ? -1 : ({$left} > {$right} ? 1 : 0))";
    }

    public function generateCoalesce(IR\CoalesceOp $node): string
    {
        return "{$node->left->accept($this)} ?? {$node->right->accept($this)}";
    }

    public function generateLogicalAnd(IR\LogicalAnd $node): string
    {
        return "{$node->left->accept($this)} && {$node->right->accept($this)}";
    }

    public function generateLogicalOr(IR\LogicalOr $node): string
    {
        return "{$node->left->accept($this)} || {$node->right->accept($this)}";
    }

    public function generateLogicalXor(IR\LogicalXor $node): string
    {
        $left = $node->left->accept($this);
        $right = $node->right->accept($this);
        return "({$left} != {$right})";
    }

    // ============================================================
    // Additional Unary Operators
    // ============================================================

    public function generateUnaryPlus(IR\UnaryPlus $node): string
    {
        return "+({$node->operand->accept($this)})";
    }

    public function generateBitwiseNot(IR\BitwiseNot $node): string
    {
        return "~({$node->operand->accept($this)})";
    }

    // ============================================================
    // Nullsafe Operations
    // ============================================================

    public function generateNullsafeMethodCall(IR\NullsafeMethodCall $node): string
    {
        $args = array_map(fn($arg) => $arg->accept($this), $node->args);
        $obj = $node->object->accept($this);
        return "{$obj}?.{$node->method}(" . implode(', ', $args) . ")";
    }

    public function generateNullsafePropertyAccess(IR\NullsafePropertyAccess $node): string
    {
        $obj = $node->object->accept($this);
        return "{$obj}?.{$node->property}";
    }

    // ============================================================
    // Exceptions
    // ============================================================

    public function generateThrow(IR\ThrowStatement $node): string
    {
        return "throw {$node->expr->accept($this)}";
    }

    public function generateTryCatch(IR\TryCatchStatement $node): string
    {
        $result = "try\n";
        $result .= $this->indent() . "{\n";
        $this->indent++;
        $result .= $this->indent() . $node->try->accept($this) . "\n";
        $this->indent--;
        $result .= $this->indent() . "}";

        if (!empty($node->catches)) {
            foreach ($node->catches as $catch) {
                $result .= "\n" . $catch->accept($this);
            }
        }

        if ($node->finally) {
            $result .= "\nfinally\n";
            $result .= $this->indent() . "{\n";
            $this->indent++;
            $result .= $this->indent() . $node->finally->accept($this) . "\n";
            $this->indent--;
            $result .= $this->indent() . "}";
        }

        return $result;
    }

    public function generateCatchClause(IR\CatchClause $node): string
    {
        $body = $node->body->accept($this);
        return "catch ({$node->type} {$node->variable})\n{$this->indent()}}{\n{$this->indent()}    {$body}\n{$this->indent()}}";
    }

    // ============================================================
    // Static Operations
    // ============================================================

    public function generateStaticCall(IR\StaticCall $node): string
    {
        $args = array_map(fn($arg) => $arg->accept($this), $node->args);
        return "{$node->class}.{$node->method}(" . implode(', ', $args) . ")";
    }

    public function generateStaticPropertyAccess(IR\StaticPropertyAccess $node): string
    {
        return "{$node->class}.{$node->property}";
    }

    public function generateClassConstFetch(IR\ClassConstFetch $node): string
    {
        return "{$node->class}.{$node->constant}";
    }

    // ============================================================
    // Include
    // ============================================================

    public function generateInclude(IR\IncludeStatement $node): string
    {
        return "// include \"{$node->path}\"";
    }

    private function indent(): string
    {
        return str_repeat('    ', $this->indent);
    }
}
