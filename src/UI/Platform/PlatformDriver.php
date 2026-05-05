<?php

declare(strict_types=1);

namespace Perry\UI\Platform;

use Perry\UI\Styling\Style;
use Perry\UI\Widget;
use Perry\UI\WidgetHandle;

interface PlatformDriver
{
    public function name(): string;

    public function createWidget(Widget $widget): WidgetHandle;

    public function destroyWidget(WidgetHandle $handle): void;

    public function applyStyle(WidgetHandle $handle, Style $style): void;

    public function addChild(WidgetHandle $parent, WidgetHandle $child): void;

    public function setBody(WidgetHandle $root): void;

    public function run(): void;

    public function quit(): void;
}
