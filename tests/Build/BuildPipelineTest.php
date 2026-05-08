<?php

declare(strict_types=1);

use Perry\Build\BuildPipeline;
use Perry\Build\Target;

test('build pipeline constructor sets target from autoDetect', function () {
    $pipeline = new BuildPipeline();
    expect($pipeline->target())->toBe(Target::autoDetect());
});

test('build pipeline constructor accepts explicit target', function () {
    $pipeline = new BuildPipeline(Target::Gtk4Linux);
    expect($pipeline->target())->toBe(Target::Gtk4Linux);
});

test('build pipeline provides platform driver', function () {
    $pipeline = new BuildPipeline(Target::Web);
    expect($pipeline->driver())->toBeInstanceOf(\Perry\UI\Platform\PlatformDriver::class);
});

test('build pipeline getInfo returns structured metadata', function () {
    $pipeline = new BuildPipeline(Target::Web);
    $info = $pipeline->getInfo();

    expect($info)->toHaveKeys(['target', 'display_name', 'driver', 'libraries', 'linker', 'sdk_path']);
    expect($info['target'])->toBe('web');
    expect($info['display_name'])->toBe('Web');
    expect($info['libraries'])->toHaveKeys(['runtime', 'stdlib', 'ui']);
});

test('build pipeline getLibraries returns library map', function () {
    $pipeline = new BuildPipeline(Target::Gtk4Linux);
    $libs = $pipeline->getLibraries();

    expect($libs)->toHaveKeys(['runtime', 'stdlib', 'ui']);
});

test('build pipeline getLinkerCommand builds correct structure', function () {
    $pipeline = new BuildPipeline(Target::MacOs);
    $cmd = $pipeline->getLinkerCommand('/tmp/test.o', '/tmp/output');

    expect($cmd)->toBeArray();
    expect($cmd)->toContain('-o');
    expect($cmd)->toContain('/tmp/output');
});

test('build pipeline compile returns false for nonexistent source', function () {
    $pipeline = new BuildPipeline(Target::Web);
    $result = $pipeline->compile('/nonexistent/source.c', '/tmp/output');

    expect($result)->toBeFalse();
});

test('build pipeline driver name matches target', function () {
    $pipeline = new BuildPipeline(Target::Android);
    expect($pipeline->driver()->name())->toBe('android');

    $pipeline2 = new BuildPipeline(Target::MacOs);
    expect($pipeline2->driver()->name())->toBe('macos');
});

test('build pipeline generateOnly produces valid HTML for Web target', function () {
    $pipeline = new BuildPipeline(Target::Web);
    $root = new \Perry\UI\Widget\Text('Hello');

    $outputFile = sys_get_temp_dir() . '/perry_test_build_' . uniqid() . '.html';
    $result = $pipeline->generateOnly($root, $outputFile);

    expect($result->success)->toBeTrue();
    expect($result->outputFile)->toBe($outputFile);
    expect(file_get_contents($outputFile))->toContain('Hello');
    unlink($outputFile);
});

test('build pipeline generateSource returns source string without writing to disk', function () {
    $pipeline = new BuildPipeline(Target::Web);
    $root = new \Perry\UI\Widget\Text('Hello');

    $source = $pipeline->generateSource($root);
    expect($source)->toBeString();
    expect($source)->toContain('Hello');
});

test('build pipeline generateOnly creates nested directories for output', function () {
    $pipeline = new BuildPipeline(Target::Web);
    $root = new \Perry\UI\Widget\Text('Dir test');

    $tmpDir = sys_get_temp_dir() . '/perry_test_nested_' . uniqid();
    $outputFile = $tmpDir . '/sub/dir/test.html';
    $result = $pipeline->generateOnly($root, $outputFile);

    expect($result->success)->toBeTrue();
    expect(is_dir($tmpDir . '/sub/dir'))->toBeTrue();
    expect(file_exists($outputFile))->toBeTrue();
    unlink($outputFile);
    rmdir($tmpDir . '/sub/dir');
    rmdir($tmpDir . '/sub');
    rmdir($tmpDir);
});
