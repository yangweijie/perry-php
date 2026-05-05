<?php

declare(strict_types=1);

namespace Perry\UI\Widget;

use Perry\UI\Binding;
use Perry\UI\Widget;
use Perry\UI\WidgetKind;

final class TextEditor extends Widget
{
    public function __construct(
        private Binding $binding,
        private string $placeholder = '',
    ) {
        parent::__construct();
    }

    public function kind(): WidgetKind
    {
        return WidgetKind::TextEditor;
    }

    public function getBinding(): Binding
    {
        return $this->binding;
    }

    public function placeholder(): string
    {
        return $this->placeholder;
    }
}
