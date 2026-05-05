<?php

declare(strict_types=1);

namespace Perry\UI\Platform;

use Perry\UI\Widget;

final class MacOsDriver extends AbstractPlatformDriver
{
    public function name(): string
    {
        return 'macos';
    }

    protected function createText(Widget $widget): array
    {
        return ['type' => 'NSTextField', 'text' => $widget->content()];
    }

    protected function createButton(Widget $widget): array
    {
        return ['type' => 'NSButton', 'title' => $widget->label()];
    }

    protected function createVStack(Widget $widget): array
    {
        return ['type' => 'NSStackView', 'orientation' => 'vertical'];
    }

    protected function createHStack(Widget $widget): array
    {
        return ['type' => 'NSStackView', 'orientation' => 'horizontal'];
    }

    protected function createSpacer(Widget $widget): array
    {
        return ['type' => 'NSView', 'spacer' => true];
    }

    protected function createImage(Widget $widget): array
    {
        return ['type' => 'NSImageView', 'source' => $widget->source()];
    }

    protected function createScrollView(Widget $widget): array
    {
        return ['type' => 'NSScrollView'];
    }

    protected function createTextInput(Widget $widget): array
    {
        return ['type' => 'NSTextField', 'editable' => true, 'placeholder' => $widget->placeholder()];
    }

    protected function createToggle(Widget $widget): array
    {
        return ['type' => 'NSSwitch', 'label' => $widget->label()];
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
        fwrite(STDERR, "[macOS] Running native app...\n");
    }

    public function quit(): void
    {
    }
}
