<?php

declare(strict_types=1);

namespace Perry\UI\Widget;

use Perry\UI\Action;
use Perry\UI\Binding;
use Perry\UI\StateId;
use Perry\UI\Widget;
use Perry\UI\WidgetKind;

final class TextInput extends Widget
{
    private ?Action $onChangeObj = null;
    private Binding $valueBinding;

    public function __construct(
        StateId|Binding $value,
        private string $placeholder = '',
        ?Action $onChange = null,
    ) {
        parent::__construct();
        if ($value instanceof Binding) {
            $this->valueBinding = $value;
        } else {
            $this->valueBinding = new Binding($value->name, '');
        }
        if ($onChange instanceof Action) {
            $this->onChangeObj = $onChange;
        }
    }

    public function kind(): WidgetKind
    {
        return WidgetKind::TextInput;
    }

    public function value(): Binding
    {
        return $this->valueBinding;
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
