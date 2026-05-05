<?php

declare(strict_types=1);

namespace Perry\UI\Widget;

use Perry\UI\StateId;
use Perry\UI\Widget;
use Perry\UI\WidgetKind;

final class Slider extends Widget
{
    public function __construct(
        private StateId $value,
        private float $min = 0.0,
        private float $max = 100.0,
        private float $step = 1.0,
    ) {
        parent::__construct();
    }

    public function kind(): WidgetKind
    {
        return WidgetKind::Slider;
    }

    public function value(): StateId
    {
        return $this->value;
    }

    public function min(): float
    {
        return $this->min;
    }

    public function max(): float
    {
        return $this->max;
    }

    public function step(): float
    {
        return $this->step;
    }
}