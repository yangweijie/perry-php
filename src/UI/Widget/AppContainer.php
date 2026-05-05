<?php

declare(strict_types=1);

namespace Perry\UI\Widget;

use Perry\UI\Binding;
use Perry\UI\Widget;
use Perry\UI\WidgetKind;

final class AppContainer extends Widget
{
    /** @var Binding[] */
    private array $bindings = [];

    public function __construct(
        private Widget $content,
        private ?int $windowWidth = null,
        private ?int $windowHeight = null,
        Binding ...$extraBindings,
    ) {
        parent::__construct();
        $this->collectBindings($content);
        foreach ($extraBindings as $b) {
            $this->bindings[$b->name] = $b;
        }
    }

    public function kind(): WidgetKind
    {
        return WidgetKind::VStack;
    }

    /** @return Binding[] */
    public function bindings(): array
    {
        return $this->bindings;
    }

    public function content(): Widget
    {
        return $this->content;
    }

    public function windowWidth(): ?int
    {
        return $this->windowWidth;
    }

    public function windowHeight(): ?int
    {
        return $this->windowHeight;
    }

    private function collectBindings(Widget $widget): void
    {
        if ($widget instanceof Text && $widget->getBinding()) {
            $this->bindings[$widget->getBinding()->name] = $widget->getBinding();
        }

        foreach ($widget->children() as $child) {
            $this->collectBindings($child);
        }
    }
}
