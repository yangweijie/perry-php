<?php

declare(strict_types=1);

namespace Perry\UI\Widget;

use Perry\UI\Widget;
use Perry\UI\WidgetKind;
use Perry\UI\Styling\Style;

/**
 * A container widget that applies animation/transition to its child.
 * The transition properties (duration, easing, delay) from the style
 * are applied to animate changes to the child widget.
 */
final class AnimatedContainer extends Widget
{
    private Widget $child;

    public function __construct(Widget $child, ?Style $style = null)
    {
        parent::__construct();
        $this->child = $child;
        if ($style !== null) {
            $this->style = $style;
        }
    }

    public function kind(): WidgetKind
    {
        return WidgetKind::AnimatedContainer;
    }

    public function getChild(): Widget
    {
        return $this->child;
    }
}