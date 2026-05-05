<?php

declare(strict_types=1);

namespace Perry\IR;

use PhpParser\Node as PhpNode;
use PhpParser\NodeVisitorAbstract;

class AstToIrVisitor extends NodeVisitorAbstract
{
    private Program $program;
    private array $parentStack = [];

    public function __construct()
    {
        $this->program = new Program();
    }

    public function getProgram(): Program
    {
        return $this->program;
    }

    public function enterNode(PhpNode $node): ?PhpNode
    {
        $this->parentStack[] = $node;
        return null;
    }

    public function leaveNode(PhpNode $node): ?PhpNode
    {
        array_pop($this->parentStack);

        if ($node instanceof PhpNode\Stmt\Expression) {
            if (!$this->isInsideIf()) {
                // Skip $var = Action::fromClosure(function() { ... }) wrapper nodes
                if ($this->isFromClosureAssignment($node->expr)) {
                    return null;
                }
                $expr = $this->transformExpr($node->expr);
                if ($expr) {
                    $this->program->statements[] = $expr;
                }
            }
        } elseif ($node instanceof PhpNode\Stmt\If_) {
            if (!$this->isInsideIf()) {
                $if = $this->transformIf($node);
                if ($if) {
                    $this->program->statements[] = $if;
                }
            }
        } elseif ($node instanceof PhpNode\Stmt\Return_) {
            if (!$this->isInsideIf() && $node->expr) {
                // Skip return Action::fromClosure(function() { ... }) wrapper nodes
                if ($this->isFromClosureCall($node->expr)) {
                    return null;
                }
                $expr = $this->transformExpr($node->expr);
                if ($expr) {
                    $this->program->statements[] = new ReturnStatement($expr);
                }
            }
        } elseif ($node instanceof PhpNode\Stmt\While_) {
            if (!$this->isInsideIf()) {
                $while = $this->transformWhile($node);
                if ($while) {
                    $this->program->statements[] = $while;
                }
            }
        } elseif ($node instanceof PhpNode\Stmt\For_) {
            if (!$this->isInsideIf()) {
                $for = $this->transformFor($node);
                if ($for) {
                    $this->program->statements[] = $for;
                }
            }
        } elseif ($node instanceof PhpNode\Stmt\Foreach_) {
            if (!$this->isInsideIf()) {
                $foreach = $this->transformForeach($node);
                if ($foreach) {
                    $this->program->statements[] = $foreach;
                }
            }
        } elseif ($node instanceof PhpNode\Stmt\Echo_) {
            if (!$this->isInsideIf()) {
                $this->program->statements[] = $this->transformEcho($node);
            }
        } elseif ($node instanceof PhpNode\Stmt\Switch_) {
            if (!$this->isInsideIf()) {
                $switch = $this->transformSwitch($node);
                if ($switch) {
                    $this->program->statements[] = $switch;
                }
            }
        } elseif ($node instanceof PhpNode\Stmt\Throw_) {
            if (!$this->isInsideIf()) {
                $throw = $this->transformThrow($node);
                if ($throw) {
                    $this->program->statements[] = $throw;
                }
            }
        } elseif ($node instanceof PhpNode\Stmt\TryCatch) {
            if (!$this->isInsideIf()) {
                $tryCatch = $this->transformTryCatch($node);
                if ($tryCatch) {
                    $this->program->statements[] = $tryCatch;
                }
            }
        } elseif ($node instanceof PhpNode\Stmt\Break_) {
            if (!$this->isInsideIf()) {
                $this->program->statements[] = new BreakStatement($node->num instanceof PhpNode\Scalar\LNumber ? (int) $node->num->value : 1);
            }
        } elseif ($node instanceof PhpNode\Stmt\Continue_) {
            if (!$this->isInsideIf()) {
                $this->program->statements[] = new ContinueStatement($node->num instanceof PhpNode\Scalar\LNumber ? (int) $node->num->value : 1);
            }
        }

        return null;
    }

    private function isInsideIf(): bool
    {
        for ($i = count($this->parentStack) - 1; $i >= 0; $i--) {
            if ($this->parentStack[$i] instanceof PhpNode\Stmt\If_) {
                return true;
            }
        }
        return false;
    }

    private function transformIf(PhpNode\Stmt\If_ $node): ?IfStatement
    {
        $condition = $this->transformExpr($node->cond);
        if (!$condition) {
            return null;
        }

        $then = $this->transformStmts($node->stmts);
        $else = null;

        if ($node->elseifs) {
            foreach ($node->elseifs as $elseif) {
                $elseIfCondition = $this->transformExpr($elseif->cond);
                $elseIfThen = $this->transformStmts($elseif->stmts);
                $else = new IfStatement($elseIfCondition, $elseIfThen, $else);
            }
        }

        if ($node->else) {
            $else = $this->transformStmts($node->else->stmts);
        }

        return new IfStatement($condition, $then, $else);
    }

    private function transformWhile(PhpNode\Stmt\While_ $node): ?WhileStatement
    {
        $condition = $this->transformExpr($node->cond);
        if (!$condition) {
            return null;
        }
        $body = $this->transformStmts($node->stmts);
        return new WhileStatement($condition, $body);
    }

    private function transformFor(PhpNode\Stmt\For_ $node): ?ForStatement
    {
        $init = [];
        foreach ($node->init as $expr) {
            $n = $this->transformExpr($expr);
            if ($n) {
                $init[] = $n;
            }
        }

        $cond = [];
        foreach ($node->cond as $expr) {
            $n = $this->transformExpr($expr);
            if ($n) {
                $cond[] = $n;
            }
        }

        $loop = [];
        foreach ($node->loop as $expr) {
            $n = $this->transformExpr($expr);
            if ($n) {
                $loop[] = $n;
            }
        }

        $body = $this->transformStmts($node->stmts);
        return new ForStatement($init, $cond, $loop, $body);
    }

    private function transformForeach(PhpNode\Stmt\Foreach_ $node): ?ForeachStatement
    {
        $iterable = $this->transformExpr($node->expr);
        if (!$iterable) {
            return null;
        }

        $valueVar = $this->transformExpr($node->valueVar);
        if (!$valueVar) {
            return null;
        }

        $keyVar = $node->keyVar ? $this->transformExpr($node->keyVar) : null;
        $body = $this->transformStmts($node->stmts);

        return new ForeachStatement($valueVar, $keyVar, $iterable, $body);
    }

    private function transformEcho(PhpNode\Stmt\Echo_ $node): EchoStatement
    {
        $values = [];
        foreach ($node->exprs as $expr) {
            $n = $this->transformExpr($expr);
            if ($n) {
                $values[] = $n;
            }
        }
        return new EchoStatement($values);
    }

    private function transformSwitch(PhpNode\Stmt\Switch_ $node): ?SwitchStatement
    {
        $condition = $this->transformExpr($node->cond);
        if (!$condition) {
            return null;
        }

        $cases = [];
        foreach ($node->cases as $case) {
            $caseCond = $case->cond ? $this->transformExpr($case->cond) : null;
            $body = $this->transformStmts($case->stmts);
            $cases[] = new CaseNode($caseCond, $body);
        }

        return new SwitchStatement($condition, $cases);
    }

    private function transformThrow(PhpNode\Stmt\Throw_ $node): ?ThrowStatement
    {
        $expr = $this->transformExpr($node->expr);
        if (!$expr) {
            return null;
        }
        return new ThrowStatement($expr);
    }

    private function transformTryCatch(PhpNode\Stmt\TryCatch $node): ?TryCatchStatement
    {
        $tryBody = $this->transformStmts($node->stmts);

        $catches = [];
        foreach ($node->catches as $catch) {
            $catchType = $catch->types[0]->toString() ?? 'Exception';
            $catchVar = $catch->var instanceof PhpNode\Expr\Variable ? $catch->var->name : 'e';
            $catchBody = $this->transformStmts($catch->stmts);
            $catches[] = new CatchClause($catchType, $catchVar, $catchBody);
        }

        $finally = null;
        if ($node->finally) {
            $finally = $this->transformStmts($node->finally->stmts);
        }

        return new TryCatchStatement($tryBody, $catches, $finally);
    }

    private function transformStmts(array $stmts): Program
    {
        $program = new Program();
        foreach ($stmts as $stmt) {
            if ($stmt instanceof PhpNode\Stmt\Expression) {
                $expr = $this->transformExpr($stmt->expr);
                if ($expr) {
                    $program->statements[] = $expr;
                }
            } elseif ($stmt instanceof PhpNode\Stmt\If_) {
                $if = $this->transformIf($stmt);
                if ($if) {
                    $program->statements[] = $if;
                }
            } elseif ($stmt instanceof PhpNode\Stmt\Return_) {
                if ($stmt->expr) {
                    $expr = $this->transformExpr($stmt->expr);
                    if ($expr) {
                        $program->statements[] = new ReturnStatement($expr);
                    }
                }
            } elseif ($stmt instanceof PhpNode\Stmt\While_) {
                $while = $this->transformWhile($stmt);
                if ($while) {
                    $program->statements[] = $while;
                }
            } elseif ($stmt instanceof PhpNode\Stmt\For_) {
                $for = $this->transformFor($stmt);
                if ($for) {
                    $program->statements[] = $for;
                }
            } elseif ($stmt instanceof PhpNode\Stmt\Foreach_) {
                $foreach = $this->transformForeach($stmt);
                if ($foreach) {
                    $program->statements[] = $foreach;
                }
            } elseif ($stmt instanceof PhpNode\Stmt\Echo_) {
                $program->statements[] = $this->transformEcho($stmt);
            } elseif ($stmt instanceof PhpNode\Stmt\Switch_) {
                $switch = $this->transformSwitch($stmt);
                if ($switch) {
                    $program->statements[] = $switch;
                }
            } elseif ($stmt instanceof PhpNode\Stmt\Throw_) {
                $throw = $this->transformThrow($stmt);
                if ($throw) {
                    $program->statements[] = $throw;
                }
            } elseif ($stmt instanceof PhpNode\Stmt\TryCatch) {
                $tryCatch = $this->transformTryCatch($stmt);
                if ($tryCatch) {
                    $program->statements[] = $tryCatch;
                }
            } elseif ($stmt instanceof PhpNode\Stmt\Break_) {
                $program->statements[] = new BreakStatement($stmt->num instanceof PhpNode\Scalar\LNumber ? (int) $stmt->num->value : 1);
            } elseif ($stmt instanceof PhpNode\Stmt\Continue_) {
                $program->statements[] = new ContinueStatement($stmt->num instanceof PhpNode\Scalar\LNumber ? (int) $stmt->num->value : 1);
            }
        }
        return $program;
    }

    private function transformExpr(PhpNode\Expr $expr): ?Node
    {
        return match (true) {
            $expr instanceof PhpNode\Expr\Assign => $this->transformAssign($expr),
            $expr instanceof PhpNode\Expr\Variable => new Variable($expr->name),
            $expr instanceof PhpNode\Scalar\LNumber => new Literal($expr->value),
            $expr instanceof PhpNode\Scalar\DNumber => new Literal($expr->value),
            $expr instanceof PhpNode\Scalar\String_ => new Literal($expr->value),
            $expr instanceof PhpNode\Expr\ConstFetch => $this->transformConstFetch($expr),

            $expr instanceof PhpNode\Expr\BinaryOp\Plus => new BinaryOp('+', $this->transformExpr($expr->left), $this->transformExpr($expr->right)),
            $expr instanceof PhpNode\Expr\BinaryOp\Minus => new BinaryOp('-', $this->transformExpr($expr->left), $this->transformExpr($expr->right)),
            $expr instanceof PhpNode\Expr\BinaryOp\Mul => new BinaryOp('*', $this->transformExpr($expr->left), $this->transformExpr($expr->right)),
            $expr instanceof PhpNode\Expr\BinaryOp\Div => new BinaryOp('/', $this->transformExpr($expr->left), $this->transformExpr($expr->right)),
            $expr instanceof PhpNode\Expr\BinaryOp\Mod => new BinaryOp('%', $this->transformExpr($expr->left), $this->transformExpr($expr->right)),
            $expr instanceof PhpNode\Expr\BinaryOp\Concat => new BinaryOp('.', $this->transformExpr($expr->left), $this->transformExpr($expr->right)),
            $expr instanceof PhpNode\Expr\BinaryOp\BooleanAnd => new BinaryOp('&&', $this->transformExpr($expr->left), $this->transformExpr($expr->right)),
            $expr instanceof PhpNode\Expr\BinaryOp\BooleanOr => new BinaryOp('||', $this->transformExpr($expr->left), $this->transformExpr($expr->right)),
            $expr instanceof PhpNode\Expr\BinaryOp\Equal => new BinaryOp('==', $this->transformExpr($expr->left), $this->transformExpr($expr->right)),
            $expr instanceof PhpNode\Expr\BinaryOp\NotEqual => new BinaryOp('!=', $this->transformExpr($expr->left), $this->transformExpr($expr->right)),
            $expr instanceof PhpNode\Expr\BinaryOp\Identical => new BinaryOp('===', $this->transformExpr($expr->left), $this->transformExpr($expr->right)),
            $expr instanceof PhpNode\Expr\BinaryOp\NotIdentical => new BinaryOp('!==', $this->transformExpr($expr->left), $this->transformExpr($expr->right)),
            $expr instanceof PhpNode\Expr\BinaryOp\Smaller => new BinaryOp('<', $this->transformExpr($expr->left), $this->transformExpr($expr->right)),
            $expr instanceof PhpNode\Expr\BinaryOp\Greater => new BinaryOp('>', $this->transformExpr($expr->left), $this->transformExpr($expr->right)),
            $expr instanceof PhpNode\Expr\BinaryOp\SmallerOrEqual => new BinaryOp('<=', $this->transformExpr($expr->left), $this->transformExpr($expr->right)),
            $expr instanceof PhpNode\Expr\BinaryOp\GreaterOrEqual => new BinaryOp('>=', $this->transformExpr($expr->left), $this->transformExpr($expr->right)),
            $expr instanceof PhpNode\Expr\BinaryOp\Pow => new PowOp($this->transformExpr($expr->left), $this->transformExpr($expr->right)),
            $expr instanceof PhpNode\Expr\BinaryOp\BitwiseAnd => new BitwiseAnd($this->transformExpr($expr->left), $this->transformExpr($expr->right)),
            $expr instanceof PhpNode\Expr\BinaryOp\BitwiseOr => new BitwiseOr($this->transformExpr($expr->left), $this->transformExpr($expr->right)),
            $expr instanceof PhpNode\Expr\BinaryOp\BitwiseXor => new BitwiseXor($this->transformExpr($expr->left), $this->transformExpr($expr->right)),
            $expr instanceof PhpNode\Expr\BinaryOp\ShiftLeft => new ShiftLeft($this->transformExpr($expr->left), $this->transformExpr($expr->right)),
            $expr instanceof PhpNode\Expr\BinaryOp\ShiftRight => new ShiftRight($this->transformExpr($expr->left), $this->transformExpr($expr->right)),
            $expr instanceof PhpNode\Expr\BinaryOp\Spaceship => new SpaceshipOp($this->transformExpr($expr->left), $this->transformExpr($expr->right)),
            $expr instanceof PhpNode\Expr\BinaryOp\Coalesce => new CoalesceOp($this->transformExpr($expr->left), $this->transformExpr($expr->right)),
            $expr instanceof PhpNode\Expr\BinaryOp\LogicalAnd => new LogicalAnd($this->transformExpr($expr->left), $this->transformExpr($expr->right)),
            $expr instanceof PhpNode\Expr\BinaryOp\LogicalOr => new LogicalOr($this->transformExpr($expr->left), $this->transformExpr($expr->right)),
            $expr instanceof PhpNode\Expr\BinaryOp\LogicalXor => new LogicalXor($this->transformExpr($expr->left), $this->transformExpr($expr->right)),

            $expr instanceof PhpNode\Expr\AssignOp\Concat => $this->transformConcatAssign($expr),
            $expr instanceof PhpNode\Expr\AssignOp\Plus => $this->transformCompoundAssign($expr, PlusAssign::class),
            $expr instanceof PhpNode\Expr\AssignOp\Minus => $this->transformCompoundAssign($expr, MinusAssign::class),
            $expr instanceof PhpNode\Expr\AssignOp\Mul => $this->transformCompoundAssign($expr, MulAssign::class),
            $expr instanceof PhpNode\Expr\AssignOp\Div => $this->transformCompoundAssign($expr, DivAssign::class),
            $expr instanceof PhpNode\Expr\AssignOp\Mod => $this->transformCompoundAssign($expr, ModAssign::class),

            $expr instanceof PhpNode\Expr\BooleanNot => new UnaryOp('!', $this->transformExpr($expr->expr)),
            $expr instanceof PhpNode\Expr\UnaryMinus => new UnaryOp('-', $this->transformExpr($expr->expr)),
            $expr instanceof PhpNode\Expr\UnaryPlus => new UnaryPlus($this->transformExpr($expr->expr)),
            $expr instanceof PhpNode\Expr\BitwiseNot => new BitwiseNot($this->transformExpr($expr->expr)),

            $expr instanceof PhpNode\Expr\PostInc => new Increment($expr->var instanceof PhpNode\Expr\Variable ? $expr->var->name : 'x', false),
            $expr instanceof PhpNode\Expr\PostDec => new Decrement($expr->var instanceof PhpNode\Expr\Variable ? $expr->var->name : 'x', false),
            $expr instanceof PhpNode\Expr\PreInc => new Increment($expr->var instanceof PhpNode\Expr\Variable ? $expr->var->name : 'x', true),
            $expr instanceof PhpNode\Expr\PreDec => new Decrement($expr->var instanceof PhpNode\Expr\Variable ? $expr->var->name : 'x', true),

            $expr instanceof PhpNode\Expr\Cast\Int_ => new Cast('int', $this->transformExpr($expr->expr)),
            $expr instanceof PhpNode\Expr\Cast\Double => new Cast('float', $this->transformExpr($expr->expr)),
            $expr instanceof PhpNode\Expr\Cast\String_ => new Cast('string', $this->transformExpr($expr->expr)),
            $expr instanceof PhpNode\Expr\Cast\Bool_ => new Cast('bool', $this->transformExpr($expr->expr)),
            $expr instanceof PhpNode\Expr\Cast\Array_ => new Cast('array', $this->transformExpr($expr->expr)),
            $expr instanceof PhpNode\Expr\Cast\Object_ => new Cast('object', $this->transformExpr($expr->expr)),

            $expr instanceof PhpNode\Expr\Ternary => $this->transformTernary($expr),
            $expr instanceof PhpNode\Expr\Isset_ => $this->transformIsset($expr),
            $expr instanceof PhpNode\Expr\Empty_ => $this->transformEmpty($expr),
            $expr instanceof PhpNode\Expr\FuncCall => $this->transformFuncCall($expr),
            $expr instanceof PhpNode\Expr\MethodCall => $this->transformMethodCall($expr),
            $expr instanceof PhpNode\Expr\PropertyFetch => $this->transformPropertyFetch($expr),
            $expr instanceof PhpNode\Expr\ArrayDimFetch => $this->transformArrayDimFetch($expr),
            $expr instanceof PhpNode\Expr\Array_ => $this->transformArray($expr),

            $expr instanceof PhpNode\Expr\Match_ => $this->transformMatch($expr),
            $expr instanceof PhpNode\Expr\NullsafeMethodCall => $this->transformNullsafeMethodCall($expr),
            $expr instanceof PhpNode\Expr\NullsafePropertyFetch => $this->transformNullsafePropertyFetch($expr),
            $expr instanceof PhpNode\Expr\StaticCall => $this->transformStaticCall($expr),
            $expr instanceof PhpNode\Expr\StaticPropertyFetch => $this->transformStaticPropertyFetch($expr),
            $expr instanceof PhpNode\Expr\ClassConstFetch => $this->transformClassConstFetch($expr),
            $expr instanceof PhpNode\Expr\Print_ => new PrintStatement($this->transformExpr($expr->expr)),
            $expr instanceof PhpNode\Expr\Include_ => $this->transformInclude($expr),
            $expr instanceof PhpNode\Expr\Clone_ => new FunctionCall('clone', [$this->transformExpr($expr->expr)]),
            $expr instanceof PhpNode\Expr\ErrorSuppress => $this->transformExpr($expr->expr),

            default => null,
        };
    }

    private function transformConstFetch(PhpNode\Expr\ConstFetch $expr): ?Literal
    {
        $name = $expr->name->toString();
        return match ($name) {
            'true' => new Literal(true),
            'false' => new Literal(false),
            'null' => new Literal(null),
            default => new Literal($name),
        };
    }

    private function transformTernary(PhpNode\Expr\Ternary $expr): ?Ternary
    {
        $condition = $this->transformExpr($expr->cond);
        $then = $expr->if ? $this->transformExpr($expr->if) : null;
        $else = $this->transformExpr($expr->else);

        if (!$condition || !$else) {
            return null;
        }

        return new Ternary($condition, $then, $else);
    }

    private function transformMatch(PhpNode\Expr\Match_ $expr): ?MatchExpression
    {
        $condition = $this->transformExpr($expr->cond);
        if (!$condition) {
            return null;
        }

        $arms = [];
        foreach ($expr->arms as $arm) {
            $armConds = [];
            foreach ($arm->conds as $cond) {
                $c = $this->transformExpr($cond);
                if ($c) {
                    $armConds[] = $c;
                }
            }
            $body = $this->transformExpr($arm->body);
            if ($body && !empty($armConds)) {
                $arms[] = ['condition' => count($armConds) === 1 ? $armConds[0] : new ArrayLiteral($armConds), 'body' => $body];
            }
        }

        return new MatchExpression($condition, $arms);
    }

    private function transformIsset(PhpNode\Expr\Isset_ $expr): ?FunctionCall
    {
        $args = [];
        foreach ($expr->vars as $var) {
            $arg = $this->transformExpr($var);
            if ($arg) {
                $args[] = $arg;
            }
        }
        return new FunctionCall('isset', $args);
    }

    private function transformEmpty(PhpNode\Expr\Empty_ $expr): ?FunctionCall
    {
        $arg = $this->transformExpr($expr->expr);
        if (!$arg) {
            return null;
        }
        return new FunctionCall('empty', [$arg]);
    }

    private function transformArray(PhpNode\Expr\Array_ $expr): ?ArrayLiteral
    {
        $items = [];
        foreach ($expr->items as $item) {
            if ($item->value) {
                $value = $this->transformExpr($item->value);
                if ($value) {
                    $items[] = $value;
                }
            }
        }
        return new ArrayLiteral($items);
    }

    private function transformAssign(PhpNode\Expr\Assign $assign): ?Assignment
    {
        if (!$assign->var instanceof PhpNode\Expr\Variable) {
            return null;
        }

        $value = $this->transformExpr($assign->expr);
        if (!$value) {
            return null;
        }

        return new Assignment($assign->var->name, $value);
    }

    private function transformConcatAssign(PhpNode\Expr\AssignOp\Concat $assign): ?Assignment
    {
        if (!$assign->var instanceof PhpNode\Expr\Variable) {
            return null;
        }

        $left = new Variable($assign->var->name);
        $right = $this->transformExpr($assign->expr);
        if (!$right) {
            return null;
        }

        return new Assignment($assign->var->name, new BinaryOp('.', $left, $right));
    }

    private function transformCompoundAssign(PhpNode\Expr\AssignOp $assign, string $class): ?Node
    {
        if (!$assign->var instanceof PhpNode\Expr\Variable) {
            return null;
        }

        $value = $this->transformExpr($assign->expr);
        if (!$value) {
            return null;
        }

        return new $class($assign->var->name, $value);
    }

    private function transformFuncCall(PhpNode\Expr\FuncCall $call): ?FunctionCall
    {
        if (!$call->name instanceof PhpNode\Name) {
            return null;
        }

        $name = $call->name->toString();
        $args = [];

        foreach ($call->args as $arg) {
            $argNode = $this->transformExpr($arg->value);
            if ($argNode) {
                $args[] = $argNode;
            }
        }

        return new FunctionCall($name, $args);
    }

    private function transformMethodCall(PhpNode\Expr\MethodCall $call): ?MethodCall
    {
        if (!$call->name instanceof PhpNode\Identifier) {
            return null;
        }

        $object = $this->transformExpr($call->var);
        if (!$object) {
            return null;
        }

        $args = [];
        foreach ($call->args as $arg) {
            $argNode = $this->transformExpr($arg->value);
            if ($argNode) {
                $args[] = $argNode;
            }
        }

        return new MethodCall($object, $call->name->name, $args);
    }

    private function transformNullsafeMethodCall(PhpNode\Expr\NullsafeMethodCall $call): ?NullsafeMethodCall
    {
        if (!$call->name instanceof PhpNode\Identifier) {
            return null;
        }

        $object = $this->transformExpr($call->var);
        if (!$object) {
            return null;
        }

        $args = [];
        foreach ($call->args as $arg) {
            $argNode = $this->transformExpr($arg->value);
            if ($argNode) {
                $args[] = $argNode;
            }
        }

        return new NullsafeMethodCall($object, $call->name->name, $args);
    }

    private function transformPropertyFetch(PhpNode\Expr\PropertyFetch $fetch): ?PropertyAccess
    {
        if (!$fetch->name instanceof PhpNode\Identifier) {
            return null;
        }

        $object = $this->transformExpr($fetch->var);
        if (!$object) {
            return null;
        }

        return new PropertyAccess($object, $fetch->name->name);
    }

    private function transformNullsafePropertyFetch(PhpNode\Expr\NullsafePropertyFetch $fetch): ?NullsafePropertyAccess
    {
        if (!$fetch->name instanceof PhpNode\Identifier) {
            return null;
        }

        $object = $this->transformExpr($fetch->var);
        if (!$object) {
            return null;
        }

        return new NullsafePropertyAccess($object, $fetch->name->name);
    }

    private function transformArrayDimFetch(PhpNode\Expr\ArrayDimFetch $fetch): ?ArrayAccess
    {
        $array = $this->transformExpr($fetch->var);
        if (!$array || !$fetch->dim) {
            return null;
        }

        $index = $this->transformExpr($fetch->dim);
        if (!$index) {
            return null;
        }

        return new ArrayAccess($array, $index);
    }

    private function transformStaticCall(PhpNode\Expr\StaticCall $call): ?StaticCall
    {
        $class = $call->class instanceof PhpNode\Name ? $call->class->toString() : 'static';
        $method = $call->name instanceof PhpNode\Identifier ? $call->name->name : 'unknown';

        $args = [];
        foreach ($call->args as $arg) {
            $argNode = $this->transformExpr($arg->value);
            if ($argNode) {
                $args[] = $argNode;
            }
        }

        return new StaticCall($class, $method, $args);
    }

    private function transformStaticPropertyFetch(PhpNode\Expr\StaticPropertyFetch $fetch): ?StaticPropertyAccess
    {
        $class = $fetch->class instanceof PhpNode\Name ? $fetch->class->toString() : 'static';
        $property = $fetch->name instanceof PhpNode\Identifier ? $fetch->name->name : 'unknown';
        return new StaticPropertyAccess($class, $property);
    }

    private function transformClassConstFetch(PhpNode\Expr\ClassConstFetch $fetch): ?ClassConstFetch
    {
        $class = $fetch->class instanceof PhpNode\Name ? $fetch->class->toString() : 'static';
        $constant = $fetch->name instanceof PhpNode\Identifier ? $fetch->name->name : 'unknown';
        return new ClassConstFetch($class, $constant);
    }

    private function transformInclude(PhpNode\Expr\Include_ $expr): ?IncludeStatement
    {
        $pathExpr = $this->transformExpr($expr->expr);
        if (!$pathExpr instanceof Literal || !is_string($pathExpr->value)) {
            return null;
        }

        $type = $expr->type;
        $once = ($type & PhpNode\Expr\Include_::TYPE_ONCE) !== 0;
        $require = ($type & PhpNode\Expr\Include_::TYPE_REQUIRE) !== 0;

        return new IncludeStatement($pathExpr->value, $once, $require);
    }

    private function isFromClosureAssignment(PhpNode\Expr $expr): bool
    {
        if (!$expr instanceof PhpNode\Expr\Assign) {
            return false;
        }
        $result = $this->isFromClosureCall($expr->expr);
        return $result;
    }

    private function isFromClosureCall(PhpNode\Expr $expr): bool
    {
        if ($expr instanceof PhpNode\Expr\FuncCall) {
            $name = $expr->name instanceof PhpNode\Name
                ? $expr->name->toString()
                : (string) $expr->name;
            if (!str_contains($name, 'fromClosure')) {
                return false;
            }
            if (count($expr->args) > 0 && $expr->args[0]->value instanceof PhpNode\Expr\Closure) {
                return true;
            }
        } elseif ($expr instanceof PhpNode\Expr\StaticCall) {
            $name = $expr->name instanceof PhpNode\Identifier
                ? $expr->name->name
                : (string) $expr->name;
            if (!str_contains($name, 'fromClosure')) {
                return false;
            }
            if (count($expr->args) > 0 && $expr->args[0]->value instanceof PhpNode\Expr\Closure) {
                return true;
            }
        }
        return false;
    }
}
