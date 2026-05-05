<?php

declare(strict_types=1);

namespace Perry\UI\Widget;

use Perry\UI\Widget;
use Perry\UI\WidgetKind;

final class HStack extends Widget
{
    public function __construct(Widget ...$children)
    {
        parent::__construct();
        foreach ($children as $child) {
            $this->addChild($child);
        }
    }

    public function kind(): WidgetKind
    {
        return WidgetKind::HStack;
    }
}
