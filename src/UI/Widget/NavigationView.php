<?php

declare(strict_types=1);

namespace Perry\UI\Widget;

use Perry\UI\Widget;
use Perry\UI\WidgetKind;

final class NavigationView extends Widget
{
    private array $screens;

    public function __construct(
        Widget ...$screens
    ) {
        parent::__construct();
        $this->screens = $screens;
    }

    public function kind(): WidgetKind
    {
        return WidgetKind::NavigationView;
    }

    public function screens(): array
    {
        return $this->screens;
    }
}