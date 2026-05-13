<?php

declare(strict_types=1);

namespace Perry\UI\Widget;

use Perry\UI\Binding;
use Perry\UI\Widget;
use Perry\UI\WidgetKind;

final class Progress extends Widget
{
    private ?Binding $progressObj = null;

    public function __construct(
        ?Binding $progress = null,
    ) {
        parent::__construct();
        $this->progressObj = $progress;
    }

    public function kind(): WidgetKind
    {
        return WidgetKind::Progress;
    }

    public function getProgress(): ?Binding
    {
        return $this->progressObj;
    }
}
