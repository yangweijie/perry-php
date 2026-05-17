<?php

declare(strict_types=1);

namespace Perry\UI\Widget;

use Perry\UI\Binding;
use Perry\UI\Widget;
use Perry\UI\WidgetKind;

final class TabView extends Widget
{
    private array $tabs;
    private ?Binding $selected = null;
    /** @var array<int, string> */
    private array $labels = [];

    public function __construct(Widget ...$tabs)
    {
        parent::__construct();
        $this->tabs = $tabs;
        foreach ($tabs as $tab) {
            $this->addChild($tab);
        }
    }

    /**
     * Set a label for a tab at the given index.
     */
    public function label(int $index, string $label): static
    {
        $this->labels[$index] = $label;
        return $this;
    }

    /**
     * Set the selected tab index binding.
     */
    public function withSelected(?Binding $binding): static
    {
        $this->selected = $binding;
        return $this;
    }

    /**
     * Get the label for tab at index. Falls back to first Text child content.
     */
    public function getLabel(int $index): string
    {
        if (isset($this->labels[$index])) {
            return $this->labels[$index];
        }

        // Auto-extract label from first Text child of the tab content
        $child = $this->tabs[$index] ?? null;
        if ($child !== null) {
            foreach ($child->children() as $c) {
                if ($c instanceof Text) {
                    $cLabel = $c->content();
                    if ($cLabel !== '') {
                        return $cLabel;
                    }
                }
            }
        }

        return 'Tab ' . ($index + 1);
    }

    /**
     * @return string[]  All tab labels
     */
    public function getLabels(): array
    {
        $result = [];
        for ($i = 0; $i < count($this->tabs); $i++) {
            $result[] = $this->getLabel($i);
        }
        return $result;
    }

    /**
     * Get the content widget for a tab.
     */
    public function content(int $index): ?Widget
    {
        return $this->tabs[$index] ?? null;
    }

    public function tabs(): array
    {
        return $this->tabs;
    }

    public function tabsCount(): int
    {
        return count($this->tabs);
    }

    public function getSelected(): ?Binding
    {
        return $this->selected;
    }

    public function kind(): WidgetKind
    {
        return WidgetKind::TabView;
    }
}
