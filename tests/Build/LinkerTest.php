<?php

declare(strict_types=1);

use Perry\Build\Linker;
use Perry\Build\Target;

// ============================================================
// buildLinkCommand — macOS
// ============================================================

test('linker command for macOS includes Cocoa and CoreGraphics frameworks', function () {
    $linker = new Linker();
    $cmd = $linker->buildLinkCommand(Target::MacOs, '/tmp/test.o', '/tmp/output');

    expect($cmd)->toContain('-o', '/tmp/output', '/tmp/test.o');
    expect($cmd)->toContain('-framework', 'Cocoa');
    expect($cmd)->toContain('-framework', 'CoreGraphics');
    expect($cmd)->toContain('-framework', 'Foundation');
});

// ============================================================
// buildLinkCommand — iOS
// ============================================================

test('linker command for iOS includes UIKit frameworks', function () {
    $linker = new Linker();
    $cmd = $linker->buildLinkCommand(Target::IOS, '/tmp/test.o', '/tmp/output');

    expect($cmd)->toContain('-framework', 'UIKit');
    expect($cmd)->toContain('-framework', 'CoreGraphics');
    expect($cmd)->not->toContain('-framework', 'Cocoa');
});

// ============================================================
// buildLinkCommand — tvOS
// ============================================================

test('linker command for tvOS includes TVUIKit', function () {
    $linker = new Linker();
    $cmd = $linker->buildLinkCommand(Target::TvOs, '/tmp/test.o', '/tmp/output');

    expect($cmd)->toContain('-framework', 'TVUIKit');
    expect($cmd)->toContain('-framework', 'UIKit');
});

// ============================================================
// buildLinkCommand — visionOS
// ============================================================

test('linker command for visionOS includes RealityKit and SwiftUI', function () {
    $linker = new Linker();
    $cmd = $linker->buildLinkCommand(Target::VisionOs, '/tmp/test.o', '/tmp/output');

    expect($cmd)->toContain('-framework', 'RealityKit');
    expect($cmd)->toContain('-framework', 'SwiftUI');
});

// ============================================================
// buildLinkCommand — watchOS
// ============================================================

test('linker command for watchOS includes WatchKit', function () {
    $linker = new Linker();
    $cmd = $linker->buildLinkCommand(Target::WatchOs, '/tmp/test.o', '/tmp/output');

    expect($cmd)->toContain('-framework', 'WatchKit');
});

// ============================================================
// buildLinkCommand — GTK4
// ============================================================

test('linker command for GTK4 uses cc with pkg-config', function () {
    $linker = new Linker();
    $cmd = $linker->buildLinkCommand(Target::Gtk4Linux, '/tmp/test.o', '/tmp/output');

    expect($cmd[0])->toBe('cc');
    expect($cmd)->toContain('-o');
    expect($cmd)->toContain('/tmp/output');
    expect($cmd)->toContain('/tmp/test.o');
});

// ============================================================
// buildLinkCommand — Android
// ============================================================

test('linker command for Android includes android and log libs', function () {
    $linker = new Linker();
    $cmd = $linker->buildLinkCommand(Target::Android, '/tmp/test.o', '/tmp/output');

    expect($cmd)->toContain('-landroid');
    expect($cmd)->toContain('-llog');
});

// ============================================================
// buildLinkCommand — Web/Wasm
// ============================================================

test('linker command for Web returns just linker + object + output', function () {
    $linker = new Linker();
    $cmd = $linker->buildLinkCommand(Target::Web, '/tmp/test.o', '/tmp/output');

    expect($cmd)->toContain('cc');
    expect($cmd)->toContain('-o');
    expect($cmd)->toContain('/tmp/output');
    expect($cmd)->toContain('/tmp/test.o');
});

test('linker command for Wasm returns just linker + object + output', function () {
    $linker = new Linker();
    $cmd = $linker->buildLinkCommand(Target::Wasm, '/tmp/test.o', '/tmp/output');

    expect($cmd)->toContain('cc');
    expect($cmd)->toContain('-o');
    expect($cmd)->toContain('/tmp/output');
    expect($cmd)->toContain('/tmp/test.o');
});

test('linker command for HarmonyOS uses cc with no extra flags', function () {
    $linker = new Linker();
    $cmd = $linker->buildLinkCommand(Target::HarmonyOS, '/tmp/test.o', '/tmp/output');

    expect($cmd[0])->toBe('cc');
    expect($cmd)->toContain('-o');
    expect($cmd)->toContain('/tmp/output');
    expect($cmd)->toContain('/tmp/test.o');
    // No framework flags
    expect($cmd)->not->toContain('-framework');
    expect($cmd)->not->toContain('-landroid');
});

// ============================================================
// link() — returns bool (no real toolchain needed for test)
// ============================================================

test('link returns false for nonexistent object file', function () {
    $linker = new Linker();
    $result = $linker->link(Target::MacOs, '/nonexistent/test.o', '/tmp/output');

    expect($result)->toBeFalse();
});
