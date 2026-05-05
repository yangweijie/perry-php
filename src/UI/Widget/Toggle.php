<?php

declare(strict_types=1);

namespace Perry\UI\Widget;

use Perry\UI\Action;
use Perry\UI\Widget;
use Perry\UI\WidgetKind;

final class Toggle extends Widget
{
    private ?Action $onToggleObj = null;

    public function __construct(
        private string $label,
        ?Action $onToggle = null,
    ) {
        parent::__construct();
        if ($onToggle instanceof Action) {
            $this->onToggleObj = $onToggle;
        }
    }

    public function kind(): WidgetKind
    {
        return WidgetKind::Toggle;
    }

    public function label(): string
    {
        return $this->label;
    }

    public function getOnToggle(): ?Action
    {
        return $this->onToggleObj;
    }
}
