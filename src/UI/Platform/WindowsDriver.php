<?php

declare(strict_types=1);

namespace Perry\UI\Platform;

use Perry\UI\Widget;

final class WindowsDriver extends AbstractPlatformDriver
{
    public function name(): string
    {
        return 'windows';
    }

    protected function createText(Widget $widget): array
    {
        return ['type' => 'TextBlock', 'text' => $widget->content()];
    }

    protected function createButton(Widget $widget): array
    {
        return ['type' => 'Button', 'content' => $widget->label()];
    }

    protected function createVStack(Widget $widget): array
    {
        return ['type' => 'StackPanel', 'orientation' => 'Vertical'];
    }

    protected function createHStack(Widget $widget): array
    {
        return ['type' => 'StackPanel', 'orientation' => 'Horizontal'];
    }

    protected function createSpacer(Widget $widget): array
    {
        return ['type' => 'Separator'];
    }

    protected function createImage(Widget $widget): array
    {
        return ['type' => 'Image', 'source' => $widget->source()];
    }

    protected function createScrollView(Widget $widget): array
    {
        return ['type' => 'ScrollViewer'];
    }

    protected function createTextInput(Widget $widget): array
    {
        return ['type' => 'TextBox', 'placeholder' => $widget->placeholder()];
    }

    protected function createToggle(Widget $widget): array
    {
        return ['type' => 'ToggleSwitch', 'header' => $widget->label()];
    }

    protected function applyStyleProperty(mixed $native, string $property, mixed $value): void
    {
        $native["style_{$property}"] = $value;
    }

    public function setBody(\Perry\UI\WidgetHandle $root): void
    {
    }

    public function run(): void
    {
        fwrite(STDERR, "[Windows] Running native app...\n");
    }

    public function quit(): void
    {
    }
}
