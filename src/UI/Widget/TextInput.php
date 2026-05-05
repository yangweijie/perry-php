<?php

declare(strict_types=1);

namespace Perry\UI\Widget;

use Perry\UI\StateId;
use Perry\UI\Widget;
use Perry\UI\WidgetKind;

final class TextInput extends Widget
{
    public function __construct(
        private StateId $value,
        private string $placeholder = '',
    ) {
        parent::__construct();
    }

    public function kind(): WidgetKind
    {
        return WidgetKind::TextInput;
    }

    public function value(): StateId
    {
        return $this->value;
    }

    public function placeholder(): string
    {
        return $this->placeholder;
    }
}
