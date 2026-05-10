<?php

declare(strict_types=1);

namespace Perry\UI;

use Perry\UI\Styling\Style;

abstract class Widget
{
    protected WidgetHandle $handle;
    protected ?Style $style = null;
    /** @var Widget[] */
    protected array $children = [];

    public function __construct()
    {
        $this->handle = WidgetHandle::next();
    }

    public function handle(): WidgetHandle
    {
        return $this->handle;
    }

    abstract public function kind(): WidgetKind;

    public function style(Style $style): static
    {
        $this->style = $style;
        return $this;
    }

    public function setStyle(?Style $style): void
    {
        $this->style = $style;
    }

    public function addChild(Widget $child): static
    {
        $this->children[] = $child;
        return $this;
    }

    /** @return Widget[] */
    public function children(): array
    {
        return $this->children;
    }

    public function getStyle(): ?Style
    {
        return $this->style;
    }
}
