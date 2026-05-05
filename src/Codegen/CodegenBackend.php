<?php

declare(strict_types=1);

namespace Perry\Codegen;

use Perry\Build\Target;
use Perry\UI\Widget;

abstract class CodegenBackend
{
    abstract public function name(): string;

    abstract public function supports(Target $target): bool;

    abstract public function generate(Widget $root): string;

    public function generateToFile(Widget $root, string $outputPath): bool
    {
        $content = $this->generate($root);
        return file_put_contents($outputPath, $content) !== false;
    }
}
