# Actions

Actions define what happens when a user interacts with a widget. Perry supports multiple action types, including a powerful Closure transpilation system that converts PHP closures into native code.

---

## Simple Actions

Pre-built action types for common operations:

```php
use Perry\UI\Action;
use Perry\UI\Binding;

$display = new Binding('display', '0');

// SetValue — assign a value
$action = Action::set($display, '42');
$action = Action::set($display, true);     // bool
$action = Action::set($display, 3.14);    // float

// Append — concatenate a string
$action = Action::append($display, '1');   // display += "1"

// Clear — reset to initial value
$action = Action::clear($display);         // display = "0"

// Custom — raw platform-specific code (passed through as-is)
$action = Action::custom('display.text = ""');
```

## Widget Actions

Interactive widgets support `Action` for event handling:

```php
use Perry\UI\Action;
use Perry\UI\Binding;
use Perry\UI\Widget\Button;
use Perry\UI\Widget\Slider;
use Perry\UI\Widget\TextInput;
use Perry\UI\Widget\Toggle;

$display = new Binding('display', '0');

// Button — action on click
$btn = new Button('7', Action::append($display, '7'));

// Slider — action on value change
$slider = new Slider(0, 100, step: 1,
    onChange: Action::set($display, '50'));

// TextInput — action on text change
$input = new TextInput($display,
    onChange: Action::set($display, ''));

// Toggle — action on toggle
$toggle = new Toggle(true,
    onToggle: Action::set($display, 'toggled'));
```

### Supported Events

| Widget | Event Property | Description |
|-------|----------------|-------------|
| `Button` | `action` (constructor) | Fires on click/tap |
| `Slider` | `onChange` | Fires when value changes |
| `TextInput` | `onChange` | Fires when text changes |
| `Toggle` | `onToggle` | Fires when checked state changes |
| `Checkbox` | `onChange` | Fires when checked state changes |
| `Dropdown` | `onChange` | Fires when selection changes |

### Action Types by Widget

| ActionType | Button | Slider | TextInput | Toggle |
|------------|--------|--------|-----------|--------|
| `SetValue` | ✅ | ✅ | ✅ | ✅ |
| `Append` | ✅ | — | — | — |
| `Clear` | ✅ | — | — | — |
| `Custom` | ✅ | ✅ | ✅ | ✅ |
| `Closure` | ✅ | ✅ | ✅ | ✅ |

---

## Closure Actions (AST Transpilation)

The most powerful action type. Write PHP closures that get parsed into an AST and cross-compiled to any target language.

```php
use Perry\UI\Action;
use Perry\UI\Binding;

$display = new Binding('display', '0');
$operand1 = new Binding('operand1', 0.0);
$operation = new Binding('operation', '');

$action = Action::fromClosure(
    function () use ($display, $operand1, $operation) {
        $operand1 = floatval($display);
        $operation = '+';
        $display .= '+';
    }
);
```

### How It Works

```
PHP closure
    ↓ nikic/php-parser
PHP AST
    ↓ Perry\IR\AstToIrVisitor
Perry IR (54 node types)
    ↓ Perry\Generator\{Swift,JavaScript,Kotlin,Dart,CSharp}Generator
Target language code
```

### Closure Bindings (Parameter Substitution)

```php
// Pass external values into the closure at definition time
$action = Action::fromClosure(
    function () use ($display, $digit) {
        $display .= $digit;
    },
    ['digit' => '5']  // $digit is replaced with "5" in generated code
);

// Generated Swift: display = display + "5"
// Generated JS:    state.display = state.display + "5"
```

### Nested Closure Pattern

Use this for parameterized button factories:

```php
function numBtn(string $digit, Binding $display): Button {
    return new Button($digit, Action::fromClosure(
        function () use ($digit, $display) {
            $display .= $digit;
        },
        compact('digit')
    ));
}

$row = new HStack(
    numBtn('1', $display),
    numBtn('2', $display),
    numBtn('3', $display),
);
```

---

## Supported PHP Features

| Feature | Swift | JavaScript | Kotlin | Dart | C# |
|---------|-------|------------|--------|------|-----|
| Variables | `var x` | `let x` | `var x` | `var x` | `var x` |
| State vars | `x = ...` | `state.x = ...` | `x.value = ...` | `x.value = ...` | `x = ...` |
| If/else | `if {} else {}` | `if {} else {}` | `if {} else {}` | `if {} else {}` | `if {} else {}` |
| While | `while {}` | `while {}` | `while {}` | `while {}` | `while {}` |
| For | `for {}` | `for {}` | `for {}` | `for {}` | `for {}` |
| Foreach | `for x in y` | `for x of y` | `for x in y` | `for x in y` | `foreach x in y` |
| Ternary | `c ? a : b` | `c ? a : b` | `if (c) a else b` | `c ? a : b` | `c ? a : b` |
| Switch | `switch {}` | `switch {}` | `when {}` | `switch {}` | `switch {}` |
| Match | `match {}` | `switch+return` | `when->` | `switch+IIFE` | `switch expr` |
| Try/catch | `do{}catch{}` | `try{}catch{}` | `try{}catch{}` | `try{}catch{}` | `try{}catch{}` |
| Throw | `throw` | `throw` | `throw` | `throw` | `throw` |
| Type cast | `Int()`, `Double()` | `parseInt()`, `parseFloat()` | `.toInt()`, `.toDouble()` | `int.parse()`, `double.parse()` | `(int)`, `(double)` |
| Increment | `+= 1` | `x++` | `x++` | `x++` | `x++` |
| Compound assign | `+=`, `-=`, `*=`, `/=` | ✅ | ✅ | ✅ | ✅ |
| Nullsafe | `?.method()` | `?.method()` | `?.method()` | `?.method()` | `?.method()` |
| Static call | `Class.method()` | `Class.method()` | `Class.method()` | `Class.method()` | `Class.method()` |

---

## PHP Function Mappings

97+ PHP built-in functions are mapped across all 5 target languages:

| PHP | Swift | JavaScript | Kotlin | Dart | C# |
|-----|-------|------------|--------|------|-----|
| `substr($s, -1)` | `String(s.last!)` | `s.slice(-1)` | `s.last().toString()` | `s[s.length-1]` | `s[s.Length-1]` |
| `substr($s, 0, -1)` | `String(s.dropLast(1))` | `s.slice(0,-1)` | `s.dropLast(1)` | `s.substring(0,s.length-1)` | `s.Substring(0,s.Length-1)` |
| `substr($s, 1)` | `String(s.dropFirst(1))` | `s.slice(1)` | `s.dropFirst(1)` | `s.substring(1)` | `s.Substring(1)` |
| `strlen($s)` | `s.count` | `s.length` | `s.length` | `s.length` | `s.Length` |
| `floatval($x)` | `Double(x) ?? 0` | `parseFloat(x)` | `x.toDoubleOrNull() ?: 0.0` | `double.parse(x.toString())` | `Convert.ToDouble(x)` |
| `intval($x)` | `Int(x)` | `parseInt(x)` | `x.toIntOrNull() ?: 0` | `int.parse(x.toString())` | `Convert.ToInt32(x)` |
| `strval($x)` | `String(x)` | `String(x)` | `x.toString()` | `x.toString()` | `x.ToString()` |
| `in_array($x, $a)` | `a.contains(x)` | `a.includes(x)` | `a.contains(x)` | `a.contains(x)` | `a.Contains(x)` |
| `strpos($s, $n)` | `s.firstIndex(of: n)` | IIFE with `indexOf` | `s.indexOf(n)` | `s.indexOf(n)` | `s.IndexOf(n)` |
| `end($a)` | `a.last!` | `a[a.length-1]` | `a.last()` | `a.last` | `a[a.Length-1]` |
| `number_format($n,$d)` | `String(format: "%.$df", $n)` | `n.toFixed(d)` | `String.format("%.$df",n)` | `n.toStringAsFixed(d)` | `$n.ToString("F$d")` |
| `floor($x)` | `floor(x)` | `Math.floor(x)` | `Math.floor(x).toInt()` | `x.floor()` | `Math.Floor(x)` |
| `empty($x)` | `x.isEmpty` | `!x` | `x.isEmpty()` | `x.isEmpty` | `string.IsNullOrEmpty(x)` |
| `count($a)` | `a.count` | `a.length` | `a.size` | `a.length` | `a.Length` |
| `json_decode($s)` | `JSONDecoder()` | `JSON.parse(s)` | `Gson().fromJson(...)` | `jsonDecode(s)` | `JsonConvert.DeserializeObject(...)` |
| `json_encode($v)` | `JSONEncoder()` | `JSON.stringify(v)` | `Gson().toJson(...)` | `jsonEncode(v)` | `JsonConvert.SerializeObject(...)` |
| `preg_match($p, $s)` | `range(of: p, in: s)` | `s.match(p)` | `p.toRegex().find(s)` | `s.contains(RegExp(p))` | `Regex.IsMatch(s, p)` |
| `array_push($a, $v)` | `a.append(v)` | `a.push(v)` | `a.add(v)` | `a.add(v)` | `a.Add(v)` |
| `array_reduce($a, $f)` | `a.reduce(0, f)` | `a.reduce(f)` | `a.reduce(f)` | `a.reduce(f)` | `a.Aggregate(f)` |
| `array_unique($a)` | `Array(Set(a))` | `[...new Set(a)]` | `a.distinct()` | `a.toSet().toList()` | `a.Distinct().ToArray()` |
| `array_merge($a, $b)` | `a + b` | `[...a, ...b]` | `a + b` | `[...a, ...b]` | `a.Concat(b)` |

**`=== false` optimization:** All generators detect `expr === false` and emit `!expr` instead.
