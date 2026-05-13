<?php

declare(strict_types=1);

namespace Perry\UI\Widget;

use Perry\UI\Action;
use Perry\UI\Binding;
use Perry\UI\Widget;
use Perry\UI\WidgetKind;

final class Checkbox extends Widget
{
    private ?Action $onChangeObj = null;
    private ?Binding $isCheckedObj = null;

    public function __construct(
        private string $label,
        ?Binding $isChecked = null,
        ?Action $onChange = null,
    ) {
        parent::__construct();
        $this->isCheckedObj = $isChecked;
        if ($onChange instanceof Action) {
            $this->onChangeObj = $onChange;
        }
    }

    public function kind(): WidgetKind
    {
        return WidgetKind::Checkbox;
    }

    public function label(): string
    {
        return $this->label;
    }

    public function getIsChecked(): ?Binding
    {
        return $this->isCheckedObj;
    }

    public function getOnChange(): ?Action
    {
        return $this->onChangeObj;
    }
}
