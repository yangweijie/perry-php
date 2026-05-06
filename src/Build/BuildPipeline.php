<?php

declare(strict_types=1);

namespace Perry\Build;

use Perry\UI\Platform\DriverFactory;
use Perry\UI\Platform\PlatformDriver;

final class BuildPipeline
{
    private Target $target;
    private LibraryResolver $resolver;
    private Linker $linker;
    private PlatformDriver $driver;

    public function __construct(?Target $target = null)
    {
        $this->target = $target ?? Target::autoDetect();
        $this->resolver = new LibraryResolver();
        $this->linker = new Linker($this->resolver);
        $this->driver = DriverFactory::forTarget($this->target);
    }

    public function target(): Target
    {
        return $this->target;
    }

    public function driver(): PlatformDriver
    {
        return $this->driver;
    }

    public function compile(string $sourceFile, string $outputFile): bool
    {
        $objectFile = $this->compileToObject($sourceFile);
        if ($objectFile === null) {
            return false;
        }

        return $this->linker->link($this->target, $objectFile, $outputFile);
    }

    private function compileToObject(string $sourceFile): ?string
    {
        $objectFile = tempnam(sys_get_temp_dir(), 'perry_') . '.o';

        $cmd = [
            'cc',
            '-c',
            '-o', $objectFile,
            $sourceFile,
        ];

        $cmdString = implode(' ', array_map('escapeshellarg', $cmd));
        exec($cmdString . ' 2>&1', $output, $exitCode);

        if ($exitCode !== 0) {
            return null;
        }

        return $objectFile;
    }

    public function getLibraries(): array
    {
        return $this->resolver->resolve($this->target);
    }

    public function getLinkerCommand(string $objectFile, string $outputFile): array
    {
        return $this->linker->buildLinkCommand($this->target, $objectFile, $outputFile);
    }

    public function getInfo(): array
    {
        return [
            'target' => $this->target->value,
            'display_name' => $this->target->displayName(),
            'driver' => $this->driver->name(),
            'libraries' => $this->getLibraries(),
            'linker' => $this->resolver->findLinker($this->target),
            'sdk_path' => $this->resolver->findSdkPath($this->target),
        ];
    }
}
