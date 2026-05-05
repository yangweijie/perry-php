<?php

declare(strict_types=1);

namespace Perry\UI\Widget;

use Perry\UI\Binding;
use Perry\UI\Widget;
use Perry\UI\WidgetKind;

final class Text extends Widget
{
    private ?Binding $binding = null;

    public function __construct(
        private string|Binding $content,
    ) {
        parent::__construct();
        if ($content instanceof Binding) {
            $this->binding = $content;
        }
    }

    public function kind(): WidgetKind
    {
        return WidgetKind::Text;
    }

    public function content(): string
    {
        return $this->binding ? '' : (string) $this->content;
    }

    public function getBinding(): ?Binding
    {
        return $this->binding;
    }
}
