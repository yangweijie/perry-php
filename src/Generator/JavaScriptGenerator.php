<?php

declare(strict_types=1);

namespace Perry\Generator;

use Perry\IR;

class JavaScriptGenerator implements IR\Generator
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
            return "state.{$node->variable} = {$node->value->accept($this)}";
        }
        if (!in_array($node->variable, $this->declaredVars)) {
            $this->declaredVars[] = $node->variable;
            return "let {$node->variable} = {$node->value->accept($this)}";
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
        }

        $result .= $this->indent() . "}";
        return $result;
    }

    public function generateBinaryOp(IR\BinaryOp $node): string
    {
        $op = match ($node->op) {
            '.' => '+',
            '===' => '===',
            '!==' => '!==',
            default => $node->op,
        };

        if ($node->right instanceof IR\Literal && $node->right->value === false && $op === '===') {
            return "!{$node->left->accept($this)}";
        }

        return "{$node->left->accept($this)} {$op} {$node->right->accept($this)}";
    }

    public function generateUnaryOp(IR\UnaryOp $node): string
    {
        return "{$node->op}{$node->operand->accept($this)}";
    }

    public function generateVariable(IR\Variable $node): string
    {
        if (in_array($node->name, $this->stateVars)) {
            return "state.{$node->name}";
        }
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
        return (string) $node->value;
    }

    public function generateFunctionCall(IR\FunctionCall $node): string
    {
        $args = array_map(fn($arg) => $arg->accept($this), $node->args);

        $jsFunc = match ($node->name) {
            // String manipulation
            'substr' => $this->generateSubstr($args),
            'trim' => "{$args[0]}.trim()",
            'ltrim' => "{$args[0]}.replace(/^\\\\s+/, '')",
            'rtrim' => "{$args[0]}.replace(/\\\\s+$/, '')",
            'strtoupper' => "{$args[0]}.toUpperCase()",
            'strtolower' => "{$args[0]}.toLowerCase()",
            'ucfirst' => "{$args[0]}.charAt(0).toUpperCase() + {$args[0]}.slice(1)",
            'lcfirst' => "{$args[0]}.charAt(0).toLowerCase() + {$args[0]}.slice(1)",
            'str_replace' => "{$args[0]}.replace(new RegExp({$args[1]}, 'g'), {$args[2]})",
            'str_repeat' => "{$args[0]}.repeat(parseInt({$args[1]}) || 0)",
            'str_pad' => $this->generateStrPad($args),
            'strip_tags' => "{$args[0]}",
            'htmlspecialchars' => "{$args[0]}",
            'md5' => "md5({$args[0]})",
            'sha1' => "sha1({$args[0]})",

            // Type conversion
            'floatval', 'doubleval' => "parseFloat({$args[0]})",
            'intval', 'int' => "parseInt({$args[0]})",
            'strval' => "String({$args[0]})",

            // String operations
            'strlen' => "{$args[0]}.length",
            'strpos' => "(() => { const _i = {$args[0]}.indexOf({$args[1]}); return _i === -1 ? false : _i; })()",
            'substr_count' => "{$args[0]}.split({$args[1]}).length - 1",

            // Array operations
            'in_array' => "{$args[1]}.includes({$args[0]})",
            'empty' => "!{$args[0]}",
            'count' => "{$args[0]}.length",
            'array' => "[]",
            'array_push' => "{$args[0]}.push({$args[1]})",
            'array_keys' => "Object.keys({$args[0]})",
            'array_values' => "Object.values({$args[0]})",
            'array_merge' => "[...{$args[0]}, ...{$args[1]}]",
            'array_slice' => "Array.from({$args[0]}).slice(parseInt({$args[1]}) || 0, parseInt({$args[1]}) || 0 + (parseInt({$args[2]}) || 1))",
            'array_reverse' => "[...{$args[0]}].reverse()",
            'array_sum' => "[...{$args[0]}].reduce((a, b) => a + b, 0)",
            'array_map' => "[...{$args[1]}].map({$args[0]})",
            'array_filter' => "[...{$args[1]}].filter({$args[0]})",
            'array_search' => "[...{$args[1]}].indexOf({$args[0]})",
            'array_column' => "[...{$args[1]}].map(x => x[{$args[0]}] || '')",
            'array_flip' => "Object.fromEntries([...{$args[0]}].map(([k, v]) => [v, k]))",
            'array_fill' => "Array(parseInt({$args[0]}) || 0).fill({$args[1]})",
            'array_rand' => "{$args[0]}[Math.floor(Math.random() * {$args[0]}.length)]",
            'array_shift' => "[...{$args[0]}].shift()",
            'array_pop' => "[...{$args[0]}].pop()",
            'array_unshift' => "[...{$args[0]}].unshift({$args[1]})",
            'array_key_exists' => "Object.prototype.hasOwnProperty.call({$args[1]}, {$args[0]})",

            // Math functions
            'floor' => "Math.floor({$args[0]})",
            'ceil' => "Math.ceil({$args[0]})",
            'round' => "Math.round({$args[0]})",
            'abs' => "Math.abs({$args[0]})",
            'min' => "Math.min({$args[0]}, {$args[1]})",
            'max' => "Math.max({$args[0]}, {$args[1]})",
            'rand' => "Math.floor(Math.random() * (parseInt({$args[1]}) || 100))",
            'sqrt' => "Math.sqrt({$args[0]})",
            'log' => "Math.log({$args[0]})",
            'sin' => "Math.sin({$args[0]})",
            'cos' => "Math.cos({$args[0]})",
            'tan' => "Math.tan({$args[0]})",

            // Type checking
            'is_null' => "{$args[0]} === null",
            'is_array' => "Array.isArray({$args[0]})",
            'is_int' => "Number.isInteger({$args[0]})",
            'is_float' => "typeof {$args[0]} === 'number' && !Number.isInteger({$args[0]})",
            'is_string' => "typeof {$args[0]} === 'string'",
            'is_bool' => "typeof {$args[0]} === 'boolean'",
            'is_numeric' => "typeof {$args[0]} === 'number'",

            // Date/Time
            'time' => "Math.floor(Date.now() / 1000)",
            'date' => "new Date().toISOString()",

            // Encoding
            'urlencode' => "encodeURIComponent({$args[0]})",
            'urldecode' => "decodeURIComponent({$args[0]})",
            'base64_encode' => "btoa({$args[0]})",
            'base64_decode' => "atob({$args[0]})",

            // Formatting
            'number_format' => "{$args[0]}.toFixed(" . ($args[1] ?? '8') . ')',
            'sprintf' => $this->generateSprintf($args),
            'json_decode' => "JSON.parse({$args[0]})",
            'json_encode' => "JSON.stringify({$args[0]})",

            // Array helpers
            'preg_split' => $this->generatePregSplit($args),
            'end' => "{$args[0]}[{$args[0]}.length - 1]",

            default => "{$node->name}(" . implode(', ', $args) . ")",
        };

        return $jsFunc;
    }

    private function generateSubstr(array $args): string
    {
        if (count($args) === 2) {
            if ($args[1] === '-1' || $args[1] === '(-1)') {
                return "{$args[0]}.slice(-1)";
            }
            if (str_starts_with($args[1], '-')) {
                $offset = ltrim($args[1], '-');
                return "{$args[0]}.slice(0, -{$offset})";
            }
            return "{$args[0]}.slice({$args[1]})";
        }
        if (count($args) === 3) {
            if ($args[2] === '-1' || $args[2] === '(-1)') {
                return "{$args[0]}.slice(0, -1)";
            }
            return "{$args[0]}.slice({$args[1]}, {$args[1]} + {$args[2]})";
        }
        return "substr(" . implode(', ', $args) . ")";
    }

    private function generatePregSplit(array $args): string
    {
        $pattern = $args[0] instanceof IR\Literal ? $args[0]->value : '';
        $chars = $this->extractCharsFromRegex($pattern);
        $escaped = str_replace('"', '\\"', $chars);
        $jsRegex = $this->toJsRegex($pattern);
        return "{$args[1]}.split({$jsRegex})";
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

    private function toJsRegex(string $pattern): string
    {
        $pattern = trim($pattern, '/');
        $escaped = str_replace('/', '\\/', $pattern);
        return "/{$escaped}/";
    }

    private function generateStrPad(array $args): string
    {
        $length = $args[1] ?? '0';
        $padStr = $args[2] ?? '" "';
        $padType = $args[3] ?? 'STR_PAD_RIGHT';
        if ($padType === 'STR_PAD_LEFT') {
            return "{$args[0]}.padStart(parseInt({$length}) || 0, {$padStr})";
        }
        if ($padType === 'STR_PAD_BOTH') {
            return "{$args[0]}.padStart(parseInt({$length}) || 0, {$padStr})";
        }
        return "{$args[0]}.padEnd(parseInt({$length}) || 0, {$padStr})";
    }

    private function generateSprintf(array $args): string
    {
        return "sprintf({$args[0]}, " . implode(', ', array_slice($args, 1)) . ")";
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
        return '[' . implode(', ', $items) . ']';
    }

    // ============================================================
    // Loops
    // ============================================================

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
        $init = !empty($node->init) ? implode(', ', array_map(fn($n) => $n->accept($this), $node->init)) : '';
        $cond = !empty($node->cond) ? implode(', ', array_map(fn($n) => $n->accept($this), $node->cond)) : '';
        $loop = !empty($node->loop) ? implode(', ', array_map(fn($n) => $n->accept($this), $node->loop)) : '';

        $result = "for ({$init}; {$cond}; {$loop}) {\n";
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
            $result = "for (const [{$key}, {$value}] of Object.entries({$iterable})) {\n";
        } else {
            $result = "for (const {$value} of {$iterable}) {\n";
        }
        $this->indent++;
        $result .= $this->indent() . $node->body->accept($this) . "\n";
        $this->indent--;
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
        $result = "switch ({$condition}) {\n";
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
        $condition = $node->condition->accept($this);
        $result = "switch ({$condition}) {\n";
        $this->indent++;
        foreach ($node->arms as $arm) {
            $cond = $arm['condition']->accept($this);
            $body = $arm['body']->accept($this);
            $result .= $this->indent() . "case {$cond}: return {$body};\n";
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
        return 'console.log(' . implode(' + " " + ', $values) . ')';
    }

    public function generatePrint(IR\PrintStatement $node): string
    {
        return "console.log({$node->expr->accept($this)})";
    }

    // ============================================================
    // Type Casting
    // ============================================================

    public function generateCast(IR\Cast $node): string
    {
        $expr = $node->expr->accept($this);
        return match ($node->type) {
            'int', 'integer' => "parseInt({$expr})",
            'float', 'double' => "parseFloat({$expr})",
            'string' => "String({$expr})",
            'bool', 'boolean' => "Boolean({$expr})",
            'array' => "Array({$expr})",
            'object' => "Object({$expr})",
            default => $expr,
        };
    }

    // ============================================================
    // Increment / Decrement
    // ============================================================

    public function generateIncrement(IR\Increment $node): string
    {
        return $node->prefix ? "++{$node->variable}" : "{$node->variable}++";
    }

    public function generateDecrement(IR\Decrement $node): string
    {
        return $node->prefix ? "--{$node->variable}" : "{$node->variable}--";
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
        return "Math.pow({$node->left->accept($this)}, {$node->right->accept($this)})";
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
        return "({$left} !== {$right})";
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
        $result = "try {\n";
        $this->indent++;
        $result .= $this->indent() . $node->try->accept($this) . "\n";
        $this->indent--;
        $result .= $this->indent() . "}";

        if (!empty($node->catches)) {
            foreach ($node->catches as $catch) {
                $result .= ' ' . $catch->accept($this);
            }
        }

        if ($node->finally) {
            $result .= " finally {\n";
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
        return "catch ({$node->variable}) {\n{$this->indent()}    {$body}\n{$this->indent()}}";
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
        return "// include '{$node->path}'";
    }

    private function indent(): string
    {
        return str_repeat('    ', $this->indent);
    }
}
