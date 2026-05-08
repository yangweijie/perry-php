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

    /**
     * Generate source code only, without invoking native toolchain.
     *
     * Uses the platform backend to produce the target-appropriate output
     * (SwiftUI, Compose, GTK4 XML, HTML, etc.) and writes it to the
     * specified file path. Skips the C-compilation and linker stages.
     *
     * Returns CompileResult with the generated file path (or failure).
     */
    public function generateOnly(\Perry\UI\Widget $root, string $outputFile): CompileResult
    {
        $factory = new \Perry\Codegen\CodegenFactory();
        $backend = $factory->forTarget($this->target);
        $source = $backend->generate($root);

        $dir = dirname($outputFile);
        if ($dir !== '' && $dir !== '.' && !is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        if (file_put_contents($outputFile, $source) === false) {
            return CompileResult::failure("Failed to write: {$outputFile}");
        }

        return CompileResult::success($outputFile, $outputFile);
    }

    /**
     * Get the generated source as a string without writing to disk.
     */
    public function generateSource(\Perry\UI\Widget $root): string
    {
        $factory = new \Perry\Codegen\CodegenFactory();
        $backend = $factory->forTarget($this->target);
        return $backend->generate($root);
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
