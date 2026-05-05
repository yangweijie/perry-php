<?php

declare(strict_types=1);

namespace Perry\UI\Widget;

use Perry\UI\Action;
use Perry\UI\StateId;
use Perry\UI\Widget;
use Perry\UI\WidgetKind;

final class Slider extends Widget
{
    private ?Action $onChangeObj = null;

    public function __construct(
        private StateId $value,
        private float $min = 0.0,
        private float $max = 100.0,
        private float $step = 1.0,
        ?Action $onChange = null,
    ) {
        parent::__construct();
        if ($onChange instanceof Action) {
            $this->onChangeObj = $onChange;
        }
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

    public function getOnChange(): ?Action
    {
        return $this->onChangeObj;
    }
}