<?php

declare(strict_types=1);

use Perry\UI\Binding;
use Perry\UI\Styling\Style;
use Perry\Codegen\CodegenFactory;
use Perry\Build\Target;

test('CodegenFactory registers Gtk4Backend', function () {
    $factory = new CodegenFactory();
    expect($factory->available())->toContain('gtk4');
});

test('Gtk4Backend supports Gtk4Linux target', function () {
    $b = (new CodegenFactory())->get('gtk4');
    expect($b->supports(Target::Gtk4Linux))->toBeTrue();
});

test('Gtk4Backend name is gtk4', function () {
    $b = (new CodegenFactory())->get('gtk4');
    expect($b->name())->toBe('gtk4');
});

test('Gtk4 generate produces XML interface with Gtk widgets', function () {
    $b = (new CodegenFactory())->get('gtk4');
    $out = $b->generate(new Perry\UI\Widget\Text('Hello'));
    expect($out)->toContain('<?xml')
        ->and($out)->toContain('gtk')
        ->and($out)->toContain('GtkLabel');
});

test('Gtk4 generates style properties as CSS', function () {
    $b = (new CodegenFactory())->get('gtk4');
    $widget = (new Perry\UI\Widget\Text('Styled'))
        ->style(Style::make()->fontSize(20)->foregroundColor('#ff0000'));
    $out = $b->generate($widget);
    expect($out)->toContain('font-size')
        ->and($out)->toContain('color');
});

test('Gtk4 generates all widget types', function () {
    $b = (new CodegenFactory())->get('gtk4');

    $specs['Text'] = [new Perry\UI\Widget\Text('Hello'), ['GtkLabel']];
    $specs['Button'] = [new Perry\UI\Widget\Button('Click'), ['GtkButton']];
    $specs['Spacer'] = [new Perry\UI\Widget\Spacer(), ['GtkSeparator']];
    $specs['WebView'] = [new Perry\UI\Widget\WebView('<p>h</p>'), ['GtkWebView']];
    $specs['TextEditor'] = [new Perry\UI\Widget\TextEditor(new Binding('val', '')), ['GtkTextView']];
    $specs['Toggle'] = [new Perry\UI\Widget\Toggle('Dark'), ['GtkCheckButton']];
    $specs['Slider'] = [new Perry\UI\Widget\Slider(new Binding('sv', 50.0)), ['GtkScale']];
    $specs['Image'] = [new Perry\UI\Widget\Image('photo.png'), ['GtkImage']];

    foreach ($specs as $label => [$w, $kws]) {
        $out = $b->generate($w);
        expect(strlen($out))->toBeGreaterThan(0, "Gtk4 $label output is empty");
        foreach ($kws as $kw) {
            expect(str_contains($out, $kw))->toBeTrue("Gtk4 $label missing: $kw");
        }
    }
});
