<?php

declare(strict_types=1);

namespace Perry\Build;

enum Target: string
{
    case MacOs = 'macos';
    case IOS = 'ios';
    case IOSSimulator = 'ios-simulator';
    case TvOs = 'tvos';
    case VisionOs = 'visionos';
    case WatchOs = 'watchos';
    case Android = 'android';
    case Gtk4Linux = 'gtk4-linux';
    case Windows = 'windows';
    case Web = 'web';
    case Wasm = 'wasm';

    public function displayName(): string
    {
        return match ($this) {
            self::MacOs => 'macOS',
            self::IOS => 'iOS',
            self::IOSSimulator => 'iOS Simulator',
            self::TvOs => 'tvOS',
            self::VisionOs => 'visionOS',
            self::WatchOs => 'watchOS',
            self::Android => 'Android',
            self::Gtk4Linux => 'GTK4/Linux',
            self::Windows => 'Windows',
            self::Web => 'Web',
            self::Wasm => 'WebAssembly',
        };
    }

    public function isApple(): bool
    {
        return in_array($this, [self::MacOs, self::IOS, self::IOSSimulator, self::TvOs, self::VisionOs, self::WatchOs], true);
    }

    public function isMobile(): bool
    {
        return in_array($this, [self::IOS, self::IOSSimulator, self::Android, self::WatchOs], true);
    }

    public function staticLibName(): string
    {
        return match ($this) {
            self::MacOs => 'libperry_darwin.a',
            self::IOS, self::IOSSimulator => 'libperry_ios.a',
            self::TvOs => 'libperry_tvos.a',
            self::VisionOs => 'libperry_visionos.a',
            self::WatchOs => 'libperry_watchos.a',
            self::Android => 'libperry_android.a',
            self::Gtk4Linux => 'libperry_linux.a',
            self::Windows => 'perry_windows.lib',
            self::Web, self::Wasm => 'libperry_wasm.a',
        };
    }

    public static function autoDetect(): self
    {
        $os = PHP_OS_FAMILY;

        return match ($os) {
            'Darwin' => self::MacOs,
            'Linux' => self::Gtk4Linux,
            'Windows' => self::Windows,
            default => self::Web,
        };
    }

    public static function fromString(string $value): self
    {
        // Handle common aliases
        $aliases = [
            'swiftui' => self::MacOs->value,
            'swift' => self::MacOs->value,
            'mac' => self::MacOs->value,
            'ios' => self::IOS->value,
            'android' => self::Android->value,
            'gtk4' => self::Gtk4Linux->value,
            'gtk' => self::Gtk4Linux->value,
            'linux' => self::Gtk4Linux->value,
            'windows' => self::Windows->value,
            'win' => self::Windows->value,
            'web' => self::Web->value,
            'wasm' => self::Wasm->value,
        ];

        $normalized = $aliases[$value] ?? $value;

        foreach (self::cases() as $case) {
            if ($case->value === $normalized) {
                return $case;
            }
        }

        throw new \InvalidArgumentException("Unknown target: {$value}");
    }
}
