<?php

declare(strict_types=1);

namespace Perry\UI\Widget;

use Perry\UI\Action;
use Perry\UI\Binding;
use Perry\UI\Widget;
use Perry\UI\WidgetKind;

final class SegmentedControl extends Widget
{
    private ?Binding $selectedValueObj = null;
    private ?Action $onChangeObj = null;

    /** @var array<string, string> */
    private array $options;

    /**
     * @param array<string, string> $options label => value
     */
    public function __construct(
        array $options,
        ?Binding $selectedValue = null,
        ?Action $onChange = null,
    ) {
        parent::__construct();
        $this->options = $options;
        $this->selectedValueObj = $selectedValue;
        if ($onChange instanceof Action) {
            $this->onChangeObj = $onChange;
        }
    }

    public function kind(): WidgetKind
    {
        return WidgetKind::SegmentedControl;
    }

    /** @return array<string, string> */
    public function options(): array
    {
        return $this->options;
    }

    public function getSelectedValue(): ?Binding
    {
        return $this->selectedValueObj;
    }

    public function getOnChange(): ?Action
    {
        return $this->onChangeObj;
    }
}
