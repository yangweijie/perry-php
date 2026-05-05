<?php

declare(strict_types=1);

namespace Perry\UI\Platform;

use Perry\UI\Styling\StyleProperty;
use Perry\UI\Widget;

final class WebDriver extends AbstractPlatformDriver
{
    public function name(): string
    {
        return 'web';
    }

    protected function createText(Widget $widget): array
    {
        return ['tag' => 'span', 'text' => $widget->content()];
    }

    protected function createButton(Widget $widget): array
    {
        return ['tag' => 'button', 'text' => $widget->label()];
    }

    protected function createVStack(Widget $widget): array
    {
        return ['tag' => 'div', 'style' => ['display' => 'flex', 'flex-direction' => 'column']];
    }

    protected function createHStack(Widget $widget): array
    {
        return ['tag' => 'div', 'style' => ['display' => 'flex', 'flex-direction' => 'row']];
    }

    protected function createSpacer(Widget $widget): array
    {
        return ['tag' => 'div', 'style' => ['flex' => '1']];
    }

    protected function createImage(Widget $widget): array
    {
        return ['tag' => 'img', 'src' => $widget->source()];
    }

    protected function createScrollView(Widget $widget): array
    {
        return ['tag' => 'div', 'style' => ['overflow' => 'auto']];
    }

    protected function createTextInput(Widget $widget): array
    {
        return ['tag' => 'input', 'type' => 'text', 'placeholder' => $widget->placeholder()];
    }

    protected function createToggle(Widget $widget): array
    {
        return ['tag' => 'label', 'text' => $widget->label(), 'input' => ['type' => 'checkbox']];
    }

    protected function applyStyleProperty(mixed $native, string $property, mixed $value): void
    {
        $cssProp = $this->toCssProperty($property);
        $cssValue = $this->toCssValue($property, $value);
        $native['style'][$cssProp] = $cssValue;
    }

    private function toCssProperty(string $property): string
    {
        return match ($property) {
            'background_color' => 'background-color',
            'foreground_color' => 'color',
            'border_color' => 'border-color',
            'border_width' => 'border-width',
            'cornerRadius' => 'border-radius',
            'font_size' => 'font-size',
            'font_weight' => 'font-weight',
            'font_family' => 'font-family',
            'text_alignment' => 'text-align',
            'shadow_color' => 'box-shadow',
            'line_spacing' => 'line-height',
            default => str_replace('_', '-', $property),
        };
    }

    private function toCssValue(string $property, mixed $value): string
    {
        if (in_array($property, ['font_size', 'border_width', 'padding', 'margin', 'width', 'height', 'cornerRadius'], true)) {
            return "{$value}px";
        }
        if ($property === 'opacity') {
            return (string) $value;
        }
        return (string) $value;
    }

    public function setBody(\Perry\UI\WidgetHandle $root): void
    {
    }

    public function run(): void
    {
        fwrite(STDERR, "[Web] Serving web app...\n");
    }

    public function quit(): void
    {
    }
}
