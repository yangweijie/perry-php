<?php

declare(strict_types=1);

namespace Perry\UI\Styling;

use Perry\UI\Widget;

final class StyleResolver
{
    /** @var array<StyleProperty> Theme-aware color properties */
    private const THEME_COLOR_PROPS = [
        StyleProperty::BackgroundColor,
        StyleProperty::ForegroundColor,
        StyleProperty::BorderColor,
        StyleProperty::ShadowColor,
    ];

    public function resolve(Widget $root, ?Theme $theme = null, ?StyleCache $cache = null): void
    {
        $this->resolveNode($root, null, null, $theme, $cache);
    }

    public function resolveForBreakpoint(Widget $root, Breakpoint $breakpoint, ?Theme $theme = null, ?StyleCache $cache = null): void
    {
        $this->resolveNode($root, null, $breakpoint, $theme, $cache);
    }

    private function resolveNode(Widget $widget, ?Style $parentStyle, ?Breakpoint $breakpoint = null, ?Theme $theme = null, ?StyleCache $cache = null): void
    {
        $key = $cache !== null ? StyleCache::keyForWidget($widget) . '_' . ($breakpoint?->value ?? 'base') : null;

        // Cache hit: restore resolved style and skip
        if ($key !== null && $cache->has($key)) {
            $widget->setStyle($cache->get($key));
            return;
        }

        $ownStyle = $widget->getStyle();

        if ($parentStyle !== null) {
            $resolved = $ownStyle !== null ? $parentStyle->merge($ownStyle) : clone $parentStyle;
            $widget->setStyle($resolved);
        }

        if ($breakpoint !== null) {
            $current = $widget->getStyle();
            if ($current !== null) {
                $variant = $current->resolveVariant($breakpoint);
                if ($variant !== null) {
                    $widget->setStyle($current->merge($variant));
                }
            }
        }

        $this->applyTheme($widget, $theme);

        // Store resolved style in cache
        if ($key !== null) {
            $resolved = $widget->getStyle();
            if ($resolved !== null) {
                $cache->set($key, clone $resolved);
            }
        }

        foreach ($widget->children() as $child) {
            $resolvedParent = $widget->getStyle();
            $this->resolveNode($child, $resolvedParent, $breakpoint, $theme, $cache);
        }
    }

    private function applyTheme(Widget $widget, ?Theme $theme): void
    {
        if ($theme === null) {
            return;
        }

        $style = $widget->getStyle();
        if ($style === null) {
            return;
        }

        foreach (self::THEME_COLOR_PROPS as $prop) {
            if ($style->has($prop)) {
                $value = $style->get($prop);
                if (is_string($value) && str_starts_with($value, '@')) {
                    $resolved = $theme->resolveValue($value);
                    $style->set($prop, $resolved);
                }
            }
        }
    }
}
