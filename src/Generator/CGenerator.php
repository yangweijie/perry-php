<?php

declare(strict_types=1);

namespace Perry\Generator;

use Perry\IR;

/**
 * Generates C code from Perry IR nodes.
 * Used for Gtk4 codegen and Gtk4 action closures.
 */
class CGenerator implements IR\Generator
{
    private int $indent = 0;
    private array $declaredVars = [];

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
        if (!in_array($node->variable, $this->declaredVars)) {
            $this->declaredVars[] = $node->variable;
            return "int {$node->variable} = {$node->value->accept($this)}";
        }
        return "{$node->variable} = {$node->value->accept($this)}";
    }

    public function generateIf(IR\IfStatement $node): string
    {
        $result = "if ({$node->condition->accept($this)}) {\n";
        $this->indent++;
        $result .= $this->indent() . $node->then->accept($this) . "\n";
        $this->indent--;

        if ($node->else) {
            $result .= $this->indent() . "} else {\n";
            $this->indent++;
            $result .= $this->indent() . $node->else->accept($this) . "\n";
            $this->indent--;
            $result .= $this->indent() . "}";
        } else {
            $result .= $this->indent() . "}";
        }

        return $result;
    }

    public function generateBinaryOp(IR\BinaryOp $node): string
    {
        return "({$node->left->accept($this)} {$node->op} {$node->right->accept($this)})";
    }

    public function generateUnaryOp(IR\UnaryOp $node): string
    {
        return "{$node->op}{$node->operand->accept($this)}";
    }

    public function generateVariable(IR\Variable $node): string
    {
        return $node->name;
    }

    public function generateLiteral(IR\Literal $node): string
    {
        $value = $node->value;
        if (is_bool($value)) {
            return $value ? "TRUE" : "FALSE";
        }
        if (is_int($value)) {
            return (string) $value;
        }
        if (is_float($value)) {
            return (string) $value;
        }
        if (is_string($value)) {
            // Escape quotes and backslashes for C string
            $escaped = str_replace(['\\', '"'], ['\\\\', '\\"'], $value);
            return "\"{$escaped}\"";
        }
        if ($value === null) {
            return "NULL";
        }
        return (string) $value;
    }

    public function generateFunctionCall(IR\FunctionCall $node): string
    {
        $args = array_map(fn($arg) => $arg->accept($this), $node->args);
        return "{$node->name}(" . implode(', ', $args) . ")";
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
        return "{$node->object->accept($this)}->{$node->method}(" . implode(', ', $args) . ")";
    }

    public function generatePropertyAccess(IR\PropertyAccess $node): string
    {
        return "{$node->object->accept($this)}->{$node->property}";
    }

    public function generateTernary(IR\Ternary $node): string
    {
        $then = $node->then ? $node->then->accept($this) : 'NULL';
        return "({$node->condition->accept($this)} ? {$then} : {$node->else->accept($this)})";
    }

    public function generateArrayLiteral(IR\ArrayLiteral $node): string
    {
        // C doesn't have array literals in the same way; return a placeholder
        return "{}";
    }

    public function generateWhile(IR\WhileStatement $node): string
    {
        $result = "while ({$node->condition->accept($this)}) {\n";
        $this->indent++;
        $result .= $this->indent() . $node->body->accept($this) . "\n";
        $this->indent--;
        $result .= $this->indent() . "}";
        return $result;
    }

    public function generateFor(IR\ForStatement $node): string
    {
        $init = implode(', ', array_map(fn($e) => $e->accept($this), $node->init));
        $cond = implode(', ', array_map(fn($e) => $e->accept($this), $node->cond));
        $loop = implode(', ', array_map(fn($e) => $e->accept($this), $node->loop));
        $result = "for ({$init}; {$cond}; {$loop}) {\n";
        $this->indent++;
        $result .= $this->indent() . $node->body->accept($this) . "\n";
        $this->indent--;
        $result .= $this->indent() . "}";
        return $result;
    }

    public function generateForeach(IR\ForeachStatement $node): string
    {
        // C doesn't have foreach; use a for loop over array length
        $iterable = $node->iterable->accept($this);
        $result = "for (int _i = 0; _i < " . $iterable . "_len; _i++) {\n";
        $this->indent++;
        $result .= $this->indent() . "{$node->valueVar->accept($this)} = {$iterable}[_i];\n";
        $result .= $this->indent() . $node->body->accept($this) . "\n";
        $this->indent--;
        $result .= $this->indent() . "}";
        return $result;
    }

    public function generateBreak(IR\BreakStatement $node): string
    {
        return "break";
    }

    public function generateContinue(IR\ContinueStatement $node): string
    {
        return "continue";
    }

    public function generateSwitch(IR\SwitchStatement $node): string
    {
        $result = "switch ({$node->condition->accept($this)}) {\n";
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
        if ($node->condition === null) {
            return "default:\n" . $this->indent() . "    {$node->body->accept($this)}";
        }
        return "case {$node->condition->accept($this)}:\n" . $this->indent() . "    {$node->body->accept($this)}";
    }

    public function generateMatch(IR\MatchExpression $node): string
    {
        // C doesn't have match; use if-else chain
        $result = '';
        foreach ($node->arms as $i => $arm) {
            if ($i === 0) {
                $result = "if ({$arm['condition']->accept($this)}) {\n";
            } else {
                $result .= $this->indent() . "} else if ({$arm['condition']->accept($this)}) {\n";
            }
            $this->indent++;
            $result .= $this->indent() . $arm['body']->accept($this) . "\n";
            $this->indent--;
        }
        $result .= $this->indent() . "}";
        return $result;
    }

    public function generateEcho(IR\EchoStatement $node): string
    {
        $values = array_map(fn($v) => $v->accept($this), $node->values);
        $format = implode(' ', array_map(fn($v) => '%s', $values));
        return "g_print(\"{$format}\\n\", " . implode(', ', $values) . ")";
    }

    public function generatePrint(IR\PrintStatement $node): string
    {
        return "g_print(\"%s\\n\", {$node->expr->accept($this)})";
    }

    public function generateCast(IR\Cast $node): string
    {
        $cType = match ($node->type) {
            'int' => 'int',
            'float' => 'double',
            'string' => 'char*',
            'bool' => 'gboolean',
            'array' => 'gpointer',
            'object' => 'gpointer',
            default => 'gpointer',
        };
        return "({$cType}){$node->expr->accept($this)}";
    }

    public function generateIncrement(IR\Increment $node): string
    {
        return $node->prefix ? "(++{$node->variable})" : "({$node->variable}++)";
    }

    public function generateDecrement(IR\Decrement $node): string
    {
        return $node->prefix ? "(--{$node->variable})" : "({$node->variable}--)";
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
        return "({$node->left->accept($this)} & {$node->right->accept($this)})";
    }

    public function generateBitwiseOr(IR\BitwiseOr $node): string
    {
        return "({$node->left->accept($this)} | {$node->right->accept($this)})";
    }

    public function generateBitwiseXor(IR\BitwiseXor $node): string
    {
        return "({$node->left->accept($this)} ^ {$node->right->accept($this)})";
    }

    public function generateShiftLeft(IR\ShiftLeft $node): string
    {
        return "({$node->left->accept($this)} << {$node->right->accept($this)})";
    }

    public function generateShiftRight(IR\ShiftRight $node): string
    {
        return "({$node->left->accept($this)} >> {$node->right->accept($this)})";
    }

    public function generateSpaceship(IR\SpaceshipOp $node): string
    {
        // C doesn't have spaceship; use comparison
        $left = $node->left->accept($this);
        $right = $node->right->accept($this);
        return "(($left < $right) ? -1 : (($left > $right) ? 1 : 0))";
    }

    public function generateCoalesce(IR\CoalesceOp $node): string
    {
        $left = $node->left->accept($this);
        $right = $node->right->accept($this);
        return "(($left) ? ($left) : ($right))";
    }

    public function generateLogicalAnd(IR\LogicalAnd $node): string
    {
        return "({$node->left->accept($this)} && {$node->right->accept($this)})";
    }

    public function generateLogicalOr(IR\LogicalOr $node): string
    {
        return "({$node->left->accept($this)} || {$node->right->accept($this)})";
    }

    public function generateLogicalXor(IR\LogicalXor $node): string
    {
        return "({$node->left->accept($this)} != {$node->right->accept($this)})";
    }

    public function generateUnaryPlus(IR\UnaryPlus $node): string
    {
        return "+{$node->operand->accept($this)}";
    }

    public function generateBitwiseNot(IR\BitwiseNot $node): string
    {
        return "(~{$node->operand->accept($this)})";
    }

    public function generateNullsafeMethodCall(IR\NullsafeMethodCall $node): string
    {
        $args = array_map(fn($arg) => $arg->accept($this), $node->args);
        return "({$node->object->accept($this)} ? {$node->object->accept($this)}->{$node->method}(" . implode(', ', $args) . ") : NULL)";
    }

    public function generateNullsafePropertyAccess(IR\NullsafePropertyAccess $node): string
    {
        return "({$node->object->accept($this)} ? {$node->object->accept($this)}->{$node->property} : NULL)";
    }

    public function generateThrow(IR\ThrowStatement $node): string
    {
        return "g_error(\"%s\", {$node->expr->accept($this)})";
    }

    public function generateTryCatch(IR\TryCatchStatement $node): string
    {
        // C doesn't have try-catch; use GError
        $result = '';
        foreach ($node->catches as $catch) {
            $result .= $this->indent() . $catch->accept($this) . "\n";
        }
        return $result;
    }

    public function generateCatchClause(IR\CatchClause $node): string
    {
        return "GError *{$node->variable} = NULL;";
    }

    public function generateStaticCall(IR\StaticCall $node): string
    {
        $args = array_map(fn($arg) => $arg->accept($this), $node->args);
        return "{$node->class}::{$node->method}(" . implode(', ', $args) . ")";
    }

    public function generateStaticPropertyAccess(IR\StaticPropertyAccess $node): string
    {
        return "{$node->class}::{$node->property}";
    }

    public function generateClassConstFetch(IR\ClassConstFetch $node): string
    {
        return "{$node->class}::{$node->constant}";
    }

    public function generateInclude(IR\IncludeStatement $node): string
    {
        $prefix = $node->require ? '#include' : '#include';
        $once = $node->once ? '' : '';
        return "{$prefix} \"{$node->path}\"";
    }

    // ============================================================
    // Class / Object Support (Passthrough for C)
    // ============================================================

    public function generatePropertyDeclaration(IR\PropertyDeclaration $node): string
    {
        $type = $node->type ?? 'void';
        return "{$type} {$node->name}";
    }

    public function generateMethodParameter(IR\MethodParameter $node): string
    {
        $type = $node->type ?? 'void';
        return "{$type} {$node->name}";
    }

    public function generateMethodDeclaration(IR\MethodDeclaration $node): string
    {
        $static = $node->isStatic ? 'static ' : '';
        $params = implode(', ', array_map(fn($p) => $p->accept($this), $node->params));
        $returnType = $node->returnType ?? 'void';
        
        if ($node->body === null) {
            return "{$static}{$returnType} {$node->name}({$params});";
        }
        
        $body = $node->body->accept($this);
        return "{$static}{$returnType} {$node->name}({$params}) {\n{$this->indent()}    {$body}\n{$this->indent()}}}";
    }

    public function generateClassDeclaration(IR\ClassDeclaration $node): string
    {
        // C uses struct for classes
        $lines = ["typedef struct {$node->name} {"];
        $this->indent++;
        
        foreach ($node->properties as $prop) {
            $lines[] = $this->indent() . $prop->accept($this) . ';';
        }
        
        $this->indent--;
        $lines[] = $this->indent() . "} {$node->name};";
        
        // Methods are separate functions
        foreach ($node->methods as $method) {
            $lines[] = $this->indent() . $method->accept($this);
        }
        
        return implode("\n", $lines);
    }

    public function generateNewExpr(IR\NewExpr $node): string
    {
        // C uses malloc for object creation
        $args = implode(', ', array_map(fn($arg) => $arg->accept($this), $node->args));
        return "calloc(1, sizeof(struct {$node->className}))";
    }

    public function generateFunctionLiteral(IR\FunctionLiteral $node): string
    {
        // C doesn't have closures; use function pointer or forward declaration
        $params = implode(', ', array_map(fn($p) => $p->accept($this), $node->params));
        
        if ($node->isArrow && $node->body !== null) {
            // Inline expression - just return the body
            return $node->body->accept($this);
        }
        
        // For block closures, C uses a named function
        // This is a placeholder - in real usage, the function would be declared separately
        $body = $node->body !== null ? $node->body->accept($this) : '';
        $indent = $this->indent();
        return "// closure: function pointer needed\n{$indent}void _closure_func($params) {\n{$indent}    {$body}\n{$indent}}}";
    }

    // ============================================================
    // Array Operations (Passthrough for C)
    // ============================================================

    public function generateArrayPop(IR\ArrayPop $node): string
    {
        return "{$node->array}->data[{$node->array}->len - 1]";
    }

    public function generateArrayUnshift(IR\ArrayUnshift $node): string
    {
        return "g_array_prepend({$node->array}, {$node->value})";
    }

    public function generateArrayKeyExists(IR\ArrayKeyExists $node): string
    {
        return "g_hash_table_contains({$node->array}, {$node->key})";
    }

    public function generateArrayReduce(IR\ArrayReduce $node): string
    {
        // C doesn't have reduce; use loop
        return "{$node->array}->data[0]";
    }

    public function generateArrayUnique(IR\ArrayUnique $node): string
    {
        // C doesn't have unique; use g_hash_table
        return "{$node->array}";
    }

    public function generateArrayDiff(IR\ArrayDiff $node): string
    {
        // C doesn't have diff; use loop
        return "{$node->array}";
    }

    private function indent(): string
    {
        return str_repeat('    ', $this->indent);
    }
}
