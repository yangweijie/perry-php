<?php

declare(strict_types=1);

namespace Perry\Generator;

use Perry\IR;

class KotlinGenerator implements IR\Generator
{
    private int $indent = 0;
    private array $declaredVars = [];
    private array $stateVars = [];
    private array $varTypes = []; // variable name => 'Double'

    public function __construct(array $stateVars = [])
    {
        $this->stateVars = $stateVars;
        // State vars are initialized with mutableStateOf(0.0) = Double
        foreach ($stateVars as $var) {
            $this->varTypes[$var] = 'Double';
        }
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
        $escapedVar = $this->escapeKotlinKeyword($node->variable);
        $value = $node->value->accept($this);

        // Track Double type from state vars and propagated locals
        $isDouble = false;
        if (in_array($node->variable, $this->stateVars)) {
            $isDouble = true;
        } elseif ($node->value instanceof IR\Variable &&
                  ($this->varTypes[$node->value->name] ?? null) === 'Double') {
            $isDouble = true;
        } elseif (str_contains($value, '.toDouble()') || str_contains($value, '.toDoubleOrNull()')) {
            $isDouble = true;
        }

        if ($isDouble) {
            $this->varTypes[$node->variable] = 'Double';
            // Convert bare int literals to Double
            if (preg_match('/^-?\d+$/', $value)) {
                $value .= '.0';
            }
            if (!in_array($node->variable, $this->declaredVars) && !in_array($node->variable, $this->stateVars)) {
                $this->declaredVars[] = $node->variable;
                return "var {$escapedVar} = {$value}";
            }
            return "{$escapedVar} = {$value}";
        }

        if (!in_array($node->variable, $this->declaredVars)) {
            $this->declaredVars[] = $node->variable;
            return "var {$escapedVar} = {$value}";
        }
        return "{$escapedVar} = {$value}";
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
        // Handle strpos comparisons: strpos($x, $y) === false → indexOf == -1
        if ($node->left instanceof IR\FunctionCall && $node->left->name === 'strpos') {
            $left = $node->left->accept($this);
            if ($node->right instanceof IR\Literal && $node->right->value === false) {
                return "{$left} == -1";
            }
            if ($node->op === '!==') {
                return "{$left} != -1";
            }
        }

        $op = match ($node->op) {
            '.' => '+',
            '===' => '==',
            '!==' => '!=',
            '&&' => '&&',
            '||' => '||',
            default => $node->op,
        };

        if ($node->right instanceof IR\Literal && $node->right->value === false && $op === '==') {
            return "!{$node->left->accept($this)}";
        }

        $left = $node->left->accept($this);
        $right = $node->right->accept($this);

        // Convert bare int literals to Double when comparing with known-Double vars
        $leftType = ($node->left instanceof IR\Variable) ? ($this->varTypes[$node->left->name] ?? null) : null;
        $rightType = ($node->right instanceof IR\Variable) ? ($this->varTypes[$node->right->name] ?? null) : null;

        if ($leftType === 'Double' && preg_match('/^-?\d+$/', $right)) {
            $right .= '.0';
        } elseif ($leftType === 'Double' && str_ends_with($right, '.toInt()')) {
            $right = "({$right}).toDouble()";
        }
        if ($rightType === 'Double' && preg_match('/^-?\d+$/', $left)) {
            $left .= '.0';
        } elseif ($rightType === 'Double' && str_ends_with($left, '.toInt()')) {
            $left = "({$left}).toDouble()";
        }

        return "{$left} {$op} {$right}";
    }

    public function generateUnaryOp(IR\UnaryOp $node): string
    {
        return "{$node->op}{$node->operand->accept($this)}";
    }

    private function escapeKotlinKeyword(string $name): string
    {
        return match ($name) {
            'val' => 'value',
            'var' => 'variable',
            'fun' => 'function',
            'is' => 'isFlag',
            'in' => 'inValue',
            'class' => 'clazz',
            'when' => 'whenValue',
            'object' => 'obj',
            default => $name,
        };
    }

    public function generateVariable(IR\Variable $node): string
    {
        return $this->escapeKotlinKeyword($node->name);
    }

    public function generateLiteral(IR\Literal $node): string
    {
        if (is_string($node->value)) {
            $escaped = str_replace(
                ['\\', '"', "\n", "\r", "\t", "\0"],
                ['\\\\', '\\"', '\\n', '\\r', '\\t', '\\0'],
                $node->value
            );
            return '"' . $escaped . '"';
        }
        if (is_bool($node->value)) {
            return $node->value ? 'true' : 'false';
        }
        if (is_null($node->value)) {
            return 'null';
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

        $kotlinFunc = match ($node->name) {
            // String manipulation
            'substr' => $this->generateSubstr($args),
            'trim' => "{$args[0]}.trim()",
            'ltrim' => "{$args[0]}.trimStart()",
            'rtrim' => "{$args[0]}.trimEnd()",
            'strtoupper' => "{$args[0]}.uppercase()",
            'strtolower' => "{$args[0]}.lowercase()",
            'ucfirst' => "{$args[0]}.first().uppercase() + {$args[0]}.drop(1)",
            'lcfirst' => "{$args[0]}.first().lowercase() + {$args[0]}.drop(1)",
            'str_replace' => "{$args[0]}.replace({$args[1]}, {$args[2]})",
            'str_repeat' => "{$args[0]}.repeat(($args[1]) ?: 0)",
            'str_pad' => $this->generateStrPad($args),
            'strip_tags' => "{$args[0]}",
            'htmlspecialchars' => "{$args[0]}",
            'md5' => "{$args[0]}.md5()",
            'sha1' => "{$args[0]}.sha1()",

            // Type conversion
            'doubleval' => "{$args[0]}.toDoubleOrNull() ?: 0.0",
            'int' => "{$args[0]}.toIntOrNull() ?: 0",
            'strval' => "{$args[0]}.toString()",

            // String operations
            'strlen' => "{$args[0]}.length",
            'strpos' => "{$args[0]}.indexOf({$args[1]})",
            'substr_count' => "{$args[0]}.count { it in {$args[1]} }",
            'explode' => "{$args[1]}.split({$args[0]})",
            'implode', 'join' => "{$args[1]}.joinToString(separator: {$args[0]})",
            'str_contains' => "{$args[0]}.contains({$args[1]})",
            'str_starts_with' => "{$args[0]}.startsWith({$args[1]})",
            'str_ends_with' => "{$args[0]}.endsWith({$args[1]})",

            // Array operations
            'in_array' => "{$args[1]}.contains({$args[0]})",
            'empty' => "{$args[0]}.isEmpty()",
            'count' => "{$args[0]}.size",
            'array' => 'mutableListOf(' . implode(', ', $args) . ')',
            'array_push' => "{$args[0]}.add({$args[1]})",
            'array_keys' => "({$args[0]} as? Map<*, *>)?.keys?.toList() ?: listOf()",
            'array_values' => "({$args[0]} as? Map<*, *>)?.values?.toList() ?: {$args[0]}",
            'array_merge' => "({$args[0]} as? List<*>)?.plus({$args[1]} as? List<*>) ?: listOf()",
            'array_slice' => "({$args[0]} as? List<*>)?.slice(Int({$args[1]}) ?: 0 until (Int({$args[1]}) ?: 0 + (Int({$args[2]}) ?: 1))) ?: listOf()",
            'array_reverse' => "({$args[0]} as? List<*>)?.reversed() ?: listOf()",
            'array_sum' => "({$args[0]} as? List<Double>)?.sum() ?: 0.0",
            'array_map' => "({$args[1]} as? List<*>).map { {$args[0]}(it) }",
            'array_filter' => "({$args[1]} as? List<*>).filter { {$args[0]}(it) }",
            'array_search' => "({$args[1]} as? List<*>).indexOf({$args[0]})",
            'array_column' => "({$args[1]} as? List<Map<String, *>>).map { it[{$args[0]}] ?: \"\" }",
            'array_flip' => "({$args[0]} as? Map<*, *>)?.associateBy({{ it.key }}, {{ it.value }}) ?: mapOf()",
            'array_fill' => "List(Int({$args[0]}) ?: 0) { {$args[1]} }",
            'array_rand' => "({$args[0]} as? List<*>).randomOrNull()",
            'array_shift' => "({$args[0]} as? MutableList<*>).removeAt(0)",
            'array_pop' => "({$args[0]} as? MutableList<*>).removeAt({$args[0]}.size - 1)",
            'array_unshift' => "({$args[0]} as? MutableList<*>).add(0, {$args[1]})",
            'array_key_exists' => "({$args[1]} as? Map<*, *>)?.containsKey({$args[0]}) ?: false",

            // Math functions
            'floor' => "Math.floor({$args[0]}).toInt()",
            'ceil' => "Math.ceil({$args[0]}).toInt()",
            'round' => "Math.round({$args[0]}).toInt()",
            'abs' => "kotlin.math.abs({$args[0]})",
            'min' => "kotlin.math.min({$args[0]}, {$args[1]})",
            'max' => "kotlin.math.max({$args[0]}, {$args[1]})",
            'rand' => "Random.nextInt(1, Int({$args[1]}) ?: 100)",
            'sqrt' => "kotlin.math.sqrt({$args[0]})",
            'log' => "kotlin.math.log({$args[0]})",
            'sin' => "kotlin.math.sin({$args[0]})",
            'cos' => "kotlin.math.cos({$args[0]})",
            'tan' => "kotlin.math.tan({$args[0]})",

            // Type checking
            'is_null' => "{$args[0]} == null",
            'is_array' => "{$args[0]} is Array<*>",
            'is_int' => "{$args[0]} is Int",
            'is_float' => "{$args[0]} is Float || {$args[0]} is Double",
            'is_string' => "{$args[0]} is String",
            'is_bool' => "{$args[0]} is Boolean",
            'is_numeric' => "{$args[0]} is Number",

            // Date/Time
            'time' => "System.currentTimeMillis() / 1000",
            'date' => "java.time.Instant.now().toString()",

            // Encoding
            'urlencode' => "URLEncoder.encode({$args[0]}, \"UTF-8\")",
            'urldecode' => "URLDecoder.decode({$args[0]}, \"UTF-8\")",
            'base64_encode' => "android.util.Base64.encode({$args[0]}.toByteArray(), android.util.Base64.NO_WRAP).decodeToString()",
            'base64_decode' => "String(android.util.Base64.decode({$args[0]}, android.util.Base64.NO_WRAP))",

            // Formatting
            'number_format' => "String.format(\"%." . ($args[1] ?? '8') . "f\", {$args[0]})",
            'sprintf' => $this->generateSprintf($args),
            'json_decode' => "org.json.JSONObject({$args[0]})",
            'json_encode' => "{$args[0]}.toString()",

            // Array helpers
            'preg_match' => "Regex({$args[0]}).containsMatchIn({$args[1]})",
            'preg_split' => $this->generatePregSplit($args),
            'end' => "{$args[0]}.last()",

            // String (extended)
            'chr' => "({$args[0]}).toChar()",
            'ord' => "{$args[0]}.first().code",
            'strrev' => "{$args[0]}.reversed()",
            'str_shuffle' => "String({$args[0]}.toCharArray().apply { shuffle() })",
            'str_word_count' => "{$args[0]}.split(Regex(\"\\\\s+\")).size",

            // Array (extended)
            'array_chunk' => "{$args[0]}.chunked(Int({$args[1]}) ?: 1)",
            'array_splice' => "{$args[0]}.slice(0 until Int({$args[1]}) ?: 0) + {$args[0]}.drop(Int({$args[1]}) ?: 0 + (Int({$args[2]}) ?: 0))",
            'array_pad' => "{$args[0]}.padTo(Int({$args[1]}) ?: 0, {$args[2]})",
            'current' => "{$args[0]}.firstOrNull()",
            'compact' => "mapOf({$args[0]} to {$args[0]}, {$args[1]} to {$args[1]})",

            // Array (P5)
            'array_count_values' => "{$args[0]}.groupBy { it }.mapValues { it.value.size }",
            'array_walk' => "{$args[0]}.forEach { {$args[1]}(it) }",

            // Misc (P5)
            'uniqid' => "UUID.randomUUID().toString()",
            'nl2br' => "{$args[0]}.replace(\"\\n\", \"<br>\")",

            'array_pop' => "{$args[0]}.removeLast()",
            'array_unshift' => "{$args[0]}.add(0, {$args[1]})",
            'array_key_exists' => "($args[1] is Map) && ($args[1].keys.contains($args[0]))",
            'array_reduce' => "{$args[0]}.reduce({$args[1]}) { acc, it -> acc + it }",
            'array_unique' => "{$args[0]}.distinct()",
            'array_diff' => "{$args[0]}.filter { it !in {$args[1]} }",
            'array_combine' => "{$args[0]}.zip({$args[1]}).toMap()",
            'array_intersect' => "{$args[0]}.filter { it in {$args[1]} }",
            'array_product' => "{$args[0]}.fold(1) { acc, it -> acc * it }",

            // String (P6)
            'addslashes' => "{$args[0]}.replace(\"\\\\\", \"\\\\\\\\\").replace(\"'\", \"\\\\'\").replace(\"\\\"\", \"\\\\\\\"\")",
            'stripslashes' => "{$args[0]}.replace(\"\\\\'\", \"'\").replace(\"\\\\\\\"\", \"\\\"\").replace(\"\\\\\\\\\", \"\\\\\")",
            'str_split' => "{$args[0]}.map { it.toString() }",
            'str_ireplace' => "{$args[0]}.replace(Regex({$args[1]}, RegexOption.IGNORE_CASE), {$args[2]})",
            'substr_replace' => "{$args[0]}.take({$args[1]}) + {$args[2]}",

            // Array (P6)
            'array_change_key_case' => "{$args[0]}.entries.associate { it.key.lowercase() to it.value }",
            'array_replace' => "{$args[0]} + {$args[1]}",
            'array_intersect_key' => "{$args[0]}.filterKeys { it in {$args[1]}.keys }",
            'array_diff_key' => "{$args[0]}.filterKeys { it !in {$args[1]}.keys }",
            'array_udiff' => "{$args[0]}.filter { a -> !{$args[1]}.any { b -> {$args[2]}(a, b) == 0 } }",

            // Math (P6)
            'pow' => "Math.pow({$args[0]}, {$args[1]})",
            'exp' => "Math.exp({$args[0]})",
            'pi' => "Math.PI",
            'microtime' => "System.currentTimeMillis() / 1000.0",

            // Type conversion (P6)
            'intval' => "((({$args[0]}) as? Number)?.toInt() ?: 0)",
            'floatval' => "((({$args[0]}) as? Number)?.toDouble() ?: 0.0)",

            // Number system (P7)
            'decbin' => "({$args[0]} as Int).toString(2)",
            'dechex' => "({$args[0]} as Int).toString(16)",
            'decoct' => "({$args[0]} as Int).toString(8)",
            'bindec' => "(({$args[0]} as String).toIntOrNull(2) ?: 0)",
            'hexdec' => "(({$args[0]} as String).toIntOrNull(16) ?: 0)",
            'octdec' => "(({$args[0]} as String).toIntOrNull(8) ?: 0)",

            // Math (P7)
            'intdiv' => "({$args[0]} as Int) / ({$args[1]} as Int)",
            'fmod' => "{$args[0]} % {$args[1]}",
            'hypot' => "kotlin.math.hypot({$args[0]}, {$args[1]})",
            'deg2rad' => "Math.toRadians({$args[0]})",
            'rad2deg' => "Math.toDegrees({$args[0]})",

            // Type checking (P7)
            'is_finite' => "({$args[0]} as? Double)?.isFinite() ?: false",
            'is_infinite' => "({$args[0]} as? Double)?.isInfinite() ?: false",
            'is_nan' => "({$args[0]} as? Double)?.isNaN() ?: false",
            'is_scalar' => "{$args[0]} is String || {$args[0]} is Int || {$args[0]} is Double || {$args[0]} is Boolean",

            // Array (P7)
            'array_key_first' => "({$args[0]} as? Map<*, *>)?.keys.firstOrNull()",

            default => "{$node->name}(" . implode(', ', $args) . ")",
        };

        return $kotlinFunc;
    }

    private function generateSubstr(array $args): string
    {
        if (count($args) === 2) {
            if ($args[1] === '-1' || $args[1] === '(-1)') {
                return "{$args[0]}.last().toString()";
            }
            if (str_starts_with($args[1], '-')) {
                $offset = ltrim($args[1], '-');
                return "{$args[0]}.dropLast({$offset})";
            }
            return "{$args[0]}.drop({$args[1]})";
        }
        if (count($args) === 3) {
            if ($args[2] === '-1' || $args[2] === '(-1)') {
                return "{$args[0]}.dropLast(1)";
            }
            return "{$args[0]}.take({$args[2]})";
        }
        return "substr(" . implode(', ', $args) . ")";
    }

    private function generatePregSplit(array $args): string
    {
        $pattern = $args[0] instanceof IR\Literal ? $args[0]->value : '';
        $chars = $this->extractCharsFromRegex($pattern);
        $escaped = addcslashes($chars, '+=-x÷%\\');
        return "{$args[1]}.split(\"[{$escaped}]\".toRegex()).filter { it.isNotEmpty() }";
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

    private function generateStrPad(array $args): string
    {
        $length = $args[1] ?? '0';
        $padStr = $args[2] ?? '" "';
        $padType = $args[3] ?? 'STR_PAD_RIGHT';
        if ($padType === 'STR_PAD_LEFT') {
            return "{$args[0]}.padStart(Int({$length}) ?: 0, {$padStr})";
        }
        if ($padType === 'STR_PAD_BOTH') {
            return "{$args[0]}.padStart(Int({$length}) ?: 0, {$padStr})";
        }
        return "{$args[0]}.padEnd(Int({$length}) ?: 0, {$padStr})";
    }

    private function generateSprintf(array $args): string
    {
        return "String.format({$args[0]}, " . implode(', ', array_slice($args, 1)) . ")";
    }

    public function generateReturn(IR\ReturnStatement $node): string
    {
        return $node->value ? "return@run {$node->value->accept($this)}" : 'return@run';
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
        return "if ({$condition}) {$then} else {$else}";
    }

    public function generateArrayLiteral(IR\ArrayLiteral $node): string
    {
        $items = array_map(fn($item) => $item->accept($this), $node->items);
        return 'listOf(' . implode(', ', $items) . ')';
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
        $init = !empty($node->init) ? implode('; ', array_map(fn($n) => $n->accept($this), $node->init)) : '';
        $cond = !empty($node->cond) ? implode('; ', array_map(fn($n) => $n->accept($this), $node->cond)) : '';
        $loop = !empty($node->loop) ? implode('; ', array_map(fn($n) => $n->accept($this), $node->loop)) : '';

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
            $result = "for (({$key}, {$value}) in {$iterable}) {\n";
        } else {
            $result = "for ({$value} in {$iterable}) {\n";
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
    // Switch / Match (Kotlin uses `when`)
    // ============================================================

    public function generateSwitch(IR\SwitchStatement $node): string
    {
        $condition = $node->condition->accept($this);
        $result = "when ({$condition}) {\n";
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
        $label = $node->condition ? $node->condition->accept($this) : 'else';
        $body = $node->body->accept($this);
        return "{$label} -> {\n{$this->indent()}    {$body}\n{$this->indent()}}";
    }

    public function generateMatch(IR\MatchExpression $node): string
    {
        $condition = $node->condition->accept($this);
        $result = "when ({$condition}) {\n";
        $this->indent++;
        foreach ($node->arms as $arm) {
            $cond = $arm['condition']->accept($this);
            $body = $arm['body']->accept($this);
            $result .= $this->indent() . "{$cond} -> {$body}\n";
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
        return 'println(' . implode(' + " " + ', $values) . ')';
    }

    public function generatePrint(IR\PrintStatement $node): string
    {
        return "println({$node->expr->accept($this)})";
    }

    // ============================================================
    // Type Casting
    // ============================================================

    public function generateCast(IR\Cast $node): string
    {
        $expr = $node->expr->accept($this);
        return match ($node->type) {
            'int', 'integer' => "{$expr}.toInt()",
            'float', 'double' => "{$expr}.toDouble()",
            'string' => "{$expr}.toString()",
            'bool', 'boolean' => "{$expr} as Boolean",
            'array' => "{$expr} as Array<*>",
            'object' => "{$expr} as Any",
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
        return "Math.pow({$node->left->accept($this)}, {$node->right->accept($this)}).toInt()";
    }

    public function generateBitwiseAnd(IR\BitwiseAnd $node): string
    {
        return "{$node->left->accept($this)} and {$node->right->accept($this)}";
    }

    public function generateBitwiseOr(IR\BitwiseOr $node): string
    {
        return "{$node->left->accept($this)} or {$node->right->accept($this)}";
    }

    public function generateBitwiseXor(IR\BitwiseXor $node): string
    {
        return "{$node->left->accept($this)} xor {$node->right->accept($this)}";
    }

    public function generateShiftLeft(IR\ShiftLeft $node): string
    {
        return "{$node->left->accept($this)} shl {$node->right->accept($this)}";
    }

    public function generateShiftRight(IR\ShiftRight $node): string
    {
        return "{$node->left->accept($this)} shr {$node->right->accept($this)}";
    }

    public function generateSpaceship(IR\SpaceshipOp $node): string
    {
        $left = $node->left->accept($this);
        $right = $node->right->accept($this);
        return "({$left} < {$right} ? -1 : ({$left} > {$right} ? 1 : 0))";
    }

    public function generateCoalesce(IR\CoalesceOp $node): string
    {
        return "{$node->left->accept($this)} ?: {$node->right->accept($this)}";
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
        return "inv({$node->operand->accept($this)})";
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
    // Class / Object Support
    // ============================================================

    public function generatePropertyDeclaration(IR\PropertyDeclaration $node): string
    {
        $visibility = match ($node->visibility) {
            'private' => 'private ',
            'protected' => 'protected ',
            default => '',
        };
        $type = $node->type !== null ? ": {$node->type}" : '';
        $default = $node->default !== null ? " = {$node->default->accept($this)}" : '';
        return "{$visibility}var {$node->name}{$type}{$default}";
    }

    public function generateMethodParameter(IR\MethodParameter $node): string
    {
        $type = $node->type !== null ? ": {$node->type}" : '';
        $default = $node->default !== null ? " = {$node->default->accept($this)}" : '';
        return "{$node->name}{$type}{$default}";
    }

    public function generateMethodDeclaration(IR\MethodDeclaration $node): string
    {
        $visibility = match ($node->visibility) {
            'private' => 'private ',
            'protected' => 'protected ',
            default => '',
        };
        $static = $node->isStatic ? 'companion object {' : '';
        $params = implode(', ', array_map(fn($p) => $p->accept($this), $node->params));
        $returnType = $node->returnType !== null ? ": {$node->returnType}" : '';
        
        if ($node->body === null) {
            return "{$visibility}fun {$node->name}({$params}){$returnType}";
        }
        
        $body = $node->body->accept($this);
        return "{$visibility}fun {$node->name}({$params}){$returnType} {\n{$this->indent()}    {$body}\n{$this->indent()}}";
    }

    public function generateClassDeclaration(IR\ClassDeclaration $node): string
    {
        $extends = $node->extends !== null ? " : {$node->extends}" : '';
        $implements = !empty($node->implements) ? " : " . implode(', ', $node->implements) : '';
        $inheritance = $extends . $implements;
        
        $lines = ["class {$node->name}{$inheritance} {"];
        $this->indent++;
        
        foreach ($node->properties as $prop) {
            $lines[] = $this->indent() . $prop->accept($this);
        }
        
        foreach ($node->methods as $method) {
            $lines[] = $this->indent() . $method->accept($this);
        }
        
        $this->indent--;
        $lines[] = $this->indent() . "}";
        
        return implode("\n", $lines);
    }

    public function generateNewExpr(IR\NewExpr $node): string
    {
        $args = implode(', ', array_map(fn($arg) => $arg->accept($this), $node->args));
        return "{$node->className}({$args})";
    }

    public function generateFunctionLiteral(IR\FunctionLiteral $node): string
    {
        $paramStr = implode(', ', array_map(fn($p) => $p->accept($this), $node->params));
        $params = $paramStr !== '' ? "($paramStr)" : '';
        
        if ($node->isArrow && $node->body !== null) {
            // Kotlin lambda: { (params) -> body }
            $body = $node->body->accept($this);
            return "{ {$params} -> {$body} }";
        }
        
        // Function literal
        $body = $node->body !== null ? $node->body->accept($this) : '';
        $indent = $this->indent();
        return "{ {$params} ->\n{$indent}    {$body}\n{$indent}}}";
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
        
        foreach ($node->catches as $catch) {
            $result .= " " . $catch->accept($this);
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
        $indent = $this->indent();
        $type = $node->type !== null ? ": {$node->type}" : '';
        return " catch ({$node->variable}{$type}) {\n{$indent}    {$body}\n{$indent}}}";
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

    public function generateArrayPop(IR\ArrayPop $node): string
    {
        return "{$node->array->accept($this)}.removeLast()";
    }

    public function generateArrayUnshift(IR\ArrayUnshift $node): string
    {
        return "{$node->array->accept($this)}.add(0, {$node->value->accept($this)})";
    }

    public function generateArrayKeyExists(IR\ArrayKeyExists $node): string
    {
        return "({$node->array->accept($this)} is Map) && ({$node->array->accept($this)}.keys.contains({$node->key->accept($this)}))";
    }

    public function generateArrayReduce(IR\ArrayReduce $node): string
    {
        return "{$node->array->accept($this)}.reduce({$node->initial->accept($this)}) { acc, it -> acc + it }";
    }

    public function generateArrayUnique(IR\ArrayUnique $node): string
    {
        return "{$node->array->accept($this)}.distinct()";
    }

    public function generateArrayDiff(IR\ArrayDiff $node): string
    {
        return "{$node->array->accept($this)}.filter { it !in {$node->diff->accept($this)} }";
    }
}
