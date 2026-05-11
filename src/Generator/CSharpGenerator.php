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
            // String manipulation
            'substr' => $this->generateSubstr($args),
            'trim' => "{$args[0]}.Trim()",
            'ltrim' => "{$args[0]}.TrimStart()",
            'rtrim' => "{$args[0]}.TrimEnd()",
            'strtoupper' => "{$args[0]}.ToUpper()",
            'strtolower' => "{$args[0]}.ToLower()",
            'ucfirst' => "{$args[0]}.Substring(0, 1).ToUpper() + {$args[0]}.Substring(1)",
            'lcfirst' => "{$args[0]}.Substring(0, 1).ToLower() + {$args[0]}.Substring(1)",
            'str_replace' => "{$args[0]}.Replace({$args[1]}, {$args[2]})",
            'str_repeat' => "string.Concat(Enumerable.Repeat({$args[0]}, (int)({$args[1]}) ?? 0))",
            'str_pad' => $this->generateStrPad($args),
            'strip_tags' => "{$args[0]}",
            'htmlspecialchars' => "System.Net.WebUtility.HtmlEncode({$args[0]})",
            'md5' => "System.Security.Cryptography.MD5.HashData(System.Text.Encoding.UTF8.GetBytes({$args[0]})).ToHexString()",
            'sha1' => "System.Security.Cryptography.SHA1.HashData(System.Text.Encoding.UTF8.GetBytes({$args[0]})).ToHexString()",

            // Type conversion
            'doubleval' => "Convert.ToDouble({$args[0]})",
            'int' => "Convert.ToInt32({$args[0]})",
            'strval' => "{$args[0]}.ToString()",

            // String operations
            'strlen' => "{$args[0]}.Length",
            'strpos' => "{$args[0]}.IndexOf({$args[1]})",
            'substr_count' => "{$args[0]}.Split({$args[1]}).Length - 1",
            'explode' => "{$args[1]}.Split({$args[0]})",
            'implode', 'join' => "string.Join({$args[0]}, {$args[1]})",
            'str_contains' => "{$args[0]}.Contains({$args[1]})",
            'str_starts_with' => "{$args[0]}.StartsWith({$args[1]})",
            'str_ends_with' => "{$args[0]}.EndsWith({$args[1]})",

            // Array operations
            'in_array' => "{$args[1]}.Contains({$args[0]})",
            'empty' => "string.IsNullOrEmpty({$args[0]})",
            'count' => "{$args[0]}.Length",
            'array' => 'new[] { ' . implode(', ', $args) . ' }',
            'array_push' => "{$args[0]}.Add({$args[1]})",
            'array_keys' => "({$args[0]} is IDictionary dict) ? new List(dict.Keys) : new List()",
            'array_values' => "({$args[0]} is IDictionary dict) ? new List(dict.Values) : new List({$args[0]})",
            'array_merge' => "Enumerable.Concat({$args[0]}, {$args[1]}).ToArray()",
            'array_slice' => "Enumerable.Skip({$args[0]}, (int)({$args[1]}) ?? 0).Take((int)({$args[2]}) ?? 1).ToArray()",
            'array_reverse' => "Enumerable.Reverse({$args[0]})",
            'array_sum' => "Enumerable.Sum({$args[0]})",
            'array_map' => "Enumerable.Select({$args[1]}, {$args[0]})",
            'array_filter' => "Enumerable.Where({$args[1]}, {$args[0]})",
            'array_search' => "Enumerable.IndexOf({$args[1]}, {$args[0]})",
            'array_column' => "Enumerable.Select({$args[1]}, x => x[{$args[0]}] ?? \"\")",
            'array_flip' => "Enumerable.ToDictionary({$args[0]}, x => x.Value, x => x.Key)",
            'array_fill' => "Enumerable.Repeat({$args[1]}, (int)({$args[0]}) ?? 0).ToArray()",
            'array_rand' => "{$args[0]}[new Random().Next(0, {$args[0]}.Length)]",
            'array_shift' => "Enumerable.Skip({$args[0]}, 1).ToArray()",
            'array_unshift' => "Enumerable.Concat(new[] { {$args[1]} }, {$args[0]}).ToArray()",
            'array_key_exists' => "({$args[1]} is IDictionary dict) ? dict.Contains({$args[0]}) : false",
            'array_pop' => "{$args[0]}[^1]",
            'array_reduce' => "{$args[0]}.Aggregate({$args[1]}, (acc, it) => acc + it)",
            'array_unique' => "{$args[0]}.Distinct().ToArray()",
            'array_diff' => "{$args[0]}.Except({$args[1]}).ToArray()",
            'array_combine' => "{$args[0]}.Zip({$args[1]}, (k, v) => new { k, v }).ToDictionary(x => x.k, x => x.v)",
            'array_intersect' => "{$args[0]}.Intersect({$args[1]}).ToArray()",
            'array_product' => "{$args[0]}.Aggregate(1, (acc, it) => acc * it)",

            // Math functions
            'floor' => "Math.Floor({$args[0]})",
            'ceil' => "Math.Ceiling({$args[0]})",
            'round' => "Math.Round({$args[0]})",
            'abs' => "Math.Abs({$args[0]})",
            'min' => "Math.Min({$args[0]}, {$args[1]})",
            'max' => "Math.Max({$args[0]}, {$args[1]})",
            'rand' => "new Random().Next(1, (int)({$args[1]}) ?? 100)",
            'sqrt' => "Math.Sqrt({$args[0]})",
            'log' => "Math.Log({$args[0]})",
            'sin' => "Math.Sin({$args[0]})",
            'cos' => "Math.Cos({$args[0]})",
            'tan' => "Math.Tan({$args[0]})",

            // Type checking
            'is_null' => "{$args[0]} == null",
            'is_array' => "{$args[0]} is Array",
            'is_int' => "{$args[0]} is int",
            'is_float' => "{$args[0]} is float || {$args[0]} is double",
            'is_string' => "{$args[0]} is string",
            'is_bool' => "{$args[0]} is bool",
            'is_numeric' => "{$args[0]} is IConvertible && {$args[0]} is not string",

            // Date/Time
            'time' => "(int)DateTime.UtcNow.Subtract(new DateTime(1970, 1, 1)).TotalSeconds",
            'date' => "DateTime.Now.ToString()",

            // Encoding
            'urlencode' => "System.Net.WebUtility.UrlEncode({$args[0]})",
            'urldecode' => "System.Net.WebUtility.UrlDecode({$args[0]})",
            'base64_encode' => "Convert.ToBase64String(System.Text.Encoding.UTF8.GetBytes({$args[0]}))",
            'base64_decode' => "System.Text.Encoding.UTF8.GetString(Convert.FromBase64String({$args[0]}))",

            // Formatting
            'number_format' => "{$args[0]}.ToString(\"F{$args[1]}\")",
            'sprintf' => $this->generateSprintf($args),
            'json_decode' => "System.Text.Json.JsonSerializer.Deserialize({$args[0]})",
            'json_encode' => "System.Text.Json.JsonSerializer.Serialize({$args[0]})",

            // Array helpers
            'preg_match' => "Regex.IsMatch({$args[1]}, {$args[0]})",
            'preg_split' => $this->generatePregSplit($args),
            'end' => "{$args[0]}.Last()",

            // String (extended)
            'chr' => "((char)(int){$args[0]}).ToString()",
            'ord' => "(int){$args[0]}[0]",
            'strrev' => "new string({$args[0]}.Reverse().ToArray())",
            'str_shuffle' => "new string({$args[0]}.OrderBy(_ => Guid.NewGuid()).ToArray())",
            'str_word_count' => "{$args[0]}.Split(new[] { ' ' }, StringSplitOptions.RemoveEmptyEntries).Length",

            // Array (extended)
            'array_chunk' => "{$args[0]}.Select((v, i) => new { v, i }).GroupBy(x => x.i / {$args[1]}).Select(g => g.Select(x => x.v).ToList()).ToList()",
            'array_splice' => "{$args[0]}.Take({$args[1]}).Concat({$args[0]}.Skip({$args[1]} + ({$args[2]} ?? 0))).ToList()",
            'array_pad' => "{$args[0]}.Concat(Enumerable.Repeat({$args[2]}, Math.Max(0, (int){$args[1]} - {$args[0]}.Count))).ToList()",
            'current' => "{$args[0]}.FirstOrDefault()",
            'compact' => "new Dictionary<string, object> { [\"{$args[0]}\"] = {$args[0]}, [\"{$args[1]}\"] = {$args[1]} }",

            // Array (P5)
            'array_count_values' => "{$args[0]}.GroupBy(e => e).ToDictionary(g => g.Key, g => g.Count())",
            'array_walk' => "{$args[0]}.ToList().ForEach(e => {$args[1]}(e))",

            // Misc (P5)
            'uniqid' => "Guid.NewGuid().ToString()",
            'nl2br' => "{$args[0]}.Replace(\"\\n\", \"<br>\")",

            // String (P6)
            'addslashes' => "{$args[0]}.Replace(\"\\\\\", \"\\\\\\\\\").Replace(\"'\", \"\\\\'\").Replace(\"\\\"\", \"\\\\\\\"\")",
            'stripslashes' => "{$args[0]}.Replace(\"\\\\'\", \"'\").Replace(\"\\\\\\\"\", \"\\\"\").Replace(\"\\\\\\\\\", \"\\\\\")",
            'str_split' => "{$args[0]}.Select(c => c.ToString()).ToArray()",
            'str_ireplace' => "Regex.Replace({$args[0]}, {$args[1]}, {$args[2]}, RegexOptions.IgnoreCase)",
            'substr_replace' => "{$args[0]}.Substring(0, {$args[1]}) + {$args[2]}",

            // Array (P6)
            'array_change_key_case' => "{$args[0]}.ToDictionary(kvp => kvp.Key.ToString().ToLower(), kvp => kvp.Value)",
            'array_replace' => "{$args[0]}.Concat({$args[1]}).GroupBy(kvp => kvp.Key).ToDictionary(g => g.Key, g => g.Last().Value)",
            'array_intersect_key' => "{$args[0]}.Where(kvp => {$args[1]}.ContainsKey(kvp.Key)).ToDictionary(kvp => kvp.Key, kvp => kvp.Value)",
            'array_diff_key' => "{$args[0]}.Where(kvp => !{$args[1]}.ContainsKey(kvp.Key)).ToDictionary(kvp => kvp.Key, kvp => kvp.Value)",
            'array_udiff' => "{$args[0]}.Where(a => !{$args[1]}.Any(b => {$args[2]}(a, b) == 0)).ToArray()",

            // Math (P6)
            'pow' => "Math.Pow({$args[0]}, {$args[1]})",
            'exp' => "Math.Exp({$args[0]})",
            'pi' => "Math.PI",
            'microtime' => "(DateTime.UtcNow - new DateTime(1970, 1, 1)).TotalSeconds",

            // Type conversion (P6)
            'intval' => "Convert.ToInt32({$args[0]})",
            'floatval' => "Convert.ToDouble({$args[0]})",

            // Number system (P7)
            'decbin' => "Convert.ToString({$args[0]}, 2)",
            'dechex' => "Convert.ToString({$args[0]}, 16)",
            'decoct' => "Convert.ToString({$args[0]}, 8)",
            'bindec' => "Convert.ToInt32({$args[0]}, 2)",
            'hexdec' => "Convert.ToInt32({$args[0]}, 16)",
            'octdec' => "Convert.ToInt32({$args[0]}, 8)",

            // Math (P7)
            'intdiv' => "Convert.ToInt32({$args[0]}) / Convert.ToInt32({$args[1]})",
            'fmod' => "{$args[0]} % {$args[1]}",
            'hypot' => "Math.Sqrt({$args[0]} * {$args[0]} + {$args[1]} * {$args[1]})",
            'deg2rad' => "{$args[0]} * (Math.PI / 180.0)",
            'rad2deg' => "{$args[0]} * (180.0 / Math.PI)",

            // Type checking (P7)
            'is_finite' => "double.IsFinite(Convert.ToDouble({$args[0]}))",
            'is_infinite' => "double.IsInfinity(Convert.ToDouble({$args[0]}))",
            'is_nan' => "double.IsNaN(Convert.ToDouble({$args[0]}))",
            'is_scalar' => "{$args[0]} is string || {$args[0]} is int || {$args[0]} is double || {$args[0]} is bool",

            // Array (P7)
            'array_key_first' => "{$args[0]}.Keys.Cast<object>().FirstOrDefault()",

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

    private function generateStrPad(array $args): string
    {
        $length = $args[1] ?? '0';
        $padStr = $args[2] ?? '" "';
        $padType = $args[3] ?? 'STR_PAD_RIGHT';
        if ($padType === 'STR_PAD_LEFT') {
            return "{$args[0]}.PadLeft((int)({$length}) ?? 0, {$padStr}[0])";
        }
        if ($padType === 'STR_PAD_BOTH') {
            return "{$args[0]}.PadLeft((int)({$length}) ?? 0, {$padStr}[0])";
        }
        return "{$args[0]}.PadRight((int)({$length}) ?? 0, {$padStr}[0])";
    }

    private function generateSprintf(array $args): string
    {
        return "string.Format({$args[0]}, " . implode(', ', array_slice($args, 1)) . ")";
    }

    public function generateReturn(IR\ReturnStatement $node): string
    {
        return $node->value ? "return {$node->value->accept($this)};" : 'return;';
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
        return 'break;';
    }

    public function generateContinue(IR\ContinueStatement $node): string
    {
        return 'continue;';
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
            'array' => "({$expr}).ToArray()",
            'object' => "({$expr}) as object",
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
    // Class / Object Support
    // ============================================================

    public function generatePropertyDeclaration(IR\PropertyDeclaration $node): string
    {
        $visibility = match ($node->visibility) {
            'private' => 'private ',
            'protected' => 'protected ',
            default => 'public ',
        };
        $type = $node->type ?? 'var';
        $default = $node->default !== null ? ` = {$node->default->accept($this)}` : '';
        return "{$visibility}{$type} {$node->name}{$default}";
    }

    public function generateMethodParameter(IR\MethodParameter $node): string
    {
        $type = $node->type ?? 'var';
        $default = $node->default !== null ? ' = ' . $node->default->accept($this) : '';
        return "{$type} {$node->name}{$default}";
    }

    public function generateMethodDeclaration(IR\MethodDeclaration $node): string
    {
        $visibility = match ($node->visibility) {
            'private' => 'private ',
            'protected' => 'protected ',
            default => 'public ',
        };
        $static = $node->isStatic ? 'static ' : '';
        $params = implode(', ', array_map(fn($p) => $p->accept($this), $node->params));
        $returnType = $node->returnType ?? 'void';
        
        if ($node->body === null) {
            return "{$visibility}{$static}{$returnType} {$node->name}({$params});";
        }
        
        $body = $node->body->accept($this);
        return "{$visibility}{$static}{$returnType} {$node->name}({$params}) {\n{$this->indent()}    {$body}\n{$this->indent()}}";
    }

    public function generateClassDeclaration(IR\ClassDeclaration $node): string
    {
        $extends = $node->extends !== null ? " : {$node->extends}" : '';
        $implements = !empty($node->implements) ? " : " . implode(', ', $node->implements) : '';
        $inheritance = $extends . $implements;
        
        $lines = ["public class {$node->name}{$inheritance} {"];
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
        return "new {$node->className}({$args})";
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
        return " catch ({$node->type} {$node->variable}) {\n{$indent}    {$body}\n{$indent}}}";
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

    public function generateFunctionLiteral(IR\FunctionLiteral $node): string
    {
        $params = implode(', ', array_map(fn($p) => $p->name, $node->params));
        
        if ($node->isArrow && $node->body !== null) {
            // C# lambda: (params) => body
            $body = $node->body->accept($this);
            return "($params) => {$body}";
        }
        
        // Anonymous method
        $body = $node->body !== null ? $node->body->accept($this) : '';
        $indent = $this->indent();
        return "delegate($params) {\n{$indent}    {$body}\n{$indent}}}";
    }

    private function indent(): string
    {
        return str_repeat('    ', $this->indent);
    }

    public function generateArrayPop(IR\ArrayPop $node): string
    {
        return "{$node->array->accept($this)}[^1]";
    }

    public function generateArrayUnshift(IR\ArrayUnshift $node): string
    {
        return "{$node->array->accept($this)}.Insert(0, {$node->value->accept($this)})";
    }

    public function generateArrayKeyExists(IR\ArrayKeyExists $node): string
    {
        return "({$node->array->accept($this)} is IDictionary dict) ? dict.Contains({$node->key->accept($this)}) : false";
    }

    public function generateArrayReduce(IR\ArrayReduce $node): string
    {
        return "{$node->array->accept($this)}.Aggregate({$node->initial->accept($this)}, (acc, it) => acc + it)";
    }

    public function generateArrayUnique(IR\ArrayUnique $node): string
    {
        return "{$node->array->accept($this)}.Distinct().ToArray()";
    }

    public function generateArrayDiff(IR\ArrayDiff $node): string
    {
        return "{$node->array->accept($this)}.Except({$node->diff->accept($this)}).ToArray()";
    }
}
