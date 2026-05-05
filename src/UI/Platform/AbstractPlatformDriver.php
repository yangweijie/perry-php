<?php

declare(strict_types=1);

namespace Perry\UI\Platform;

use Perry\UI\Styling\Style;
use Perry\UI\Styling\StyleProperty;
use Perry\UI\Widget;
use Perry\UI\WidgetHandle;
use Perry\UI\WidgetKind;

abstract class AbstractPlatformDriver implements PlatformDriver
{
    /** @var array<string, mixed> */
    protected array $nativeHandles = [];

    public function createWidget(Widget $widget): WidgetHandle
    {
        $handle = $widget->handle();
        $this->nativeHandles[$handle->id] = $this->createNativeWidget($widget);
        return $handle;
    }

    public function destroyWidget(WidgetHandle $handle): void
    {
        unset($this->nativeHandles[$handle->id]);
    }

    public function addChild(WidgetHandle $parent, WidgetHandle $child): void
    {
    }

    protected function createNativeWidget(Widget $widget): mixed
    {
        return match ($widget->kind()) {
            WidgetKind::Text => $this->createText($widget),
            WidgetKind::Button => $this->createButton($widget),
            WidgetKind::VStack => $this->createVStack($widget),
            WidgetKind::HStack => $this->createHStack($widget),
            WidgetKind::Spacer => $this->createSpacer($widget),
            WidgetKind::Image => $this->createImage($widget),
            WidgetKind::ScrollView => $this->createScrollView($widget),
            WidgetKind::TextInput => $this->createTextInput($widget),
            WidgetKind::Toggle => $this->createToggle($widget),
            default => null,
        };
    }

    abstract protected function createText(Widget $widget): mixed;
    abstract protected function createButton(Widget $widget): mixed;
    abstract protected function createVStack(Widget $widget): mixed;
    abstract protected function createHStack(Widget $widget): mixed;
    abstract protected function createSpacer(Widget $widget): mixed;
    abstract protected function createImage(Widget $widget): mixed;
    abstract protected function createScrollView(Widget $widget): mixed;
    abstract protected function createTextInput(Widget $widget): mixed;
    abstract protected function createToggle(Widget $widget): mixed;

    public function applyStyle(WidgetHandle $handle, Style $style): void
    {
        $native = $this->nativeHandles[$handle->id] ?? null;
        if ($native === null) {
            return;
        }

        foreach ($style->all() as $prop => $value) {
            $this->applyStyleProperty($native, $prop, $value);
        }
    }

    abstract protected function applyStyleProperty(mixed $native, string $property, mixed $value): void;
}
