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
            // String manipulation
            'substr' => $this->generateSubstr($args),
            'trim' => "{$args[0]}.trimmingCharacters(in: CharacterSet.whitespacesAndNewlines)",
            'ltrim' => "{$args[0]}.trimmingCharacters(in: CharacterSet(charactersIn: \"" . ($args[1] ?? ' ') . "\"))",
            'rtrim' => "{$args[0]}.trimmingCharacters(in: CharacterSet(charactersIn: \"" . ($args[1] ?? ' ') . "\"))",
            'strtoupper' => "{$args[0]}.uppercased()",
            'strtolower' => "{$args[0]}.lowercased()",
            'ucfirst' => "{$args[0]}.prefix(1).uppercased() + {$args[0]}.dropFirst()",
            'lcfirst' => "{$args[0]}.prefix(1).lowercased() + {$args[0]}.dropFirst()",
            'str_replace' => "{$args[0]}.replacingOccurrences(of: {$args[1]}, with: {$args[2]})",
            'str_repeat' => "String(repeating: {$args[0]}, count: Int({$args[1]}) ?? 0)",
            'str_pad' => $this->generateStrPad($args),
            'strip_tags' => "{$args[0]}",
            'htmlspecialchars' => "{$args[0]}",
            'md5' => "{$args[0]}.md5()",
            'sha1' => "{$args[0]}.sha1()",

            // Type conversion
            'floatval', 'doubleval' => "Double({$args[0]}) ?? 0",
            'intval', 'int' => "Int({$args[0]})",
            'strval' => "String({$args[0]})",

            // String operations
            'strlen' => "{$args[0]}.count",
            'strpos' => "{$args[0]}.contains({$args[1]})",
            'substr_count' => "{$args[0]}.components(separatedBy: {$args[1]}).count - 1",
            'explode' => "{$args[1]}.components(separatedBy: {$args[0]})",
            'implode', 'join' => "{$args[1]}.joined(separator: {$args[0]})",
            'str_contains' => "{$args[0]}.contains({$args[1]})",
            'str_starts_with' => "{$args[0]}.hasPrefix({$args[1]})",
            'str_ends_with' => "{$args[0]}.hasSuffix({$args[1]})",

            // Array operations
            'in_array' => "{$args[1]}.contains({$args[0]})",
            'empty' => "{$args[0]}.isEmpty",
            'count' => "({$args[0]} as! [Any]).count",
            'array_push' => "{$args[0]}.append({$args[1]})",
            'array_keys' => "({$args[0]} as! [AnyHashable: Any]).keys.map { String(describing: $0) }",
            'array_values' => "({$args[0]} as! [Any]).map { $0 }",
            'array_merge' => "({$args[0]} as! [Any]) + ({$args[1]} as! [Any])",
            'array_slice' => "Array({$args[0]} as! [Any])[Int({$args[1]}) ?? 0 ..< (Int({$args[1]}) ?? 0 + (Int({$args[2]}) ?? 1))]",
            'array_reverse' => "({$args[0]} as! [Any]).reversed()",
            'array_sum' => "({$args[0]} as! [Double]).reduce(0, +)",
            'array_map' => "({$args[1]} as! [Any]).map { {$args[0]}($0) }",
            'array_filter' => "({$args[1]} as! [Any]).filter { {$args[0]}($0) }",
            'array_search' => "({$args[1]} as! [Any]).firstIndex { $0 == {$args[0]} }",
            'array_column' => "({$args[1]} as! [[String: Any]]).map { $0[{$args[0]}] ?? \"\" }",
            'array_flip' => "Dictionary({$args[0]} as! [String: Any].enumerated().map { ($1, $0.offset) }, uniquingKeysWith: { $1 })",
            'array_fill' => "Array(repeating: {$args[1]}, count: Int({$args[0]}) ?? 0)",
            'array_rand' => "({$args[0]} as! [Any]).randomElement()",
            'array_shift' => "({$args[0]} as! [Any]).removeFirst()",
            'array_pop' => "({$args[0]} as! [Any]).removeLast()",
            'array_unshift' => "({$args[0]} as! [Any]).insert({$args[1]}, at: 0)",
            'array_key_exists' => "({$args[1]} as! [AnyHashable: Any]).keys.contains({$args[0]})",
            'array_reduce' => "({$args[0]} as! [Any]).reduce({$args[1]}) { \($0) + \($1) }",
            'array_unique' => "Array(Set({$args[0]} as! [Any]))",
            'array_diff' => "({$args[0]} as! [Any]).filter { !({$args[1]} as! [Any]).contains($0) }",
            'array_combine' => "Dictionary(uniqueKeysWithValues: zip({$args[0]} as! [Any], {$args[1]} as! [Any]))",
            'array_intersect' => "({$args[0]} as! [Any]).filter { ({$args[1]} as! [Any]).contains($0) }",
            'array_product' => "({$args[0]} as! [Double]).reduce(1.0, *)",

            // Math functions
            'floor' => "floor({$args[0]})",
            'ceil' => "ceil({$args[0]})",
            'round' => "round({$args[0]})",
            'abs' => "abs({$args[0]})",
            'min' => "min({$args[0]}, {$args[1]})",
            'max' => "max({$args[0]}, {$args[1]})",
            'rand' => "Int.random(in: 1...Int({$args[1]}) ?? 100)",
            'sqrt' => "sqrt({$args[0]})",
            'log' => "log({$args[0]})",
            'sin' => "sin({$args[0]})",
            'cos' => "cos({$args[0]})",
            'tan' => "tan({$args[0]})",

            // Type checking
            'is_null' => "{$args[0]} == nil",
            'is_array' => "{$args[0]} is NSArray",
            'is_int' => "{$args[0]} is Int",
            'is_float' => "{$args[0]} is Double",
            'is_string' => "{$args[0]} is String",
            'is_bool' => "{$args[0]} is Bool",
            'is_numeric' => "{$args[0]} is Int || {$args[0]} is Double",

            // Date/Time
            'time' => "Int(Date().timeIntervalSince1970)",
            'date' => "Date().formatted()",

            // Encoding
            'urlencode' => "{$args[0]}.addingPercentEncoding(withAllowedCharacters: .urlQueryAllowed) ?? \"\"",
            'urldecode' => "{$args[0]}.removingPercentEncoding ?? {$args[0]}",
            'base64_encode' => "Data({$args[0]}.utf8).base64EncodedString()",
            'base64_decode' => "String(data: Data(base64Encoded: {$args[0]}) ?? Data(), encoding: .utf8) ?? \"\"",

            // Formatting
            'number_format' => "String(format: \"%." . ($args[1] ?? '8') . "f\", {$args[0]})",
            'sprintf' => $this->generateSprintf($args),
            'json_decode' => $this->generateJsonDecode($args),
            'json_encode' => $this->generateJsonEncode($args),
            'json_last_error' => '0',
            'json_last_error_msg' => '"OK"',

            // Array helpers
            'preg_match' => "{$args[1]}.range(of: {$args[0]}, options: .regularExpression) != nil",
            'preg_split' => $this->generatePregSplit($args),
            'end' => "{$args[0]}.last!",

            // String (extended)
            'chr' => "String(UnicodeScalar(Int({$args[0]}) ?? 0)!)",
            'ord' => "Int({$args[0]}.unicodeScalars.first?.value ?? 0)",
            'strrev' => "String({$args[0]}.reversed())",
            'str_shuffle' => "String({$args[0]}.shuffled())",
            'str_word_count' => "{$args[0]}.components(separatedBy: CharacterSet.whitespacesAndNewlines).filter { !\$0.isEmpty }.count",

            // Array (extended)
            'array_chunk' => "stride(from: 0, to: ({$args[0]} as! [Any]).count, by: Int({$args[1]}) ?? 1).map { Array(({$args[0]} as! [Any])[\$0..<min(\$0 + (Int({$args[1]}) ?? 1), ({$args[0]} as! [Any]).count)]) }",
            'array_splice' => "Array(({$args[0]} as! [Any])[..<Int({$args[1]}) ?? 0] + ({$args[0]} as! [Any])[Int({$args[1]}) ?? 0 + (Int({$args[2]}) ?? 0)...])",
            'array_pad' => "({$args[0]} as! [Any]).padding(toLength: Int({$args[1]}) ?? 0, with: {$args[2]})",
            'current' => "({$args[0]} as! [Any]).first",
            'compact' => "[{$args[0]}, {$args[1]}]",

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

    private function generateStrPad(array $args): string
    {
        $length = $args[1] ?? '0';
        $padStr = $args[2] ?? '" "';
        $padType = $args[3] ?? 'STR_PAD_RIGHT';
        if ($padType === 'STR_PAD_LEFT') {
            return "{$args[0]}.padding(toLength: Int({$length}) ?? 0, withPad: {$padStr}, startingAt: 0)";
        }
        if ($padType === 'STR_PAD_BOTH') {
            return "{$args[0]}.padding(toLength: Int({$length}) ?? 0, withPad: {$padStr}, startingAt: 0)";
        }
        return "{$args[0]}.padding(toLength: Int({$length}) ?? 0, withPad: {$padStr}, startingAt: 0)";
    }

    private function generateSprintf(array $args): string
    {
        // Simple sprintf: format string + args
        $format = $args[0] instanceof IR\Literal ? $args[0]->value : '%@';
        $swiftFormat = str_replace(['%s', '%d', '%f'], ['%@', '%d', '%.2f'], $format);
        return "String(format: \"{$swiftFormat}\", " . implode(', ', array_slice($args, 1)) . ")";
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
        $static = $node->isStatic ? 'static ' : '';
        $params = implode(', ', array_map(fn($p) => $p->accept($this), $node->params));
        $returnType = $node->returnType !== null ? " -> {$node->returnType}" : '';
        
        if ($node->body === null) {
            return "{$visibility}{$static}func {$node->name}({$params}){$returnType}";
        }
        
        $body = $node->body->accept($this);
        return "{$visibility}{$static}func {$node->name}({$params}){$returnType} {\n{$this->indent()}    {$body}\n{$this->indent()}}";
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
            // Swift arrow function: { (params) in body }
            $body = $node->body->accept($this);
            return "{ {$params} in {$body} }";
        }
        
        // Block closure
        $body = $node->body !== null ? $node->body->accept($this) : '';
        $indent = $this->indent();
        return "{ {$params} in\n{$indent}    {$body}\n{$indent}}}";
    }

    public function generateArrayPop(IR\ArrayPop $node): string
    {
        return "({$node->array->accept($this)} as! [Any]).removeLast()";
    }

    public function generateArrayUnshift(IR\ArrayUnshift $node): string
    {
        return "({$node->array->accept($this)} as! [Any]).insert({$node->value->accept($this)}, at: 0)";
    }

    public function generateArrayKeyExists(IR\ArrayKeyExists $node): string
    {
        return "({$node->array->accept($this)} as! [AnyHashable: Any]).keys.contains({$node->key->accept($this)})";
    }

    public function generateArrayReduce(IR\ArrayReduce $node): string
    {
        return "({$node->array->accept($this)} as! [Any]).reduce({$node->initial->accept($this)}) { $0 + $1 }";
    }

    public function generateArrayUnique(IR\ArrayUnique $node): string
    {
        return "Array(Set({$node->array->accept($this)} as! [Any]))";
    }

    public function generateArrayDiff(IR\ArrayDiff $node): string
    {
        return "({$node->array->accept($this)} as! [Any]).filter { !$0.isContainedIn({$node->diff->accept($this)} as! [Any]) }";
    }

    private function indent(): string
    {
        return str_repeat('    ', $this->indent);
    }
}
