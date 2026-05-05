<?php

declare(strict_types=1);

namespace Perry\UI\Platform;

use Perry\Build\Target;

final class DriverFactory
{
    public static function forTarget(Target $target): PlatformDriver
    {
        return match ($target) {
            Target::MacOs => new MacOsDriver(),
            Target::IOS, Target::IOSSimulator => new IosDriver(),
            Target::Android => new AndroidDriver(),
            Target::Gtk4Linux => new Gtk4Driver(),
            Target::Windows => new WindowsDriver(),
            Target::Web, Target::Wasm => new WebDriver(),
            Target::TvOs => new class extends AbstractPlatformDriver {
                public function name(): string { return 'tvos'; }
                protected function createText(\Perry\UI\Widget $w): array { return ['type' => 'UILabel', 'text' => $w->content()]; }
                protected function createButton(\Perry\UI\Widget $w): array { return ['type' => 'UIButton', 'title' => $w->label()]; }
                protected function createVStack(\Perry\UI\Widget $w): array { return ['type' => 'UIStackView', 'axis' => 'vertical']; }
                protected function createHStack(\Perry\UI\Widget $w): array { return ['type' => 'UIStackView', 'axis' => 'horizontal']; }
                protected function createSpacer(\Perry\UI\Widget $w): array { return ['type' => 'UIView']; }
                protected function createImage(\Perry\UI\Widget $w): array { return ['type' => 'UIImageView', 'source' => $w->source()]; }
                protected function createScrollView(\Perry\UI\Widget $w): array { return ['type' => 'UIScrollView']; }
                protected function createTextInput(\Perry\UI\Widget $w): array { return ['type' => 'UITextField', 'placeholder' => $w->placeholder()]; }
                protected function createToggle(\Perry\UI\Widget $w): array { return ['type' => 'UISwitch', 'label' => $w->label()]; }
                protected function applyStyleProperty(mixed $n, string $p, mixed $v): void { $n["style_{$p}"] = $v; }
                public function setBody(\Perry\UI\WidgetHandle $r): void {}
                public function run(): void { fwrite(STDERR, "[tvOS] Running native app...\n"); }
                public function quit(): void {}
            },
            Target::VisionOs => new class extends AbstractPlatformDriver {
                public function name(): string { return 'visionos'; }
                protected function createText(\Perry\UI\Widget $w): array { return ['type' => 'Text', 'content' => $w->content()]; }
                protected function createButton(\Perry\UI\Widget $w): array { return ['type' => 'Button', 'title' => $w->label()]; }
                protected function createVStack(\Perry\UI\Widget $w): array { return ['type' => 'VStack']; }
                protected function createHStack(\Perry\UI\Widget $w): array { return ['type' => 'HStack']; }
                protected function createSpacer(\Perry\UI\Widget $w): array { return ['type' => 'Spacer']; }
                protected function createImage(\Perry\UI\Widget $w): array { return ['type' => 'Image', 'source' => $w->source()]; }
                protected function createScrollView(\Perry\UI\Widget $w): array { return ['type' => 'ScrollView']; }
                protected function createTextInput(\Perry\UI\Widget $w): array { return ['type' => 'TextField', 'placeholder' => $w->placeholder()]; }
                protected function createToggle(\Perry\UI\Widget $w): array { return ['type' => 'Toggle', 'label' => $w->label()]; }
                protected function applyStyleProperty(mixed $n, string $p, mixed $v): void { $n["style_{$p}"] = $v; }
                public function setBody(\Perry\UI\WidgetHandle $r): void {}
                public function run(): void { fwrite(STDERR, "[visionOS] Running native app...\n"); }
                public function quit(): void {}
            },
            Target::WatchOs => new class extends AbstractPlatformDriver {
                public function name(): string { return 'watchos'; }
                protected function createText(\Perry\UI\Widget $w): array { return ['type' => 'Text', 'content' => $w->content()]; }
                protected function createButton(\Perry\UI\Widget $w): array { return ['type' => 'Button', 'title' => $w->label()]; }
                protected function createVStack(\Perry\UI\Widget $w): array { return ['type' => 'VStack']; }
                protected function createHStack(\Perry\UI\Widget $w): array { return ['type' => 'HStack']; }
                protected function createSpacer(\Perry\UI\Widget $w): array { return ['type' => 'Spacer']; }
                protected function createImage(\Perry\UI\Widget $w): array { return ['type' => 'Image', 'source' => $w->source()]; }
                protected function createScrollView(\Perry\UI\Widget $w): array { return ['type' => 'ScrollView']; }
                protected function createTextInput(\Perry\UI\Widget $w): array { return ['type' => 'TextField', 'placeholder' => $w->placeholder()]; }
                protected function createToggle(\Perry\UI\Widget $w): array { return ['type' => 'Toggle', 'label' => $w->label()]; }
                protected function applyStyleProperty(mixed $n, string $p, mixed $v): void { $n["style_{$p}"] = $v; }
                public function setBody(\Perry\UI\WidgetHandle $r): void {}
                public function run(): void { fwrite(STDERR, "[watchOS] Running native app...\n"); }
                public function quit(): void {}
            },
        };
    }

    public static function autoDetect(): PlatformDriver
    {
        return self::forTarget(Target::autoDetect());
    }
}
