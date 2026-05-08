<?php

declare(strict_types=1);

use Perry\Build\Target;

// ============================================================
// Enum values & display names
// ============================================================

test('Target has all 15 expected cases', function () {
    $cases = [];
    foreach (Target::cases() as $case) {
        $cases[] = $case->value;
    }

    expect($cases)->toContain(
        'macos', 'ios', 'ios-simulator', 'tvos', 'visionos', 'watchos',
        'flutter', 'harmonyos', 'glance', 'wear-tiles',
        'android', 'gtk4-linux', 'windows', 'web', 'wasm',
    );
    expect($cases)->toHaveCount(15);
});

test('displayName returns human-readable names', function () {
    expect(Target::MacOs->displayName())->toBe('macOS');
    expect(Target::IOS->displayName())->toBe('iOS');
    expect(Target::IOSSimulator->displayName())->toBe('iOS Simulator');
    expect(Target::TvOs->displayName())->toBe('tvOS');
    expect(Target::VisionOs->displayName())->toBe('visionOS');
    expect(Target::WatchOs->displayName())->toBe('watchOS');
    expect(Target::Flutter->displayName())->toBe('Flutter');
    expect(Target::HarmonyOS->displayName())->toBe('HarmonyOS');
    expect(Target::Glance->displayName())->toBe('Glance');
    expect(Target::WearTiles->displayName())->toBe('Wear Tiles');
    expect(Target::Android->displayName())->toBe('Android');
    expect(Target::Gtk4Linux->displayName())->toBe('GTK4/Linux');
    expect(Target::Windows->displayName())->toBe('Windows');
    expect(Target::Web->displayName())->toBe('Web');
    expect(Target::Wasm->displayName())->toBe('WebAssembly');
});

// ============================================================
// isApple / isMobile
// ============================================================

test('isApple returns true for Apple platforms', function () {
    expect(Target::MacOs->isApple())->toBeTrue();
    expect(Target::IOS->isApple())->toBeTrue();
    expect(Target::IOSSimulator->isApple())->toBeTrue();
    expect(Target::TvOs->isApple())->toBeTrue();
    expect(Target::VisionOs->isApple())->toBeTrue();
    expect(Target::WatchOs->isApple())->toBeTrue();
});

test('isApple returns false for non-Apple platforms', function () {
    expect(Target::Flutter->isApple())->toBeFalse();
    expect(Target::Glance->isApple())->toBeFalse();
    expect(Target::WearTiles->isApple())->toBeFalse();
    expect(Target::HarmonyOS->isApple())->toBeFalse();
    expect(Target::Android->isApple())->toBeFalse();
    expect(Target::Gtk4Linux->isApple())->toBeFalse();
    expect(Target::Windows->isApple())->toBeFalse();
    expect(Target::Web->isApple())->toBeFalse();
    expect(Target::Wasm->isApple())->toBeFalse();
});

test('isMobile returns true for mobile platforms', function () {
    expect(Target::IOS->isMobile())->toBeTrue();
    expect(Target::IOSSimulator->isMobile())->toBeTrue();
    expect(Target::Glance->isMobile())->toBeTrue();
    expect(Target::WearTiles->isMobile())->toBeTrue();
    expect(Target::HarmonyOS->isMobile())->toBeTrue();
    expect(Target::Android->isMobile())->toBeTrue();
    expect(Target::WatchOs->isMobile())->toBeTrue();
});

test('isMobile returns false for desktop/other', function () {
    expect(Target::MacOs->isMobile())->toBeFalse();
    expect(Target::TvOs->isMobile())->toBeFalse();
    expect(Target::VisionOs->isMobile())->toBeFalse();
    expect(Target::Gtk4Linux->isMobile())->toBeFalse();
    expect(Target::Windows->isMobile())->toBeFalse();
    expect(Target::Web->isMobile())->toBeFalse();
    expect(Target::Wasm->isMobile())->toBeFalse();
});

// ============================================================
// staticLibName
// ============================================================

test('staticLibName returns correct filenames per target', function () {
    expect(Target::MacOs->staticLibName())->toBe('libperry_darwin.a');
    expect(Target::IOS->staticLibName())->toBe('libperry_ios.a');
    expect(Target::IOSSimulator->staticLibName())->toBe('libperry_ios.a');
    expect(Target::TvOs->staticLibName())->toBe('libperry_tvos.a');
    expect(Target::VisionOs->staticLibName())->toBe('libperry_visionos.a');
    expect(Target::WatchOs->staticLibName())->toBe('libperry_watchos.a');
    expect(Target::Flutter->staticLibName())->toBe('libperry_flutter.a');
    expect(Target::Glance->staticLibName())->toBe('libperry_glance.a');
    expect(Target::WearTiles->staticLibName())->toBe('libperry_wear_tiles.a');
    expect(Target::HarmonyOS->staticLibName())->toBe('libperry_harmonyos.a');
    expect(Target::Android->staticLibName())->toBe('libperry_android.a');
    expect(Target::Gtk4Linux->staticLibName())->toBe('libperry_linux.a');
    expect(Target::Windows->staticLibName())->toBe('perry_windows.lib');
    expect(Target::Web->staticLibName())->toBe('libperry_wasm.a');
    expect(Target::Wasm->staticLibName())->toBe('libperry_wasm.a');
});

// ============================================================
// fromString
// ============================================================

test('fromString resolves standard target names', function () {
    expect(Target::fromString('macos'))->toBe(Target::MacOs);
    expect(Target::fromString('ios'))->toBe(Target::IOS);
    expect(Target::fromString('ios-simulator'))->toBe(Target::IOSSimulator);
    expect(Target::fromString('tvos'))->toBe(Target::TvOs);
    expect(Target::fromString('visionos'))->toBe(Target::VisionOs);
    expect(Target::fromString('watchos'))->toBe(Target::WatchOs);
    expect(Target::fromString('flutter'))->toBe(Target::Flutter);
    expect(Target::fromString('harmonyos'))->toBe(Target::HarmonyOS);
    expect(Target::fromString('glance'))->toBe(Target::Glance);
    expect(Target::fromString('wear-tiles'))->toBe(Target::WearTiles);
    expect(Target::fromString('android'))->toBe(Target::Android);
    expect(Target::fromString('gtk4-linux'))->toBe(Target::Gtk4Linux);
    expect(Target::fromString('windows'))->toBe(Target::Windows);
    expect(Target::fromString('web'))->toBe(Target::Web);
    expect(Target::fromString('wasm'))->toBe(Target::Wasm);
});

test('fromString resolves common aliases', function () {
    expect(Target::fromString('swiftui'))->toBe(Target::MacOs);
    expect(Target::fromString('swift'))->toBe(Target::MacOs);
    expect(Target::fromString('mac'))->toBe(Target::MacOs);
    expect(Target::fromString('gtk4'))->toBe(Target::Gtk4Linux);
    expect(Target::fromString('gtk'))->toBe(Target::Gtk4Linux);
    expect(Target::fromString('linux'))->toBe(Target::Gtk4Linux);
    expect(Target::fromString('win'))->toBe(Target::Windows);
    expect(Target::fromString('arkts'))->toBe(Target::HarmonyOS);
    expect(Target::fromString('harmony'))->toBe(Target::HarmonyOS);
    expect(Target::fromString('compose'))->toBe(Target::Android);
});

test('fromString throws for unknown targets', function () {
    expect(fn () => Target::fromString('nonexistent'))
        ->toThrow(\InvalidArgumentException::class);
    expect(fn () => Target::fromString(''))
        ->toThrow(\InvalidArgumentException::class);
});

// ============================================================
// autoDetect (runs on the current system)
// ============================================================

test('autoDetect returns a valid target', function () {
    $target = Target::autoDetect();
    expect($target)->toBeInstanceOf(Target::class);
});
