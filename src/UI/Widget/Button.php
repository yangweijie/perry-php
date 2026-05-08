<?php

declare(strict_types=1);

namespace Perry\UI\Widget;

use Perry\UI\Action;
use Perry\UI\ActionType;
use Perry\UI\Widget;
use Perry\UI\WidgetKind;

final class Button extends Widget
{
    private ?Action $actionObj = null;

    public function __construct(
        private string $label,
        private Action|\Closure|null $action = null,
    ) {
        parent::__construct();
        if ($action instanceof Action) {
            $this->actionObj = $action;
        } elseif ($action instanceof \Closure) {
            $this->actionObj = Action::fromClosure($action);
        }
    }

    public function kind(): WidgetKind
    {
        return WidgetKind::Button;
    }

    public function label(): string
    {
        return $this->label;
    }

    public function getAction(): ?Action
    {
        return $this->actionObj;
    }
}
