<?php

declare(strict_types=1);

namespace Perry\UI\Widget;

use Perry\UI\Action;
use Perry\UI\Binding;
use Perry\UI\Widget;
use Perry\UI\WidgetKind;

final class RadioButton extends Widget
{
    private ?Action $onChangeObj = null;
    private ?Binding $selectedValueObj = null;

    public function __construct(
        private string $label,
        private string $group,
        private string $value,
        ?Binding $selectedValue = null,
        ?Action $onChange = null,
    ) {
        parent::__construct();
        $this->selectedValueObj = $selectedValue;
        if ($onChange instanceof Action) {
            $this->onChangeObj = $onChange;
        }
    }

    public function kind(): WidgetKind
    {
        return WidgetKind::RadioButton;
    }

    public function label(): string
    {
        return $this->label;
    }

    public function group(): string
    {
        return $this->group;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function getSelectedValue(): ?Binding
    {
        return $this->selectedValueObj;
    }

    public function getOnChange(): ?Action
    {
        return $this->onChangeObj;
    }
}
