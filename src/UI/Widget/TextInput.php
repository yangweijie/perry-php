<?php

declare(strict_types=1);

namespace Perry\UI\Widget;

use Perry\UI\Action;
use Perry\UI\StateId;
use Perry\UI\Widget;
use Perry\UI\WidgetKind;

final class TextInput extends Widget
{
    private ?Action $onChangeObj = null;

    public function __construct(
        private StateId $value,
        private string $placeholder = '',
        ?Action $onChange = null,
    ) {
        parent::__construct();
        if ($onChange instanceof Action) {
            $this->onChangeObj = $onChange;
        }
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

    public function getOnChange(): ?Action
    {
        return $this->onChangeObj;
    }
}
