<?php

declare(strict_types=1);

namespace Perry\UI\Widget;

use Perry\UI\Widget;
use Perry\UI\WidgetKind;

final class ListWidget extends Widget
{
    private array $items;

    public function __construct(
        Widget ...$items
    ) {
        parent::__construct();
        $this->items = $items;
    }

    public function kind(): WidgetKind
    {
        return WidgetKind::ListWidget;
    }

    public function items(): array
    {
        return $this->items;
    }
}