<?php

declare(strict_types=1);

namespace Perry\Codegen;

use Perry\Build\Target;
use Perry\UI\Widget;

abstract class CodegenBackend
{
    protected int $objectId = 0;

    abstract public function name(): string;

    abstract public function supports(Target $target): bool;

    abstract public function generate(Widget $root): string;

    /**
     * Generate a unique ID for widgets.
     * Override in subclasses if you need a specific prefix.
     */
    protected function nextId(): string
    {
        return 'widget_' . (string) ++$this->objectId;
    }

    /**
     * Generate platform-specific main activity/entry point code.
     * Override in backends that need to generate activity classes (e.g., Android).
     */
    public function generateMainActivity(string $outputName): string
    {
        return '';
    }

    public function generateToFile(Widget $root, string $outputPath): bool
    {
        $content = $this->generate($root);
        return file_put_contents($outputPath, $content) !== false;
    }
}
