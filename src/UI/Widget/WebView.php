<?php

declare(strict_types=1);

namespace Perry\UI\Widget;

use Perry\UI\Widget;
use Perry\UI\WidgetKind;

final class WebView extends Widget
{
    private string $html;

    public function __construct(string $html)
    {
        parent::__construct();
        $this->html = $html;
    }

    public function kind(): WidgetKind
    {
        return WidgetKind::WebView;
    }

    public function html(): string
    {
        return $this->html;
    }
}
