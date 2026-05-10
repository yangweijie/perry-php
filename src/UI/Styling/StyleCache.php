<?php

declare(strict_types=1);

namespace Perry\UI\Styling;

final class StyleCache
{
    private array $store = [];

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->store);
    }

    public function get(string $key): mixed
    {
        return $this->store[$key] ?? null;
    }

    public function set(string $key, mixed $value): void
    {
        $this->store[$key] = $value;
    }

    public function remove(string $key): void
    {
        unset($this->store[$key]);
    }

    public function clear(): void
    {
        $this->store = [];
    }

    public static function keyForWidget(object $widget): string
    {
        return 'w_' . spl_object_id($widget);
    }

    public static function keyForStyle(Style $style): string
    {
        $data = $style->allProperties();
        return 's_' . md5(serialize($data));
    }

    public static function keyForCss(Style $style): string
    {
        $data = $style->allProperties();
        return 'c_' . md5(serialize($data));
    }
}
