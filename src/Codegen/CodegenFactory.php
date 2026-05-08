<?php

declare(strict_types=1);

namespace Perry\Codegen;

use Perry\Build\Target;

final class CodegenFactory
{
    /** @var CodegenBackend[] */
    private array $backends = [];

    public function __construct()
    {
        $this->register(new SwiftUIBackend());
        $this->register(new HtmlBackend());
        $this->register(new AndroidXmlBackend());
        $this->register(new WinUIBackend());
        $this->register(new Gtk4Backend());
        $this->register(new ComposeBackend());
        $this->register(new WasmBackend());
        $this->register(new ArkTsBackend());
        $this->register(new GlanceBackend());
        $this->register(new WearTilesBackend());
        $this->register(new FlutterBackend());
    }

    public function register(CodegenBackend $backend): void
    {
        $this->backends[$backend->name()] = $backend;
    }

    public function get(string $name): CodegenBackend
    {
        if (!isset($this->backends[$name])) {
            throw new \InvalidArgumentException("Unknown codegen backend: {$name}");
        }
        return $this->backends[$name];
    }

    public function forTarget(Target $target): CodegenBackend
    {
        foreach ($this->backends as $backend) {
            if ($backend->supports($target)) {
                return $backend;
            }
        }

        throw new \RuntimeException("No codegen backend for target: {$target->value}");
    }

    /** @return string[] */
    public function available(): array
    {
        return array_keys($this->backends);
    }
}
