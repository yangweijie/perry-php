<?php

declare(strict_types=1);

namespace Perry\UI\Widget;

use Perry\UI\Action;
use Perry\UI\Binding;
use Perry\UI\Widget;
use Perry\UI\WidgetKind;

final class DatePicker extends Widget
{
    private ?Binding $dateObj = null;
    private ?Binding $isOpenObj = null;
    private ?Action $onChangeObj = null;
    private ?Action $onOpenChangeObj = null;

    public function __construct(
        ?Binding $date = null,
        ?Binding $isOpen = null,
        ?Action $onChange = null,
        ?Action $onOpenChange = null,
    ) {
        parent::__construct();
        $this->dateObj = $date;
        $this->isOpenObj = $isOpen;
        if ($onChange instanceof Action) {
            $this->onChangeObj = $onChange;
        }
        if ($onOpenChange instanceof Action) {
            $this->onOpenChangeObj = $onOpenChange;
        }
    }

    public function kind(): WidgetKind
    {
        return WidgetKind::DatePicker;
    }

    public function getDate(): ?Binding
    {
        return $this->dateObj;
    }

    public function getIsOpen(): ?Binding
    {
        return $this->isOpenObj;
    }

    public function getOnChange(): ?Action
    {
        return $this->onChangeObj;
    }

    public function getOnOpenChange(): ?Action
    {
        return $this->onOpenChangeObj;
    }
}
