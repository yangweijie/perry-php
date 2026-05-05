<?php

declare(strict_types=1);

namespace Perry\UI\Platform;

use Perry\UI\Widget;

final class Gtk4Driver extends AbstractPlatformDriver
{
    public function name(): string
    {
        return 'gtk4';
    }

    protected function createText(Widget $widget): array
    {
        return ['type' => 'GtkLabel', 'label' => $widget->content()];
    }

    protected function createButton(Widget $widget): array
    {
        return ['type' => 'GtkButton', 'label' => $widget->label()];
    }

    protected function createVStack(Widget $widget): array
    {
        return ['type' => 'GtkBox', 'orientation' => 'vertical'];
    }

    protected function createHStack(Widget $widget): array
    {
        return ['type' => 'GtkBox', 'orientation' => 'horizontal'];
    }

    protected function createSpacer(Widget $widget): array
    {
        return ['type' => 'GtkSeparator'];
    }

    protected function createImage(Widget $widget): array
    {
        return ['type' => 'GtkImage', 'file' => $widget->source()];
    }

    protected function createScrollView(Widget $widget): array
    {
        return ['type' => 'GtkScrolledWindow'];
    }

    protected function createTextInput(Widget $widget): array
    {
        return ['type' => 'GtkEntry', 'placeholder' => $widget->placeholder()];
    }

    protected function createToggle(Widget $widget): array
    {
        return ['type' => 'GtkSwitch', 'label' => $widget->label()];
    }

    protected function applyStyleProperty(mixed $native, string $property, mixed $value): void
    {
        $native["css_{$property}"] = $value;
    }

    public function setBody(\Perry\UI\WidgetHandle $root): void
    {
    }

    public function run(): void
    {
        fwrite(STDERR, "[GTK4] Running native app...\n");
    }

    public function quit(): void
    {
    }
}
