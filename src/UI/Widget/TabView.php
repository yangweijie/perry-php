<?php

declare(strict_types=1);

namespace Perry\UI\Widget;

use Perry\UI\Widget;
use Perry\UI\WidgetKind;

final class TabView extends Widget
{
    private array $tabs;

    public function __construct(
        Widget ...$tabs
    ) {
        parent::__construct();
        $this->tabs = $tabs;
    }

    public function kind(): WidgetKind
    {
        return WidgetKind::TabView;
    }

    public function tabs(): array
    {
        return $this->tabs;
    }
}