<?php

declare(strict_types=1);

use Perry\UI\Binding;
use Perry\UI\StateId;
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
    $specs['VStack'] = [new Perry\UI\Widget\VStack(new Perry\UI\Widget\Text('A')), ['GtkBox', 'vertical']];
    $specs['HStack'] = [new Perry\UI\Widget\HStack(new Perry\UI\Widget\Text('B')), ['GtkBox', 'horizontal']];
    $specs['ScrollView'] = [new Perry\UI\Widget\ScrollView(new Perry\UI\Widget\Text('S')), ['GtkScrolledWindow']];
    $specs['TextInput'] = [new Perry\UI\Widget\TextInput(StateId::next(), 'Enter...'), ['GtkEntry']];
    $specs['ListWidget'] = [new Perry\UI\Widget\ListWidget(new Perry\UI\Widget\Text('item')), ['GtkBox']];
    $specs['NavigationView'] = [new Perry\UI\Widget\NavigationView(new Perry\UI\Widget\Text('screen')), ['GtkStack']];
    $specs['TabView'] = [new Perry\UI\Widget\TabView(new Perry\UI\Widget\Text('tab')), ['GtkNotebook']];

    foreach ($specs as $label => [$w, $kws]) {
        $out = $b->generate($w);
        expect(strlen($out))->toBeGreaterThan(0, "Gtk4 $label output is empty");
        foreach ($kws as $kw) {
            expect(str_contains($out, $kw))->toBeTrue("Gtk4 $label missing: $kw");
        }
    }
});

test('Gtk4 generates Closure action with C code', function () {
    $b = (new CodegenFactory())->get('gtk4');
    $action = Perry\UI\Action::fromClosure(function() {
        $x = 42;
        g_print("Hello from closure: %d\n", $x);
    });
    $button = new Perry\UI\Widget\Button('Click', $action);
    $ui = $b->generate($button);
    // The handler is generated in generateMainActivity
    $cCode = $b->generateMainActivity('test');
    expect($cCode)->toContain('g_print')
        ->and($cCode)->toContain('Hello from closure')
        ->and($cCode)->toContain('42');
});

test('Gtk4 Closure action with bindings', function () {
    $b = (new CodegenFactory())->get('gtk4');
    $action = Perry\UI\Action::fromClosure(function($count) {
        g_print("Count: %d\n", $count);
    }, ['count' => 100]);
    $button = new Perry\UI\Widget\Button('Click', $action);
    $ui = $b->generate($button);
    // The handler is generated in generateMainActivity
    $cCode = $b->generateMainActivity('test');
    expect($cCode)->toContain('g_print')
        ->and($cCode)->toContain('Count:')
        ->and($cCode)->toContain('100');
});
