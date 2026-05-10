<?php

declare(strict_types=1);

namespace Perry\IR;

use Closure;

/**
 * Type inference engine for Perry IR.
 * Analyzes IR nodes to infer their types.
 */
class TypeInferer
{
    /** @var array<string, Type> Variable type environment */
    private array $env = [];

    /**
     * Infer the type of an IR node.
     */
    public function infer(Node $node): Type
    {
        return match (true) {
            $node instanceof Literal => $this->inferLiteral($node),
            $node instanceof Variable => $this->inferVariable($node),
            $node instanceof BinaryOp => $this->inferBinaryOp($node),
            $node instanceof UnaryOp => $this->inferUnaryOp($node),
            $node instanceof Assignment => $this->inferAssignment($node),
            $node instanceof ArrayAccess => $this->inferArrayAccess($node),
            $node instanceof ArrayLiteral => $this->inferArrayLiteral($node),
            $node instanceof FunctionCall => $this->inferFunctionCall($node),
            $node instanceof MethodCall => $this->inferMethodCall($node),
            $node instanceof PropertyAccess => Type::any(),
            $node instanceof Ternary => $this->inferTernary($node),
            $node instanceof Cast => $this->inferCast($node),
            $node instanceof FunctionLiteral => $this->inferFunctionLiteral($node),
            $node instanceof NewExpr => $this->inferNewExpr($node),
            default => Type::unknown(),
        };
    }

    /**
     * Set the type of a variable in the environment.
     */
    public function setVariableType(string $name, Type $type): void
    {
        $this->env[$name] = $type;
    }

    /**
     * Clear the variable environment.
     */
    public function clearEnv(): void
    {
        $this->env = [];
    }

    private function inferLiteral(Literal $node): Type
    {
        return match (gettype($node->value)) {
            'integer' => Type::int(),
            'double' => Type::float(),
            'string' => Type::string(),
            'boolean' => Type::bool(),
            'NULL' => Type::null(),
            'array' => Type::array(),
            default => Type::unknown(),
        };
    }

    private function inferVariable(Variable $node): Type
    {
        return $this->env[$node->name] ?? Type::unknown();
    }

    private function inferBinaryOp(BinaryOp $node): Type
    {
        $left = $this->infer($node->left);
        $right = $this->infer($node->right);

        return match ($node->op) {
            '+' => $this->inferAdd($left, $right),
            '-', '*', '/', '%' => $this->inferNumeric($left, $right),
            '==' => Type::bool(),
            '!=' => Type::bool(),
            '<' => Type::bool(),
            '>' => Type::bool(),
            '<=' => Type::bool(),
            '>=' => Type::bool(),
            '&&' => Type::bool(),
            '||' => Type::bool(),
            '.' => Type::string(),  // String concatenation
            '===' => Type::bool(),
            '!==' => Type::bool(),
            '<<' => $left,
            '>>' => $left,
            '&' => $left,
            '|' => $left,
            '^' => $left,
            default => Type::unknown(),
        };
    }

    private function inferAdd(Type $left, Type $right): Type
    {
        // String concatenation
        if ($left->name === Type::TYPE_STRING || $right->name === Type::TYPE_STRING) {
            return Type::string();
        }
        // Number addition
        if ($left->isNumber() && $right->isNumber()) {
            // Result is float if either operand is float
            return ($left->name === Type::TYPE_FLOAT || $right->name === Type::TYPE_FLOAT)
                ? Type::float()
                : Type::int();
        }
        // Array concatenation
        if ($left->name === Type::TYPE_ARRAY && $right->name === Type::TYPE_ARRAY) {
            return Type::array();
        }
        return Type::unknown();
    }

    private function inferNumeric(Type $left, Type $right): Type
    {
        if ($left->isNumber() && $right->isNumber()) {
            return ($left->name === Type::TYPE_FLOAT || $right->name === Type::TYPE_FLOAT)
                ? Type::float()
                : Type::int();
        }
        return Type::unknown();
    }

    private function inferUnaryOp(UnaryOp $node): Type
    {
        $operand = $this->infer($node->operand);

        return match ($node->op) {
            '!' => Type::bool(),
            '-' => $operand->isNumber() ? $operand : Type::unknown(),
            '+' => $operand->isNumber() ? $operand : Type::unknown(),
            '~' => Type::int(),  // Bitwise NOT returns int
            '!' => Type::bool(),
            default => Type::unknown(),
        };
    }

    private function inferAssignment(Assignment $node): Type
    {
        $type = $this->infer($node->value);
        $this->setVariableType($node->variable, $type);
        return $type;
    }

    private function inferArrayAccess(ArrayAccess $node): Type
    {
        $array = $this->infer($node->array);
        if ($array->name === Type::TYPE_ARRAY) {
            return Type::any();  // Array element type is unknown without more info
        }
        return Type::unknown();
    }

    private function inferArrayLiteral(ArrayLiteral $node): Type
    {
        if (empty($node->items)) {
            return Type::array();
        }
        // Infer common type of all elements
        $types = array_map(fn($item) => $this->infer($item), $node->items);
        $common = $types[0];
        foreach ($types as $t) {
            if (!$t->isAssignableTo($common) && !$common->isAssignableTo($t)) {
                return Type::array();  // Mixed types
            }
        }
        return Type::array();
    }

    private function inferFunctionCall(FunctionCall $node): Type
    {
        // PHP function return types
        return match ($node->name) {
            // String functions return string
            'substr', 'trim', 'ltrim', 'rtrim', 'strtoupper', 'strtolower',
            'ucfirst', 'lcfirst', 'str_replace', 'str_repeat', 'str_pad',
            'strip_tags', 'htmlspecialchars', 'md5', 'sha1', 'strpos',
            'substr_count', 'ucwords', 'strrev', 'str_shuffle' => Type::string(),

            // Numeric functions return int or float
            'intval', 'int',             'floor', 'ceil', 'round', 'abs', 'min', 'max',
            'rand', 'mt_rand', 'array_sum', 'array_count_values', 'strlen', 'count' => Type::int(),
            'floatval', 'doubleval', 'sqrt', 'log', 'sin', 'cos', 'tan',
            'asin', 'acos', 'atan', 'sinh', 'cosh', 'tanh' => Type::float(),

            // Boolean functions
            'empty', 'is_null', 'is_array', 'is_int', 'is_float', 'is_string',
            'is_bool', 'is_numeric', 'is_callable', 'isset' => Type::bool(),

            // Array functions
            'array', 'array_keys', 'array_values', 'array_merge',
            'array_slice', 'array_reverse', 'array_map', 'array_filter',
            'array_search', 'array_column', 'array_flip', 'array_fill',
            'array_rand', 'array_shift', 'array_pop', 'array_unshift',
            'array_key_exists', 'in_array', 'array_push' => Type::int(),  // array_push returns count

            // Type conversion
            'strval' => Type::string(),

            // JSON
            'json_decode' => Type::any(),
            'json_encode' => Type::string(),

            // Encoding
            'urlencode', 'urldecode' => Type::string(),
            'base64_encode' => Type::string(),
            'base64_decode' => Type::string(),

            // Formatting
            'number_format', 'sprintf' => Type::string(),

            // Date/Time
            'time' => Type::int(),
            'date' => Type::string(),

            default => Type::unknown(),
        };
    }

    private function inferMethodCall(MethodCall $node): Type
    {
        $object = $this->infer($node->object);
        return match ($node->method) {
            // String methods
            'trim', 'trimStart', 'trimEnd', 'uppercase', 'lowercase',
            'md5', 'sha1', 'toString' => Type::string(),
            'length' => Type::int(),
            'indexOf' => Type::int(),
            'contains' => Type::bool(),
            'isEmpty' => Type::bool(),
            'count' => Type::int(),
            'add', 'push' => Type::void(),
            'map', 'filter', 'slice', 'sliceArray' => Type::array(),
            'reversed' => Type::array(),
            'sum' => Type::int(),
            'first', 'last' => Type::any(),
            'random' => Type::any(),
            'remove', 'removeAt' => Type::void(),
            'contains' => Type::bool(),
            'keys', 'values' => Type::array(),
            'toJson', 'serialize' => Type::string(),
            default => Type::unknown(),
        };
    }

    private function inferTernary(Ternary $node): Type
    {
        $then = $node->then ? $this->infer($node->then) : Type::null();
        $else = $this->infer($node->else);

        // Find common supertype
        if ($then->equals($else)) {
            return $then;
        }
        if ($then->isAssignableTo($else)) {
            return $else;
        }
        if ($else->isAssignableTo($then)) {
            return $then;
        }
        return Type::any();
    }

    private function inferCast(Cast $node): Type
    {
        return match ($node->type) {
            'int', 'integer' => Type::int(),
            'float', 'double' => Type::float(),
            'string' => Type::string(),
            'bool', 'boolean' => Type::bool(),
            'array' => Type::array(),
            'object' => Type::any(),
            default => Type::unknown(),
        };
    }

    private function inferFunctionLiteral(FunctionLiteral $node): Type
    {
        // Function literal type is represented as a function type
        // For now, return a generic function type
        return Type::any();
    }

    private function inferNewExpr(NewExpr $node): Type
    {
        return Type::class($node->className);
    }
}
