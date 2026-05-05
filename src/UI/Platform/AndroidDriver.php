<?php

declare(strict_types=1);

namespace Perry\UI\Platform;

use Perry\UI\Widget;

final class AndroidDriver extends AbstractPlatformDriver
{
    public function name(): string
    {
        return 'android';
    }

    protected function createText(Widget $widget): array
    {
        return ['type' => 'TextView', 'text' => $widget->content()];
    }

    protected function createButton(Widget $widget): array
    {
        return ['type' => 'Button', 'text' => $widget->label()];
    }

    protected function createVStack(Widget $widget): array
    {
        return ['type' => 'LinearLayout', 'orientation' => 'vertical'];
    }

    protected function createHStack(Widget $widget): array
    {
        return ['type' => 'LinearLayout', 'orientation' => 'horizontal'];
    }

    protected function createSpacer(Widget $widget): array
    {
        return ['type' => 'Space'];
    }

    protected function createImage(Widget $widget): array
    {
        return ['type' => 'ImageView', 'source' => $widget->source()];
    }

    protected function createScrollView(Widget $widget): array
    {
        return ['type' => 'ScrollView'];
    }

    protected function createTextInput(Widget $widget): array
    {
        return ['type' => 'EditText', 'hint' => $widget->placeholder()];
    }

    protected function createToggle(Widget $widget): array
    {
        return ['type' => 'Switch', 'text' => $widget->label()];
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
        fwrite(STDERR, "[Android] Running native app...\n");
    }

    public function quit(): void
    {
    }
}
