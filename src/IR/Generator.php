<?php

declare(strict_types=1);

namespace Perry\IR;

interface Generator
{
    // Core
    public function generateProgram(Program $node): string;
    public function generateAssignment(Assignment $node): string;
    public function generateIf(IfStatement $node): string;
    public function generateBinaryOp(BinaryOp $node): string;
    public function generateUnaryOp(UnaryOp $node): string;
    public function generateVariable(Variable $node): string;
    public function generateLiteral(Literal $node): string;
    public function generateFunctionCall(FunctionCall $node): string;
    public function generateReturn(ReturnStatement $node): string;
    public function generateArrayAccess(ArrayAccess $node): string;
    public function generateMethodCall(MethodCall $node): string;
    public function generatePropertyAccess(PropertyAccess $node): string;
    public function generateTernary(Ternary $node): string;
    public function generateArrayLiteral(ArrayLiteral $node): string;

    // Loops
    public function generateWhile(WhileStatement $node): string;
    public function generateFor(ForStatement $node): string;
    public function generateForeach(ForeachStatement $node): string;

    // Loop control
    public function generateBreak(BreakStatement $node): string;
    public function generateContinue(ContinueStatement $node): string;

    // Switch / Match
    public function generateSwitch(SwitchStatement $node): string;
    public function generateCase(CaseNode $node): string;
    public function generateMatch(MatchExpression $node): string;

    // Output
    public function generateEcho(EchoStatement $node): string;
    public function generatePrint(PrintStatement $node): string;

    // Type casting
    public function generateCast(Cast $node): string;

    // Increment / Decrement
    public function generateIncrement(Increment $node): string;
    public function generateDecrement(Decrement $node): string;

    // Compound assignment
    public function generatePlusAssign(PlusAssign $node): string;
    public function generateMinusAssign(MinusAssign $node): string;
    public function generateMulAssign(MulAssign $node): string;
    public function generateDivAssign(DivAssign $node): string;
    public function generateModAssign(ModAssign $node): string;

    // Additional binary ops
    public function generatePow(PowOp $node): string;
    public function generateBitwiseAnd(BitwiseAnd $node): string;
    public function generateBitwiseOr(BitwiseOr $node): string;
    public function generateBitwiseXor(BitwiseXor $node): string;
    public function generateShiftLeft(ShiftLeft $node): string;
    public function generateShiftRight(ShiftRight $node): string;
    public function generateSpaceship(SpaceshipOp $node): string;
    public function generateCoalesce(CoalesceOp $node): string;
    public function generateLogicalAnd(LogicalAnd $node): string;
    public function generateLogicalOr(LogicalOr $node): string;
    public function generateLogicalXor(LogicalXor $node): string;

    // Additional unary ops
    public function generateUnaryPlus(UnaryPlus $node): string;
    public function generateBitwiseNot(BitwiseNot $node): string;

    // Nullsafe
    public function generateNullsafeMethodCall(NullsafeMethodCall $node): string;
    public function generateNullsafePropertyAccess(NullsafePropertyAccess $node): string;

    // Exceptions
    public function generateThrow(ThrowStatement $node): string;
    public function generateTryCatch(TryCatchStatement $node): string;
    public function generateCatchClause(CatchClause $node): string;

    // Static
    public function generateStaticCall(StaticCall $node): string;
    public function generateStaticPropertyAccess(StaticPropertyAccess $node): string;
    public function generateClassConstFetch(ClassConstFetch $node): string;

    // Include
    public function generateInclude(IncludeStatement $node): string;

    // Class / Object
    public function generatePropertyDeclaration(PropertyDeclaration $node): string;
    public function generateMethodParameter(MethodParameter $node): string;
    public function generateMethodDeclaration(MethodDeclaration $node): string;
    public function generateClassDeclaration(ClassDeclaration $node): string;
    public function generateNewExpr(NewExpr $node): string;

    // Anonymous functions / closures
    public function generateFunctionLiteral(FunctionLiteral $node): string;

    // Array operations
    public function generateArrayPop(ArrayPop $node): string;
    public function generateArrayUnshift(ArrayUnshift $node): string;
    public function generateArrayKeyExists(ArrayKeyExists $node): string;
    public function generateArrayReduce(ArrayReduce $node): string;
    public function generateArrayUnique(ArrayUnique $node): string;
    public function generateArrayDiff(ArrayDiff $node): string;
}
