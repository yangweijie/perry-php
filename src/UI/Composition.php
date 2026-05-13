<?php

declare(strict_types=1);

namespace Perry\UI;

use Perry\UI\Styling\Style;

abstract class Composition extends Widget
{
    protected array $state = [];
    protected array $hooks = [];

    public function __construct()
    {
        parent::__construct();
    }

    abstract protected function render(Style $style): Widget;

    public function getState(string $key, mixed $default = null): mixed
    {
        return $this->state[$key] ?? $default;
    }

    public function setState(string $key, mixed $value): void
    {
        $this->state[$key] = $value;
    }

    public function getHook(string $key): mixed
    {
        return $this->hooks[$key] ?? null;
    }

    public function setHook(string $key, mixed $value): void
    {
        $this->hooks[$key] = $value;
    }

    public function onMount(): void {}

    public function onUpdate(): void {}

    public function onUnmount(): void {}

    public function toWidget(): Widget
    {
        $this->onMount();
        return $this->render($this->style ?? Style::make());
    }
}