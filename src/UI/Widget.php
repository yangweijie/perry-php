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
    protected ?string $actionName = null;
    protected ?string $widgetName = null;
    protected ?Binding $visibleBinding = null;

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

    /**
     * Set an optional action name for named-action dispatch.
     * When set, backends can generate named function references
     * (e.g., onclick="onClear()") instead of inlining closure code.
     */
    public function actionName(string $name): static
    {
        $this->actionName = $name;
        return $this;
    }

    public function getActionName(): ?string
    {
        return $this->actionName;
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

    /**
     * Set an XAML element name (x:Name) for this widget.
     * Allows hand-written code-behind to reference this element.
     */
    public function name(string $name): static
    {
        $this->widgetName = $name;
        return $this;
    }

    public function getName(): ?string
    {
        return $this->widgetName;
    }

    /**
     * Set a visibility binding: the widget is visible only when the binding
     * evaluates to boolean true. When not set, the widget is always visible.
     *
     * Backends can use this to generate conditional visibility (e.g.,
     * Visibility="Visible"/"Collapsed" in WPF XAML).
     */
    public function visible(Binding $binding): static
    {
        $this->visibleBinding = $binding;
        return $this;
    }

    /**
     * Get the visibility binding, or null if not set.
     */
    public function getVisible(): ?Binding
    {
        return $this->visibleBinding;
    }
}
