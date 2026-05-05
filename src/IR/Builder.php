<?php

declare(strict_types=1);

namespace Perry\IR;

use PhpParser\NodeTraverser;
use PhpParser\ParserFactory;

class Builder
{
    private ParserFactory $parserFactory;

    public function __construct()
    {
        $this->parserFactory = new ParserFactory();
    }

    public function buildFromClosure(\Closure $closure): Program
    {
        $reflection = new \ReflectionFunction($closure);
        $file = $reflection->getFileName();
        $startLine = $reflection->getStartLine();
        $endLine = $reflection->getEndLine();

        if ($file === false || $startLine === false || $endLine === false) {
            return new Program();
        }

        $lines = file($file);
        $code = implode('', array_slice($lines, $startLine - 1, $endLine - $startLine + 1));
        // Strip trailing comma from multi-line Action::fromClosure(closure, [bindings]) calls
        // ReflectionFunction::getEndLine() includes the closing brace line, which may have
        // a trailing comma from the second argument of fromClosure()
        $code = rtrim($code);
        if (str_ends_with($code, ',')) {
            $code = substr($code, 0, -1);
        }
        // A bare function() { ... } is not a valid PHP statement — it needs to be
        // wrapped as an expression statement. Add trailing semicolon if needed.
        if (!str_ends_with(rtrim($code), ';')) {
            $code = rtrim($code) . ';';
        }
        $code = '<?php ' . $code;

        return $this->buildFromCode($code);
    }

    public function buildFromCode(string $code): Program
    {
        $parser = $this->parserFactory->createForNewestSupportedVersion();
        $ast = $parser->parse($code);

        if ($ast === null) {
            return new Program();
        }

        $visitor = new AstToIrVisitor();
        $traverser = new NodeTraverser();
        $traverser->addVisitor($visitor);
        $traverser->traverse($ast);

        return $visitor->getProgram();
    }
}
