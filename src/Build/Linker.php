<?php

declare(strict_types=1);

namespace Perry\Build;

final class Linker
{
    private LibraryResolver $resolver;

    public function __construct(?LibraryResolver $resolver = null)
    {
        $this->resolver = $resolver ?? new LibraryResolver();
    }

    public function buildLinkCommand(Target $target, string $objectFile, string $outputFile): array
    {
        $linker = $this->resolver->findLinker($target);
        $libs = $this->resolver->resolve($target);
        $sdkPath = $this->resolver->findSdkPath($target);

        $cmd = [$linker];

        if ($target->isApple() && $sdkPath) {
            $cmd[] = '-isysroot';
            $cmd[] = $sdkPath;
        }

        $cmd[] = '-o';
        $cmd[] = $outputFile;
        $cmd[] = $objectFile;

        foreach ($libs as $lib) {
            if ($lib !== null) {
                $cmd[] = $lib;
            }
        }

        $cmd = array_merge($cmd, $this->platformFlags($target));

        return $cmd;
    }

    public function link(Target $target, string $objectFile, string $outputFile): bool
    {
        $cmd = $this->buildLinkCommand($target, $objectFile, $outputFile);
        $cmdString = implode(' ', array_map('escapeshellarg', $cmd));

        exec($cmdString . ' 2>&1', $output, $exitCode);

        return $exitCode === 0;
    }

    /** @return string[] */
    private function platformFlags(Target $target): array
    {
        return match ($target) {
            Target::MacOs => [
                '-framework', 'Cocoa',
                '-framework', 'CoreGraphics',
                '-framework', 'Foundation',
            ],
            Target::IOS, Target::IOSSimulator => [
                '-framework', 'UIKit',
                '-framework', 'CoreGraphics',
                '-framework', 'Foundation',
            ],
            Target::TvOs => [
                '-framework', 'TVUIKit',
                '-framework', 'UIKit',
            ],
            Target::VisionOs => [
                '-framework', 'RealityKit',
                '-framework', 'SwiftUI',
            ],
            Target::WatchOs => [
                '-framework', 'WatchKit',
            ],
            Target::Gtk4Linux => [
                exec('pkg-config --libs gtk4 2>/dev/null') ?: '-lgtk-4',
            ],
            Target::Windows => [
                'user32.lib',
                'gdi32.lib',
                'shell32.lib',
            ],
            Target::Android => [
                '-landroid',
                '-llog',
            ],
            default => [],
        };
    }
}
