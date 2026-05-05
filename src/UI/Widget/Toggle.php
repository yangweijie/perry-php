<?php

declare(strict_types=1);

namespace Perry\UI\Widget;

use Perry\UI\StateId;
use Perry\UI\Widget;
use Perry\UI\WidgetKind;

final class Toggle extends Widget
{
    public function __construct(
        private StateId $isOn,
        private string $label = '',
    ) {
        parent::__construct();
    }

    public function kind(): WidgetKind
    {
        return WidgetKind::Toggle;
    }

    public function isOn(): StateId
    {
        return $this->isOn;
    }

    public function label(): string
    {
        return $this->label;
    }
}
