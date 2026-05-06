<?php

declare(strict_types=1);

namespace Perry\Build;

final class LibraryResolver
{
    private string $libDir;

    public function __construct(string $libDir = 'lib')
    {
        $this->libDir = $libDir;
    }

    public function resolve(Target $target): array
    {
        $runtimeLib = $this->findLibrary('runtime', $target);
        $stdlibLib = $this->findLibrary('stdlib', $target);
        $uiLib = $this->findLibrary('ui', $target);

        return [
            'runtime' => $runtimeLib,
            'stdlib' => $stdlibLib,
            'ui' => $uiLib,
        ];
    }

    private function findLibrary(string $name, Target $target): ?string
    {
        $candidates = [
            $this->libDir . "/libperry_{$name}_{$target->value}.a",
            $this->libDir . "/libperry_{$name}.a",
            $this->libDir . "/perry_{$name}_{$target->value}.lib",
            $this->libDir . "/perry_{$name}.lib",
        ];

        foreach ($candidates as $candidate) {
            if (file_exists($candidate)) {
                return realpath($candidate);
            }
        }

        return null;
    }

    public function findLinker(Target $target): string
    {
        if ($target->isApple()) {
            return $this->findAppleLinker();
        }

        if ($target === Target::Windows) {
            return 'cl.exe';
        }

        return 'cc';
    }

    private function findAppleLinker(): string
    {
        $xcrun = shell_exec('xcrun -f clang 2>/dev/null');
        if ($xcrun) {
            return trim($xcrun);
        }

        return 'clang';
    }

    public function findSdkPath(Target $target): ?string
    {
        if (!$target->isApple()) {
            return null;
        }

        $sdk = match ($target) {
            Target::MacOs => 'macosx',
            Target::IOS, Target::IOSSimulator => 'iphoneos',
            Target::TvOs => 'appletvos',
            Target::VisionOs => 'xros',
            Target::WatchOs => 'watchos',
            default => null,
        };

        if ($sdk === null) {
            return null;
        }

        $path = shell_exec("xcrun --sdk {$sdk} --show-sdk-path 2>/dev/null");
        return $path ? trim($path) : null;
    }
}
