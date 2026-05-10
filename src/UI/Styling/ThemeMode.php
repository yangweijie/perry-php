<?php

declare(strict_types=1);

namespace Perry\UI\Styling;

enum ThemeMode: string
{
    case Light = 'light';
    case Dark = 'dark';

    public function label(): string
    {
        return match ($this) {
            self::Light => 'Light',
            self::Dark => 'Dark',
        };
    }

    public function opposite(): self
    {
        return match ($this) {
            self::Light => self::Dark,
            self::Dark => self::Light,
        };
    }
}
