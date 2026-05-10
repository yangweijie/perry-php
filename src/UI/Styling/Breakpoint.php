<?php

declare(strict_types=1);

namespace Perry\UI\Styling;

enum Breakpoint: string
{
    case Sm = 'sm';   // < 640px
    case Md = 'md';   // 640-1023px
    case Lg = 'lg';   // 1024-1279px
    case Xl = 'xl';   // >= 1280px

    public function minWidth(): int
    {
        return match ($this) {
            self::Sm => 0,
            self::Md => 640,
            self::Lg => 1024,
            self::Xl => 1280,
        };
    }

    public function maxWidth(): ?int
    {
        return match ($this) {
            self::Sm => 639,
            self::Md => 1023,
            self::Lg => 1279,
            self::Xl => null,
        };
    }

    public function toCssMediaQuery(): string
    {
        $parts = [];
        if ($this->minWidth() > 0) {
            $parts[] = "(min-width: {$this->minWidth()}px)";
        }
        if ($this->maxWidth() !== null) {
            $parts[] = "(max-width: {$this->maxWidth()}px)";
        }
        return implode(' and ', $parts);
    }

    public static function fromString(string $value): self
    {
        return match ($value) {
            'sm' => self::Sm,
            'md' => self::Md,
            'lg' => self::Lg,
            'xl' => self::Xl,
            default => self::Md,
        };
    }
}
