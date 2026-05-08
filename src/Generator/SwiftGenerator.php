<?php

declare(strict_types=1);

namespace Perry\Generator;

use Perry\IR;

class SwiftGenerator implements IR\Generator
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
            return "{$node->variable} = {$node->value->accept($this)}";
        }
        if (!in_array($node->variable, $this->declaredVars)) {
            $this->declaredVars[] = $node->variable;
            return "var {$node->variable} = {$node->value->accept($this)}";
        }
        return "{$node->variable} = {$node->value->accept($this)}";
    }

    public function generateIf(IR\IfStatement $node): string
    {
        $result = "if {$node->condition->accept($this)} {\n";
        $this->indent++;
        $result .= $this->indent() . $node->then->accept($this) . "\n";
        $this->indent--;

        if ($node->else) {
            $result .= $this->indent() . "} else {\n";
            $this->indent++;
            $result .= $this->indent() . $node->else->accept($this) . "\n";
            $this->indent--;
        }

        $result .= $this->indent() . "}";
        return $result;
    }

    public function generateBinaryOp(IR\BinaryOp $node): string
    {
        $op = match ($node->op) {
            '.' => '+',
            '&&' => '&&',
            '||' => '||',
            '===' => '==',
            '!==' => '!=',
            default => $node->op,
        };

        if ($node->right instanceof IR\Literal && $node->right->value === false && $op === '==') {
            return "!{$node->left->accept($this)}";
        }

        $left = $node->left->accept($this);
        $right = $node->right->accept($this);

        // PHP's `.` (concat) maps to Swift `+`, but Swift doesn't auto-convert Int→String
        if ($node->op === '.') {
            $left = $this->wrapForConcat($node->left, $left);
            $right = $this->wrapForConcat($node->right, $right);
        }

        return "{$left} {$op} {$right}";
    }

    private function wrapForConcat(IR\Node $node, string $code): string
    {
        // String literals don't need wrapping
        if ($node instanceof IR\Literal && is_string($node->value)) {
            return $code;
        }
        // Function calls that return string (like substr, json_encode) don't need wrapping
        if ($node instanceof IR\FunctionCall && in_array($node->name, ['substr', 'strval', 'json_encode', 'json_decode'], true)) {
            return $code;
        }
        // Everything else (Int, Variable, etc.) needs String() wrapping
        return "String({$code})";
    }

    public function generateUnaryOp(IR\UnaryOp $node): string
    {
        $op = match ($node->op) {
            '!' => '!',
            default => $node->op,
        };

        return "{$op}{$node->operand->accept($this)}";
    }

    public function generateVariable(IR\Variable $node): string
    {
        return $node->name;
    }

    public function generateLiteral(IR\Literal $node): string
    {
        if (is_null($node->value)) {
            return 'nil';
        }
        if (is_string($node->value)) {
            return '"' . addslashes($node->value) . '"';
        }
        if (is_bool($node->value)) {
            return $node->value ? 'true' : 'false';
        }
        if (is_float($node->value)) {
            $str = (string) $node->value;
            return str_contains($str, '.') ? $str : $str . '.0';
        }
        return (string) $node->value;
    }

    public function generateFunctionCall(IR\FunctionCall $node): string
    {
        $args = array_map(fn($arg) => $arg->accept($this), $node->args);

        $swiftFunc = match ($node->name) {
            'substr' => $this->generateSubstr($args),
            'floatval', 'doubleval' => "Double({$args[0]}) ?? 0",
            'intval', 'int' => "Int({$args[0]})",
            'strval' => "String({$args[0]})",
            'strlen' => "{$args[0]}.count",
            'strpos' => "{$args[0]}.contains({$args[1]})",
            'in_array' => "{$args[1]}.contains({$args[0]})",
            'empty' => "{$args[0]}.isEmpty",
            'number_format' => "String(format: \"%." . ($args[1] ?? '8') . "f\", {$args[0]})",
            'preg_split' => $this->generatePregSplit($args),
            'end' => "{$args[0]}.last!",
            'floor' => "floor({$args[0]})",
            'ceil' => "ceil({$args[0]})",
            'round' => "round({$args[0]})",
            'array_push' => "{$args[0]}.append({$args[1]})",
            'json_decode' => $this->generateJsonDecode($args),
            'json_encode' => $this->generateJsonEncode($args),
            'json_last_error' => '0',
            'json_last_error_msg' => '"OK"',
            'is_null' => "{$args[0]} == nil",
            'is_array' => "{$args[0]} is NSArray",
            'count' => "({$args[0]} as! [Any]).count",
            default => "{$node->name}(" . implode(', ', $args) . ")",
        };

        return $swiftFunc;
    }

    private function generateSubstr(array $args): string
    {
        if (count($args) === 2) {
            if ($args[1] === '-1' || $args[1] === '(-1)') {
                return "String({$args[0]}.last!)";
            }
            if (str_starts_with($args[1], '-')) {
                $offset = ltrim($args[1], '-');
                return "String({$args[0]}.dropLast({$offset}))";
            }
            return "String({$args[0]}.dropFirst({$args[1]}))";
        }
        if (count($args) === 3) {
            if ($args[2] === '-1' || $args[2] === '(-1)') {
                return "String({$args[0]}.dropLast(1))";
            }
            return "String({$args[0]}.prefix({$args[2]}))";
        }
        return "substr(" . implode(', ', $args) . ")";
    }

    private function generatePregSplit(array $args): string
    {
        $pattern = $args[0] instanceof IR\Literal ? $args[0]->value : '';
        $chars = $this->extractCharsFromRegex($pattern);
        $escaped = str_replace('"', '\\"', $chars);
        return "{$args[1]}.components(separatedBy: CharacterSet(charactersIn: \"{$escaped}\"))";
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

    private function generateJsonDecode(array $args): string
    {
        $data = $args[0] ?? '""';
        return "(try? JSONSerialization.jsonObject(with: {$data}.data(using: .utf8)!, options: []))";
    }

    private function generateJsonEncode(array $args): string
    {
        $obj = $args[0] ?? '""';
        $pretty = !empty($args[1]);
        $opts = $pretty ? '.prettyPrinted' : '[]';
        return "(try? JSONSerialization.data(withJSONObject: {$obj}, options: {$opts})).flatMap { String(data: $0, encoding: .utf8) } ?? \"\"";
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
        $then = $node->then ? $node->then->accept($this) : 'nil';
        $else = $node->else->accept($this);
        return "{$condition} ? {$then} : {$else}";
    }

    public function generateArrayLiteral(IR\ArrayLiteral $node): string
    {
        $items = array_map(fn($item) => $item->accept($this), $node->items);
        return '[' . implode(', ', $items) . ']';
    }

    public function generateWhile(IR\WhileStatement $node): string
    {
        $result = "while {$node->condition->accept($this)} {\n";
        $this->indent++;
        $result .= $this->indent() . $node->body->accept($this) . "\n";
        $this->indent--;
        $result .= $this->indent() . "}";
        return $result;
    }

    public function generateFor(IR\ForStatement $node): string
    {
        $init = !empty($node->init) ? implode(', ', array_map(fn($n) => $n->accept($this), $node->init)) : '';
        $cond = !empty($node->cond) ? implode(', ', array_map(fn($n) => $n->accept($this), $node->cond)) : '';
        $loop = !empty($node->loop) ? implode(', ', array_map(fn($n) => $n->accept($this), $node->loop)) : '';

        $result = "for {$init}; {$cond}; {$loop} {\n";
        $this->indent++;
        $result .= $this->indent() . $node->body->accept($this) . "\n";
        $this->indent--;
        $result .= $this->indent() . "}";
        return $result;
    }

    public function generateForeach(IR\ForeachStatement $node): string
    {
        $key = $node->keyVar ? $node->keyVar->accept($this) . ', ' : '';
        $value = $node->valueVar->accept($this);
        $iterable = $node->iterable->accept($this);

        $result = "for {$key}{$value} in {$iterable} {\n";
        $this->indent++;
        $result .= $this->indent() . $node->body->accept($this) . "\n";
        $this->indent--;
        $result .= $this->indent() . "}";
        return $result;
    }

    public function generateBreak(IR\BreakStatement $node): string
    {
        return $node->depth > 1 ? "break {$node->depth}" : 'break';
    }

    public function generateContinue(IR\ContinueStatement $node): string
    {
        return $node->depth > 1 ? "continue {$node->depth}" : 'continue';
    }

    public function generateSwitch(IR\SwitchStatement $node): string
    {
        $condition = $node->condition->accept($this);
        $result = "switch {$condition} {\n";
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
        return "{$label}\n{$this->indent()}    {$body}\n{$this->indent()}    break";
    }

    public function generateMatch(IR\MatchExpression $node): string
    {
        $condition = $node->condition->accept($this);
        $result = "match {$condition} {\n";
        $this->indent++;
        foreach ($node->arms as $arm) {
            $cond = $arm['condition']->accept($this);
            $body = $arm['body']->accept($this);
            $result .= $this->indent() . "case {$cond}: {$body}\n";
        }
        $this->indent--;
        $result .= $this->indent() . "}";
        return $result;
    }

    public function generateEcho(IR\EchoStatement $node): string
    {
        $values = array_map(fn($v) => "print({$v->accept($this)})", $node->values);
        return implode(', ', $values);
    }

    public function generatePrint(IR\PrintStatement $node): string
    {
        return "print({$node->expr->accept($this)})";
    }

    public function generateCast(IR\Cast $node): string
    {
        $expr = $node->expr->accept($this);
        return match ($node->type) {
            'int' => "Int({$expr})",
            'float' => "Double({$expr})",
            'string' => "String({$expr})",
            'bool' => "Bool({$expr})",
            'array' => "Array({$expr})",
            'object' => "{$expr} as AnyObject",
            default => $expr,
        };
    }

    public function generateIncrement(IR\Increment $node): string
    {
        return $node->prefix ? "({$node->variable} += 1)" : "({$node->variable} += 1)";
    }

    public function generateDecrement(IR\Decrement $node): string
    {
        return $node->prefix ? "({$node->variable} -= 1)" : "({$node->variable} -= 1)";
    }

    public function generatePlusAssign(IR\PlusAssign $node): string
    {
        return "{$node->variable} += {$node->value->accept($this)}";
    }

    public function generateMinusAssign(IR\MinusAssign $node): string
    {
        return "{$node->variable} -= {$node->value->accept($this)}";
    }

    public function generateMulAssign(IR\MulAssign $node): string
    {
        return "{$node->variable} *= {$node->value->accept($this)}";
    }

    public function generateDivAssign(IR\DivAssign $node): string
    {
        return "{$node->variable} /= {$node->value->accept($this)}";
    }

    public function generateModAssign(IR\ModAssign $node): string
    {
        return "{$node->variable} %= {$node->value->accept($this)}";
    }

    public function generatePow(IR\PowOp $node): string
    {
        return "pow({$node->left->accept($this)}, {$node->right->accept($this)})";
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

    public function generateUnaryPlus(IR\UnaryPlus $node): string
    {
        return "+({$node->operand->accept($this)})";
    }

    public function generateBitwiseNot(IR\BitwiseNot $node): string
    {
        return "~({$node->operand->accept($this)})";
    }

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

    public function generateThrow(IR\ThrowStatement $node): string
    {
        return "throw {$node->expr->accept($this)}";
    }

    public function generateTryCatch(IR\TryCatchStatement $node): string
    {
        $result = "do {\n";
        $this->indent++;
        $result .= $this->indent() . $node->try->accept($this) . "\n";
        $this->indent--;
        $result .= $this->indent() . "} catch";

        if (!empty($node->catches)) {
            $catches = [];
            foreach ($node->catches as $catch) {
                $catches[] = $catch->accept($this);
            }
            $result .= ' ' . implode(' ', $catches);
        }

        if ($node->finally) {
            $result .= " {\n";
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
        return "catch {$node->type} as {$node->variable} {\n{$this->indent()}    {$body}\n{$this->indent()}}";
    }

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

    public function generateInclude(IR\IncludeStatement $node): string
    {
        return "// include '{$node->path}'";
    }

    private function indent(): string
    {
        return str_repeat('    ', $this->indent);
    }
}
