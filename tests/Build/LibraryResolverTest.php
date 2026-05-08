<?php

declare(strict_types=1);

use Perry\Build\LibraryResolver;
use Perry\Build\Target;

// ============================================================
// Library resolution
// ============================================================

test('resolver returns array with expected keys', function () {
    $resolver = new LibraryResolver('/nonexistent/lib');
    $libs = $resolver->resolve(Target::MacOs);

    expect($libs)->toHaveKeys(['runtime', 'stdlib', 'ui']);
});

test('resolver returns nulls for nonexistent libraries', function () {
    $resolver = new LibraryResolver('/nonexistent/lib');
    $libs = $resolver->resolve(Target::MacOs);

    expect($libs['runtime'])->toBeNull();
    expect($libs['stdlib'])->toBeNull();
    expect($libs['ui'])->toBeNull();
});

test('resolver finds library when file exists', function () {
    $tempDir = sys_get_temp_dir() . '/perry_test_' . uniqid();
    mkdir($tempDir, 0755, true);

    $libPath = $tempDir . '/libperry_runtime_macos.a';
    touch($libPath);

    $resolver = new LibraryResolver($tempDir);
    $libs = $resolver->resolve(Target::MacOs);

    expect($libs['runtime'])->toBe(realpath($libPath));

    // cleanup
    unlink($libPath);
    rmdir($tempDir);
});

// ============================================================
// findLinker
// ============================================================

test('findLinker returns cc for non-Apple, non-Windows', function () {
    $resolver = new LibraryResolver();
    expect($resolver->findLinker(Target::Gtk4Linux))->toBe('cc');
    expect($resolver->findLinker(Target::Android))->toBe('cc');
    expect($resolver->findLinker(Target::Web))->toBe('cc');
    expect($resolver->findLinker(Target::Wasm))->toBe('cc');
});

test('findLinker returns cl.exe for Windows', function () {
    $resolver = new LibraryResolver();
    expect($resolver->findLinker(Target::Windows))->toBe('cl.exe');
});

test('findLinker returns clang for Apple on macOS', function () {
    $resolver = new LibraryResolver();
    $linker = $resolver->findLinker(Target::MacOs);

    // Should be either xcrun result (if available) or 'clang'
    expect($linker)->not->toBeEmpty();
    expect($linker)->toContain('clang');
});

// ============================================================
// findSdkPath
// ============================================================

test('findSdkPath returns null for non-Apple targets', function () {
    $resolver = new LibraryResolver();
    expect($resolver->findSdkPath(Target::Gtk4Linux))->toBeNull();
    expect($resolver->findSdkPath(Target::Windows))->toBeNull();
    expect($resolver->findSdkPath(Target::Android))->toBeNull();
    expect($resolver->findSdkPath(Target::Web))->toBeNull();
    expect($resolver->findSdkPath(Target::Wasm))->toBeNull();
});

test('findSdkPath returns non-null for Apple targets on macOS', function () {
    $resolver = new LibraryResolver();
    $sdkPath = $resolver->findSdkPath(Target::MacOs);

    // On Darwin this should find the macOS SDK
    if (PHP_OS_FAMILY === 'Darwin') {
        expect($sdkPath)->not->toBeNull();
        expect($sdkPath)->toContain('MacOSX');
    }
});

// ============================================================
// Custom lib directory
// ============================================================

test('resolver respects custom lib directory', function () {
    $resolver = new LibraryResolver('/custom/lib/path');
    $libs = $resolver->resolve(Target::Windows);

    expect($libs['runtime'])->toBeNull(); // doesn't exist at custom path
});
