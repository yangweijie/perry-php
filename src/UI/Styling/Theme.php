<?php

declare(strict_types=1);

namespace Perry\UI\Styling;

class Theme
{
    private ThemeMode $mode;
    private string $name;

    /** @var array<string, string> */
    private array $colors;

    /** @var array<string, string> */
    private static array $defaultLightColors = [
        'primary' => '#007aff',
        'background' => '#ffffff',
        'surface' => '#f5f5f5',
        'text' => '#000000',
        'text-secondary' => '#666666',
        'error' => '#ff3b30',
        'border' => '#e0e0e0',
        'disabled' => '#999999',
    ];

    /** @var array<string, string> */
    private static array $defaultDarkColors = [
        'primary' => '#0a84ff',
        'background' => '#000000',
        'surface' => '#1c1c1e',
        'text' => '#ffffff',
        'text-secondary' => '#999999',
        'error' => '#ff453a',
        'border' => '#38383a',
        'disabled' => '#666666',
    ];

    public function __construct(string $name = 'default', ThemeMode $mode = ThemeMode::Light, ?array $colors = null)
    {
        $this->name = $name;
        $this->mode = $mode;
        $defaults = $mode === ThemeMode::Light ? self::$defaultLightColors : self::$defaultDarkColors;
        $this->colors = $colors !== null ? array_merge($defaults, $colors) : $defaults;
    }

    public static function light(?array $overrides = null): self
    {
        return new self('light', ThemeMode::Light, $overrides);
    }

    public static function dark(?array $overrides = null): self
    {
        return new self('dark', ThemeMode::Dark, $overrides);
    }

    public function name(): string
    {
        return $this->name;
    }

    public function mode(): ThemeMode
    {
        return $this->mode;
    }

    public function withMode(ThemeMode $mode): self
    {
        return new self($this->name, $mode, $this->colors);
    }

    public function setColor(string $token, string $color): self
    {
        $this->colors[$token] = $color;
        return $this;
    }

    public function getColor(string $token, ?string $default = null): ?string
    {
        return $this->colors[$token] ?? $default;
    }

    public function hasColor(string $token): bool
    {
        return array_key_exists($token, $this->colors);
    }

    public function resolveValue(string $value): string
    {
        if (str_starts_with($value, '@')) {
            $token = substr($value, 1);
            return $this->colors[$token] ?? $value;
        }
        return $value;
    }

    /** @return array<string, string> */
    public function allColors(): array
    {
        return $this->colors;
    }

    public static function toCssCustomProperties(?self $light = null, ?self $dark = null): string
    {
        $light ??= self::light();
        $dark ??= self::dark();

        $props = '';

        $props .= "        :root {\n";
        foreach ($light->allColors() as $token => $color) {
            $props .= "            --theme-{$token}: {$color};\n";
        }
        $props .= "        }\n";

        $props .= "        @media (prefers-color-scheme: dark) {\n";
        $props .= "            :root {\n";
        foreach ($dark->allColors() as $token => $color) {
            $props .= "                --theme-{$token}: {$color};\n";
        }
        $props .= "            }\n";
        $props .= "        }\n";

        return $props;
    }
}
