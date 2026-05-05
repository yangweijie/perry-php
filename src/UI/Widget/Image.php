<?php

declare(strict_types=1);

namespace Perry\UI\Widget;

use Perry\UI\Widget;
use Perry\UI\WidgetKind;

final class Image extends Widget
{
    public function __construct(
        private string $source,
    ) {
        parent::__construct();
    }

    public function kind(): WidgetKind
    {
        return WidgetKind::Image;
    }

    public function source(): string
    {
        return $this->source;
    }
}
