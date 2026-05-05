<?php

declare(strict_types=1);

namespace Perry\UI\Widget;

use Perry\UI\Widget;
use Perry\UI\WidgetKind;

final class Spacer extends Widget
{
    public function kind(): WidgetKind
    {
        return WidgetKind::Spacer;
    }
}
