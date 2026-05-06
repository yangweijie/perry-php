<?php

declare(strict_types=1);

namespace Perry\Build;

final class TargetDetector
{
    public static function detect(): Target
    {
        return Target::autoDetect();
    }

    public static function detectWithDetails(): array
    {
        $os = PHP_OS_FAMILY;
        $arch = php_uname('m');

        return [
            'target' => Target::autoDetect(),
            'os' => $os,
            'arch' => $arch,
            'php_version' => PHP_VERSION,
            'is_arm' => str_contains($arch, 'arm') || str_contains($arch, 'aarch64'),
            'is_x86' => str_contains($arch, 'x86') || str_contains($arch, 'x64'),
        ];
    }
}
