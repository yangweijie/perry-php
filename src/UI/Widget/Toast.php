<?php

declare(strict_types=1);

namespace Perry\UI\Widget;

use Perry\UI\Widget;
use Perry\UI\WidgetKind;

final class Toast extends Widget
{
    public function __construct(
        private string $message,
    ) {
        parent::__construct();
    }

    public function kind(): WidgetKind
    {
        return WidgetKind::Toast;
    }

    public function message(): string
    {
        return $this->message;
    }
}
