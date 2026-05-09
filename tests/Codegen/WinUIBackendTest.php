<?php

declare(strict_types=1);

use Perry\UI\Binding;
use Perry\UI\Styling\Style;
use Perry\Codegen\CodegenFactory;
use Perry\Build\Target;

test('CodegenFactory registers WinUIBackend', function () {
    $factory = new CodegenFactory();
    expect($factory->available())->toContain('winui');
});

test('WinUIBackend supports Windows target', function () {
    $b = (new CodegenFactory())->get('winui');
    expect($b->supports(Target::Windows))->toBeTrue();
});

test('WinUIBackend name is winui', function () {
    $b = (new CodegenFactory())->get('winui');
    expect($b->name())->toBe('winui');
});

test('WinUI generate produces XAML with namespaces', function () {
    $b = (new CodegenFactory())->get('winui');
    $out = $b->generate(new Perry\UI\Widget\Text('Hello'));
    expect($out)->toContain('<Window')
        ->and($out)->toContain('http://schemas.microsoft.com/winfx');
});

test('WinUI generates style attributes', function () {
    $b = (new CodegenFactory())->get('winui');
    $widget = (new Perry\UI\Widget\Text('Styled'))
        ->style(Style::make()->fontSize(20)->foregroundColor('#ff0000')->padding(12));
    $out = $b->generate($widget);
    expect($out)->toContain('FontSize')
        ->and($out)->toContain('Foreground')
        ->and($out)->toContain('Padding');
});

test('WinUI generates all widget types', function () {
    $b = (new CodegenFactory())->get('winui');

    $specs['Text'] = [new Perry\UI\Widget\Text('Hello'), ['TextBlock']];
    $specs['Button'] = [new Perry\UI\Widget\Button('Click'), ['Button']];
    $specs['Spacer'] = [new Perry\UI\Widget\Spacer(), ['Rectangle']];
    $specs['WebView'] = [new Perry\UI\Widget\WebView('<p>h</p>'), ['WebView2']];
    $specs['TextEditor'] = [new Perry\UI\Widget\TextEditor(new Binding('val', '')), ['TextBox']];
    $specs['Toggle'] = [new Perry\UI\Widget\Toggle('Dark'), ['CheckBox']];
    $specs['Slider'] = [new Perry\UI\Widget\Slider(new Binding('sv', 50.0)), ['Slider']];
    $specs['Image'] = [new Perry\UI\Widget\Image('photo.png'), ['Image']];

    foreach ($specs as $label => [$w, $kws]) {
        $out = $b->generate($w);
        expect(strlen($out))->toBeGreaterThan(0, "WinUI $label output is empty");
        foreach ($kws as $kw) {
            expect(str_contains($out, $kw))->toBeTrue("WinUI $label missing: $kw");
        }
    }
});
