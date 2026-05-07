<?php

declare(strict_types=1);

namespace Perry;

use Perry\Build\BuildPipeline;
use Perry\Build\Target;
use Perry\Codegen\CodegenFactory;
use Perry\UI\Platform\DriverFactory;
use Perry\UI\Platform\PlatformDriver;
use Perry\UI\Styling\Style;
use Perry\UI\Widget;

final class App
{
    private ?Widget $root = null;
    private ?PlatformDriver $driver = null;
    private BuildPipeline $pipeline;
    private CodegenFactory $codegen;
    private ?Target $overrideTarget = null;

    public function __construct(?Target $target = null)
    {
        $this->pipeline = new BuildPipeline($target);
        $this->codegen = new CodegenFactory();
    }

    public function setRoot(Widget $widget): static
    {
        $this->root = $widget;
        return $this;
    }

    public function driver(): PlatformDriver
    {
        if ($this->driver === null) {
            $this->driver = DriverFactory::forTarget($this->pipeline->target());
        }
        return $this->driver;
    }

    public function run(): void
    {
        if ($this->root === null) {
            throw new \RuntimeException('No root widget set. Call setRoot() before run().');
        }

        $driver = $this->driver();
        $this->buildWidgetTree($driver, $this->root);
        $driver->setBody($this->root->handle());
        $driver->run();
    }

    private function buildWidgetTree(PlatformDriver $driver, Widget $widget): void
    {
        $driver->createWidget($widget);

        if ($widget->getStyle() !== null) {
            $driver->applyStyle($widget->handle(), $widget->getStyle());
        }

        foreach ($widget->children() as $child) {
            $this->buildWidgetTree($driver, $child);
            $driver->addChild($widget->handle(), $child->handle());
        }
    }

    public function generateCode(string $backendName): string
    {
        if ($this->root === null) {
            throw new \RuntimeException('No root widget set. Call setRoot() before generateCode().');
        }

        $backend = $this->codegen->get($backendName);
        return $backend->generate($this->root);
    }

    public function setTarget(Target $target): void
    {
        $this->overrideTarget = $target;
        $this->pipeline = new BuildPipeline($target);
    }

    public function generateForTarget(): string
    {
        if ($this->root === null) {
            throw new \RuntimeException('No root widget set. Call setRoot() before generateForTarget().');
        }

        $target = $this->overrideTarget ?? $this->pipeline->target();
        $backend = $this->codegen->forTarget($target);
        return $backend->generate($this->root);
    }

    public function pipeline(): BuildPipeline
    {
        return $this->pipeline;
    }

    public function codegen(): CodegenFactory
    {
        return $this->codegen;
    }

    public function info(): array
    {
        return [
            'target' => $this->pipeline->target()->value,
            'display_name' => $this->pipeline->target()->displayName(),
            'driver' => $this->driver()->name(),
            'codegen_backends' => $this->codegen->available(),
        ];
    }
}
