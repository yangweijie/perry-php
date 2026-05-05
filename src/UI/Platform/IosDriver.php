<?php

declare(strict_types=1);

namespace Perry\UI\Platform;

use Perry\UI\Widget;

final class IosDriver extends AbstractPlatformDriver
{
    public function name(): string
    {
        return 'ios';
    }

    protected function createText(Widget $widget): array
    {
        return ['type' => 'UILabel', 'text' => $widget->content()];
    }

    protected function createButton(Widget $widget): array
    {
        return ['type' => 'UIButton', 'title' => $widget->label()];
    }

    protected function createVStack(Widget $widget): array
    {
        return ['type' => 'UIStackView', 'axis' => 'vertical'];
    }

    protected function createHStack(Widget $widget): array
    {
        return ['type' => 'UIStackView', 'axis' => 'horizontal'];
    }

    protected function createSpacer(Widget $widget): array
    {
        return ['type' => 'UIView', 'spacer' => true];
    }

    protected function createImage(Widget $widget): array
    {
        return ['type' => 'UIImageView', 'source' => $widget->source()];
    }

    protected function createScrollView(Widget $widget): array
    {
        return ['type' => 'UIScrollView'];
    }

    protected function createTextInput(Widget $widget): array
    {
        return ['type' => 'UITextField', 'placeholder' => $widget->placeholder()];
    }

    protected function createToggle(Widget $widget): array
    {
        return ['type' => 'UISwitch', 'label' => $widget->label()];
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
        fwrite(STDERR, "[iOS] Running native app...\n");
    }

    public function quit(): void
    {
    }
}
