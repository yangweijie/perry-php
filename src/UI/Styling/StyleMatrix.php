<?php

declare(strict_types=1);

namespace Perry\UI\Styling;

use Perry\UI\WidgetKind;

enum PlatformSupport: string
{
    case Wired = 'wired';
    case Stub = 'stub';
    case Missing = 'missing';
    case NotApplicable = 'n/a';
}

final class StyleMatrix
{
    /** @var array<string, array<string, PlatformSupport>> */
    private array $matrix = [];

    public function __construct()
    {
        $this->buildMatrix();
    }

    private function buildMatrix(): void
    {
        $platforms = ['macos', 'ios', 'tvos', 'visionos', 'watchos', 'android', 'gtk4', 'windows', 'web'];
        $properties = StyleProperty::cases();

        foreach ($platforms as $platform) {
            foreach ($properties as $prop) {
                $this->matrix[$platform][$prop->value] = $this->defaultSupport($platform, $prop);
            }
        }
    }

    private function defaultSupport(string $platform, StyleProperty $prop): PlatformSupport
    {
        if (in_array($platform, ['tvos', 'visionos', 'watchos'], true)) {
            return PlatformSupport::Stub;
        }

        return PlatformSupport::Wired;
    }

    public function getSupport(string $platform, StyleProperty $property): PlatformSupport
    {
        return $this->matrix[$platform][$property->value] ?? PlatformSupport::Missing;
    }

    public function setSupport(string $platform, StyleProperty $property, PlatformSupport $support): void
    {
        $this->matrix[$platform][$property->value] = $support;
    }

    public function getWiredProperties(string $platform): array
    {
        $wired = [];
        foreach ($this->matrix[$platform] ?? [] as $prop => $support) {
            if ($support === PlatformSupport::Wired) {
                $wired[] = $prop;
            }
        }
        return $wired;
    }

    public function getMissingProperties(string $platform): array
    {
        $missing = [];
        foreach ($this->matrix[$platform] ?? [] as $prop => $support) {
            if ($support === PlatformSupport::Missing) {
                $missing[] = $prop;
            }
        }
        return $missing;
    }

    public function isFullySupported(string $platform): bool
    {
        foreach ($this->matrix[$platform] ?? [] as $support) {
            if ($support !== PlatformSupport::Wired && $support !== PlatformSupport::NotApplicable) {
                return false;
            }
        }
        return true;
    }

    /** @return string[] */
    public function platforms(): array
    {
        return array_keys($this->matrix);
    }
}
