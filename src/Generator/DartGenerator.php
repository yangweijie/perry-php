<?php

declare(strict_types=1);

namespace Perry\Generator;

use Perry\IR;

class DartGenerator extends AbstractGenerator
{
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
            return "{$node->variable}.value = {$node->value->accept($this)}";
        }
        if (!in_array($node->variable, $this->declaredVars)) {
            $this->declaredVars[] = $node->variable;
            return "var {$node->variable} = {$node->value->accept($this)}";
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
            '===' => '==',
            '!==' => '!=',
            '.' => '+',
            '&&' => '&&',
            '||' => '||',
            default => $node->op,
        };

        if ($node->right instanceof IR\Literal && $node->right->value === false && $op === '==') {
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
        return $node->name;
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

        $dartFunc = match ($node->name) {
            // String manipulation
            'substr' => $this->generateSubstr($args),
            'trim' => "{$args[0]}.trim()",
            'ltrim' => "{$args[0]}.replaceFirst(RegExp('^\\\\s+'), '')",
            'rtrim' => "{$args[0]}.replaceFirst(RegExp('\\\\s+$'), '')",
            'strtoupper' => "{$args[0]}.toUpperCase()",
            'strtolower' => "{$args[0]}.toLowerCase()",
            'ucfirst' => "{$args[0]}.substring(0, 1).toUpperCase() + {$args[0]}.substring(1)",
            'lcfirst' => "{$args[0]}.substring(0, 1).toLowerCase() + {$args[0]}.substring(1)",
            'str_replace' => "{$args[0]}.replaceAll({$args[1]}, {$args[2]})",
            'str_repeat' => "{$args[0]}. * (int.tryParse({$args[1]}) ?? 0)",
            'str_pad' => $this->generateStrPad($args),
            'strip_tags' => "{$args[0]}",
            'htmlspecialchars' => "{$args[0]}",
            'md5' => "md5({$args[0]})",
            'sha1' => "sha1({$args[0]})",

            // Type conversion
            'doubleval' => "double.parse({$args[0]}.toString())",
            'int' => "int.parse({$args[0]}.toString())",
            'strval' => "{$args[0]}.toString()",

            // String operations
            'strlen' => "{$args[0]}.length",
            'strpos' => "{$args[0]}.indexOf({$args[1]})",
            'substr_count' => "{$args[0]}.split({$args[1]}).length - 1",
            'explode' => "{$args[1]}.split({$args[0]})",
            'implode', 'join' => "{$args[1]}.join({$args[0]})",
            'str_contains' => "{$args[0]}.contains({$args[1]})",
            'str_starts_with' => "{$args[0]}.startsWith({$args[1]})",
            'str_ends_with' => "{$args[0]}.endsWith({$args[1]})",

            // Array operations
            'in_array' => "{$args[1]}.contains({$args[0]})",
            'empty' => "{$args[0]}.isEmpty",
            'count' => "{$args[0]}.length",
            'array' => '[' . implode(', ', $args) . ']',
            'array_push' => "{$args[0]}.add({$args[1]})",
            'array_keys' => "({$args[0]} is Map) ? {$args[0]}.keys.toList() : []",
            'array_values' => "({$args[0]} is Map) ? {$args[0]}.values.toList() : {$args[0]}",
            'array_merge' => "List.from({$args[0]}).followedBy(List.from({$args[1]})).toList()",
            'array_slice' => "({$args[0]} as List).sublist(int.tryParse({$args[1]}) ?? 0, (int.tryParse({$args[1]}) ?? 0) + (int.tryParse({$args[2]}) ?? 1))",
            'array_reverse' => "({$args[0]} as List).reversed().toList()",
            'array_sum' => "({$args[0]} as List<num>).reduce((a, b) => a + b)",
            'array_map' => "({$args[1]} as List).map((x) => {$args[0]}(x)).toList()",
            'array_filter' => "({$args[1]} as List).where((x) => {$args[0]}(x)).toList()",
            'array_search' => "({$args[1]} as List).indexOf({$args[0]})",
            'array_column' => "({$args[1]} as List<Map>).map((x) => x[{$args[0]}] ?? '')",
            'array_flip' => "({$args[0]} as Map).entries.associate((e) => MapEntry(e.value, e.key))",
            'array_fill' => "List.filled(int.tryParse({$args[0]}) ?? 0, {$args[1]})",
            'array_rand' => "({$args[0]} as List).isEmpty ? null : {$args[0]}[({$args[0]} as List).length ~/ 2]",
            'array_shift' => "({$args[0]} as List).removeAt(0)",
            'array_pop' => "({$args[0]} as List).removeLast()",
            'array_unshift' => "({$args[0]} as List).insert(0, {$args[1]})",
            'array_key_exists' => "({$args[1]} is Map) ? {$args[1]}.containsKey({$args[0]}) : false",

            // Math functions
            'floor' => "{$args[0]}.floor()",
            'ceil' => "{$args[0]}.ceil()",
            'round' => "{$args[0]}.round()",
            'abs' => "{$args[0]}.abs()",
            'min' => "({$args[0]} < {$args[1]}) ? {$args[0]} : {$args[1]}",
            'max' => "({$args[0]} > {$args[1]}) ? {$args[0]} : {$args[1]}",
            'rand' => "Random().nextInt(int.tryParse({$args[1]}) ?? 100)",
            'sqrt' => "{$args[0]}.sqrt()",
            'log' => "{$args[0]}.ln()",
            'sin' => "{$args[0]}.sin()",
            'cos' => "{$args[0]}.cos()",
            'tan' => "{$args[0]}.tan()",

            // Type checking
            'is_null' => "{$args[0]} == null",
            'is_array' => "{$args[0]} is List",
            'is_int' => "{$args[0]} is int",
            'is_float' => "{$args[0]} is double",
            'is_string' => "{$args[0]} is String",
            'is_bool' => "{$args[0]} is bool",
            'is_numeric' => "{$args[0]} is num",

            // Date/Time
            'time' => "DateTime.now().millisecondsSinceEpoch ~/ 1000",
            'date' => "DateTime.now().toString()",

            // Encoding
            'urlencode' => "Uri.encodeComponent({$args[0]})",
            'urldecode' => "Uri.decodeComponent({$args[0]})",
            'base64_encode' => "base64Encode(utf8.encode({$args[0]}))",
            'base64_decode' => "utf8.decode(base64Decode({$args[0]}))",

            // Formatting
            'number_format' => $this->generateNumberFormat($args),
            'sprintf' => $this->generateSprintf($args),
            'json_decode' => "jsonDecode({$args[0]})",
            'json_encode' => "jsonEncode({$args[0]})",

            // Array helpers
            'preg_match' => "RegExp({$args[0]}).hasMatch({$args[1]})",
            'preg_split' => $this->generatePregSplit($args),
            'end' => "{$args[0]}.last",

            // String (extended)
            'chr' => "String.fromCharCode({$args[0]})",
            'ord' => "{$args[0]}.codeUnitAt(0)",
            'strrev' => "{$args[0]}.split('').reversed.join('')",
            'str_shuffle' => "({$args[0]}.split('')..shuffle()).join('')",
            'str_word_count' => "{$args[0]}.split(RegExp('\\\\s+')).where((s) => s.isNotEmpty).length",

            // Array (extended)
            'array_chunk' => "[for (var i = 0; i < {$args[0]}.length; i += {$args[1]}) {$args[0]}.sublist(i, (i + {$args[1]} > {$args[0]}.length) ? {$args[0]}.length : i + {$args[1]})]",
            'array_splice' => "[...{$args[0]}.sublist(0, {$args[1]}), ...{$args[0]}.sublist({$args[1]} + ({$args[2]} ?? 0))]",
            'array_pad' => "{$args[0]}.length >= {$args[1]} ? {$args[0]} : ([...{$args[0]}, ...List.filled({$args[1]} - {$args[0]}.length, {$args[2]})])",
            'current' => "{$args[0]}.first",
            'compact' => "{{$args[0]}: {$args[0]}, {$args[1]}: {$args[1]}}",

            // Array (P5)
            'array_count_values' => "Map.from({$args[0]}.fold(<dynamic, int>{}, (Map<dynamic, int> acc, e) => acc..update(e, (v) => v + 1, ifAbsent: () => 1)))",
            'array_walk' => "{$args[0]}.forEach((e) => {$args[1]}(e))",

            // Misc (P5)
            'uniqid' => "DateTime.now().millisecondsSinceEpoch.toString()",
            'nl2br' => "{$args[0]}.replaceAll('\\n', '<br>')",

            'array_pop' => "{$args[0]}.removeLast()",
            'array_unshift' => "{$args[0]}.insert(0, {$args[1]})",
            'array_key_exists' => "({$args[1]} is Map) && ({$args[1]}.containsKey({$args[0]}))",
            'array_reduce' => "{$args[0]}.reduce((acc, it) => acc + it)",
            'array_unique' => "{$args[0]}.toSet().toList()",
            'array_diff' => "{$args[0]}.where((it) => !{$args[1]}.contains(it)).toList()",
            'array_combine' => "Map.fromIterables({$args[0]}, {$args[1]})",
            'array_intersect' => "{$args[0]}.where((it) => {$args[1]}.contains(it)).toList()",
            'array_product' => "{$args[0]}.fold(1, (acc, it) => acc * it)",

            // String (P6)
            'addslashes' => "{$args[0]}.replaceAll(\"\\\\\", \"\\\\\\\\\").replaceAll(\"'\", \"\\\\'\").replaceAll(\"\\\"\", \"\\\\\\\"\")",
            'stripslashes' => "{$args[0]}.replaceAll(\"\\\\'\", \"'\").replaceAll(\"\\\\\\\"\", \"\\\"\").replaceAll(\"\\\\\\\\\", \"\\\\\")",
            'str_split' => "{$args[0]}.split('')",
            'str_ireplace' => "{$args[0]}.replaceAll(RegExp({$args[1]}, caseSensitive: false), {$args[2]})",
            'substr_replace' => "{$args[0]}.substring(0, {$args[1]}) + {$args[2]}",

            // Array (P6)
            'array_change_key_case' => "Map.fromIterable({$args[0]}.entries, key: (e) => e.key.toString().toLowerCase(), value: (e) => e.value)",
            'array_replace' => "{...{$args[0]}, ...{$args[1]}}",
            'array_intersect_key' => "Map.fromIterable({$args[0]}.entries.where((e) => {$args[1]}.containsKey(e.key)), key: (e) => e.key, value: (e) => e.value)",
            'array_diff_key' => "Map.fromIterable({$args[0]}.entries.where((e) => !{$args[1]}.containsKey(e.key)), key: (e) => e.key, value: (e) => e.value)",
            'array_udiff' => "{$args[0]}.where((a) => !{$args[1]}.any((b) => {$args[2]}(a, b) == 0)).toList()",

            // Math (P6)
            'pow' => "Math.pow({$args[0]}, {$args[1]})",
            'exp' => "Math.exp({$args[0]})",
            'pi' => "pi",
            'microtime' => "DateTime.now().millisecondsSinceEpoch / 1000.0",

            // Type conversion (P6)
            'intval' => "(int.tryParse({$args[0]}.toString()) ?? 0)",
            'floatval' => "(double.tryParse({$args[0]}.toString()) ?? 0.0)",

            // Number system (P7)
            'decbin' => "({$args[0]} as int).toRadixString(2)",
            'dechex' => "({$args[0]} as int).toRadixString(16)",
            'decoct' => "({$args[0]} as int).toRadixString(8)",
            'bindec' => "(int.tryParse({$args[0]}.toString(), radix: 2) ?? 0)",
            'hexdec' => "(int.tryParse({$args[0]}.toString(), radix: 16) ?? 0)",
            'octdec' => "(int.tryParse({$args[0]}.toString(), radix: 8) ?? 0)",

            // Math (P7)
            'intdiv' => "{$args[0]} ~/ {$args[1]}",
            'fmod' => "{$args[0]} % {$args[1]}",
            'hypot' => "sqrt({$args[0]} * {$args[0]} + {$args[1]} * {$args[1]})",
            'deg2rad' => "{$args[0]} * (pi / 180.0)",
            'rad2deg' => "{$args[0]} * (180.0 / pi)",

            // Type checking (P7)
            'is_finite' => "{$args[0]} is double && {$args[0]}.isFinite",
            'is_infinite' => "{$args[0]} is double && {$args[0]}.isInfinite",
            'is_nan' => "{$args[0]} is double && {$args[0]}.isNaN",
            'is_scalar' => "{$args[0]} is String || {$args[0]} is int || {$args[0]} is double || {$args[0]} is bool",

            // Array (P7)
            'array_key_first' => "({$args[0]} as Map).keys.isNotEmpty ? ({$args[0]} as Map).keys.first : null",

            default => "{$node->name}(" . implode(', ', $args) . ")",
        };

        return $dartFunc;
    }

    private function generateSubstr(array $args): string
    {
        if (count($args) === 2) {
            if ($args[1] === '-1' || $args[1] === '(-1)') {
                return "{$args[0]}.split('').last";
            }
            if (str_starts_with($args[1], '-')) {
                $offset = ltrim($args[1], '-');
                return "{$args[0]}.substring(0, {$args[0]}.length - {$offset})";
            }
            return "{$args[0]}.substring({$args[1]})";
        }
        if (count($args) === 3) {
            if ($args[2] === '-1' || $args[2] === '(-1)') {
                return "{$args[0]}.substring(0, {$args[0]}.length - 1)";
            }
            return "{$args[0]}.substring({$args[1]}, {$args[1]} + {$args[2]})";
        }
        return "substr(" . implode(', ', $args) . ")";
    }

    private function generateNumberFormat(array $args): string
    {
        $decimals = $args[1] ?? '8';
        return "{$args[0]}.toStringAsFixed({$decimals})";
    }

    private function generatePregSplit(array $args): string
    {
        $pattern = $args[0] instanceof IR\Literal ? $args[0]->value : '';
        $chars = $this->extractCharsFromRegex($pattern);
        $escaped = addcslashes($chars, '/');
        return "{$args[1]}.split(RegExp('[{$escaped}]')).where((s) => s.isNotEmpty).toList()";
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
            return "{$args[0]}.padLeft(int.tryParse({$length}) ?? 0, {$padStr})";
        }
        if ($padType === 'STR_PAD_BOTH') {
            return "{$args[0]}.padLeft(int.tryParse({$length}) ?? 0, {$padStr})";
        }
        return "{$args[0]}.padRight(int.tryParse({$length}) ?? 0, {$padStr})";
    }

    private function generateSprintf(array $args): string
    {
        return "sprintf({$args[0]}, [" . implode(', ', array_slice($args, 1)) . "])";
    }

    public function generateReturn(IR\ReturnStatement $node): string
    {
        return $node->value ? "return {$node->value->accept($this)}" : 'return';
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
            $result = "for (var {$key} in {$iterable}.keys) {\n";
            $this->indent++;
            $result .= $this->indent() . "var {$value} = {$iterable}[{$key}];\n";
            $result .= $this->indent() . $node->body->accept($this) . "\n";
            $this->indent--;
        } else {
            $result = "for (var {$value} in {$iterable}) {\n";
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
        // Dart doesn't have match expression, use switch
        $condition = $node->condition->accept($this);
        $result = "(() {\n";
        $this->indent++;
        $result .= $this->indent() . "switch ({$condition}) {\n";
        $this->indent++;
        foreach ($node->arms as $arm) {
            $cond = $arm['condition']->accept($this);
            $body = $arm['body']->accept($this);
            $result .= $this->indent() . "case {$cond}: return {$body};\n";
        }
        $this->indent--;
        $result .= $this->indent() . "}\n";
        $this->indent--;
        $result .= $this->indent() . "})()";
        return $result;
    }

    // ============================================================
    // Output
    // ============================================================

    public function generateEcho(IR\EchoStatement $node): string
    {
        $values = array_map(fn($v) => $v->accept($this), $node->values);
        return 'print(' . implode(' + " " + ', $values) . ')';
    }

    public function generatePrint(IR\PrintStatement $node): string
    {
        return "print({$node->expr->accept($this)})";
    }

    // ============================================================
    // Type Casting
    // ============================================================

    public function generateCast(IR\Cast $node): string
    {
        $expr = $node->expr->accept($this);
        return match ($node->type) {
            'int', 'integer' => "int.parse({$expr}.toString())",
            'float', 'double' => "double.parse({$expr}.toString())",
            'string' => "{$expr}.toString()",
            'bool', 'boolean' => "{$expr} as bool",
            'array' => "List.from({$expr})",
            'object' => "{$expr} as dynamic",
            default => $expr,
        };
    }

    // ============================================================
    // Increment / Decrement
    // ============================================================

    public function generateIncrement(IR\Increment $node): string
    {
        $var = $this->generateVariable(new IR\Variable($node->variable));
        return $node->prefix ? "++{$var}" : "{$var}++";
    }

    public function generateDecrement(IR\Decrement $node): string
    {
        $var = $this->generateVariable(new IR\Variable($node->variable));
        return $node->prefix ? "--{$var}" : "{$var}--";
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
        return "pow({$node->left->accept($this)}, {$node->right->accept($this)}).toInt()";
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
    // Class / Object Support
    // ============================================================

    public function generatePropertyDeclaration(IR\PropertyDeclaration $node): string
    {
        $visibility = match ($node->visibility) {
            'private' => '_ ',
            'protected' => '',
            default => '',
        };
        $type = $node->type !== null ? "{$node->type} " : '';
        $default = $node->default !== null ? " = {$node->default->accept($this)}" : '';
        return "{$visibility}{$node->name}{$default}";
    }

    public function generateMethodParameter(IR\MethodParameter $node): string
    {
        $type = $node->type !== null ? "{$node->type} " : '';
        $default = $node->default !== null ? "= {$node->default->accept($this)}" : '';
        return "{$type}{$node->name}{$default}";
    }

    public function generateMethodDeclaration(IR\MethodDeclaration $node): string
    {
        $static = $node->isStatic ? 'static ' : '';
        $params = implode(', ', array_map(fn($p) => $p->accept($this), $node->params));
        $returnType = $node->returnType !== null ? "{$node->returnType} " : '';
        
        if ($node->body === null) {
            return "{$static}{$returnType}{$node->name}({$params});";
        }
        
        $body = $node->body->accept($this);
        return "{$static}{$returnType}{$node->name}({$params}) {\n{$this->indent()}    {$body}\n{$this->indent()}}";
    }

    public function generateClassDeclaration(IR\ClassDeclaration $node): string
    {
        $extends = $node->extends !== null ? " extends {$node->extends}" : '';
        $implements = !empty($node->implements) ? " implements " . implode(', ', $node->implements) : '';
        $inheritance = $extends . $implements;
        
        $lines = ["class {$node->name}{$inheritance} {"];
        $this->indent++;
        
        foreach ($node->properties as $prop) {
            $lines[] = $this->indent() . $prop->accept($this) . ';';
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
        $params = implode(', ', array_map(fn($p) => $p->name, $node->params));
        
        if ($node->isArrow && $node->body !== null) {
            // Dart arrow function: (params) => body
            $body = $node->body->accept($this);
            return "($params) => {$body}";
        }
        
        // Function literal
        $body = $node->body !== null ? $node->body->accept($this) : '';
        $indent = $this->indent();
        return "($params) {\n{$indent}    {$body}\n{$indent}}}";
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
        return " catch ({$node->variable}) {\n{$indent}    {$body}\n{$indent}}}";
    }

    public function generateArrayPop(IR\ArrayPop $node): string
    {
        return "{$node->array->accept($this)}.removeLast()";
    }

    public function generateArrayUnshift(IR\ArrayUnshift $node): string
    {
        return "{$node->array->accept($this)}.insert(0, {$node->value->accept($this)})";
    }

    public function generateArrayKeyExists(IR\ArrayKeyExists $node): string
    {
        return "({$node->array->accept($this)} is Map) && ({$node->array->accept($this)}.containsKey({$node->key->accept($this)}))";
    }

    public function generateArrayReduce(IR\ArrayReduce $node): string
    {
        return "{$node->array->accept($this)}.reduce((acc, it) => acc + it)";
    }

    public function generateArrayUnique(IR\ArrayUnique $node): string
    {
        return "{$node->array->accept($this)}.toSet().toList()";
    }

    public function generateArrayDiff(IR\ArrayDiff $node): string
    {
        return "{$node->array->accept($this)}.where((it) => !{$node->diff->accept($this)}.contains(it)).toList()";
    }
}
