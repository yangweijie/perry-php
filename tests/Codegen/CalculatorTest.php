<?php

test('calculator generates SwiftUI output on macOS', function () {
    $output = shell_exec('php examples/calculator.php macos 2>&1');
    
    expect($output)->toContain('import SwiftUI');
});

test('calculator generates HTML output for web', function () {
    $output = shell_exec('php examples/calculator.php web 2>&1');
    
    expect($output)->toContain('<!DOCTYPE html>')
        ->toContain('Perry');
});

test('all generators can instantiate', function () {
    $generators = [
        new \Perry\Generator\SwiftGenerator(),
        new \Perry\Generator\JavaScriptGenerator(),
        new \Perry\Generator\KotlinGenerator(),
    ];
    
    foreach ($generators as $gen) {
        expect($gen)->toBeInstanceOf(\Perry\IR\Generator::class);
    }
});

test('all backends can instantiate', function () {
    $factory = new \Perry\Codegen\CodegenFactory();
    $backends = $factory->available();
    
    expect($backends)->toContain('swiftui')
        ->toContain('html')
        ->toContain('android-xml')
        ->toContain('winui')
        ->toContain('gtk4');
});
