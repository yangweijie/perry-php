<?php

declare(strict_types=1);

namespace Perry\UI\Widget;

use Perry\UI\Widget;
use Perry\UI\WidgetKind;

/**
 * A wrapper that applies transition effects to its child widget.
 * Unlike AnimatedContainer which animates style changes, Transition
 * wraps a child and applies transition properties for enter/exit animations.
 */
final class Transition extends Widget
{
    private Widget $child;
    private string $type;

    public function __construct(Widget $child, string $type = 'fade')
    {
        parent::__construct();
        $this->child = $child;
        $this->type = $type;
    }

    public function kind(): WidgetKind
    {
        return WidgetKind::Transition;
    }

    public function getChild(): Widget
    {
        return $this->child;
    }

    public function getType(): string
    {
        return $this->type;
    }
}