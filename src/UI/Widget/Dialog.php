<?php

declare(strict_types=1);

namespace Perry\UI\Widget;

use Perry\UI\Binding;
use Perry\UI\Widget;
use Perry\UI\WidgetKind;

final class Dialog extends Widget
{
    private ?Binding $isOpenObj = null;

    public function __construct(
        ?Binding $isOpen = null,
        Widget ...$children,
    ) {
        parent::__construct();
        $this->isOpenObj = $isOpen;
        $this->children = $children;
    }

    public function kind(): WidgetKind
    {
        return WidgetKind::Dialog;
    }

    /** @return Widget[] */
    public function children(): array
    {
        return $this->children;
    }

    public function getIsOpen(): ?Binding
    {
        return $this->isOpenObj;
    }
}
