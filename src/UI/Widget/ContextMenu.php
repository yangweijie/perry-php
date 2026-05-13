<?php

declare(strict_types=1);

namespace Perry\UI\Widget;

use Perry\UI\Action;
use Perry\UI\Binding;
use Perry\UI\Widget;
use Perry\UI\WidgetKind;

final class ContextMenu extends Widget
{
    private ?Binding $isOpenObj = null;
    private ?Action $onSelectObj = null;
    private ?Action $onDismissObj = null;

    /** @var array<string, string> */
    private array $items;

    /**
     * @param array<string, string> $items label => value
     */
    public function __construct(
        array $items,
        ?Binding $isOpen = null,
        ?Action $onSelect = null,
        ?Action $onDismiss = null,
    ) {
        parent::__construct();
        $this->items = $items;
        $this->isOpenObj = $isOpen;
        if ($onSelect instanceof Action) {
            $this->onSelectObj = $onSelect;
        }
        if ($onDismiss instanceof Action) {
            $this->onDismissObj = $onDismiss;
        }
    }

    public function kind(): WidgetKind
    {
        return WidgetKind::ContextMenu;
    }

    /** @return array<string, string> */
    public function items(): array
    {
        return $this->items;
    }

    public function getIsOpen(): ?Binding
    {
        return $this->isOpenObj;
    }

    public function getOnSelect(): ?Action
    {
        return $this->onSelectObj;
    }

    public function getOnDismiss(): ?Action
    {
        return $this->onDismissObj;
    }
}
