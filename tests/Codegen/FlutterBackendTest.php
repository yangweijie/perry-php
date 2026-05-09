<?php

declare(strict_types=1);

use Perry\UI\Binding;
use Perry\UI\StateId;
use Perry\UI\Styling\Style;
use Perry\Codegen\CodegenFactory;
use Perry\Build\Target;

test('CodegenFactory registers FlutterBackend', function () {
    $factory = new CodegenFactory();
    expect($factory->available())->toContain('flutter');
});

test('FlutterBackend supports Flutter target', function () {
    $b = (new CodegenFactory())->get('flutter');
    expect($b->supports(Target::Flutter))->toBeTrue();
});

test('FlutterBackend name is flutter', function () {
    $b = (new CodegenFactory())->get('flutter');
    expect($b->name())->toBe('flutter');
});

test('Flutter generate produces Dart widget tree', function () {
    $b = (new CodegenFactory())->get('flutter');
    $out = $b->generate(new Perry\UI\Widget\Text('Hello'));
    expect($out)->toContain('MaterialApp')
        ->and($out)->toContain('Scaffold')
        ->and($out)->toContain('Text(');
});

test('Flutter generates style properties', function () {
    $b = (new CodegenFactory())->get('flutter');
    $widget = (new Perry\UI\Widget\Text('Styled'))
        ->style(Style::make()->fontSize(20)->foregroundColor('#ff0000'));
    $out = $b->generate($widget);
    expect($out)->toContain('fontSize')
        ->and($out)->toContain('color');
});

test('Flutter generate with AppContainer produces state', function () {
    $b = (new CodegenFactory())->get('flutter');
    $container = new Perry\UI\Widget\AppContainer(
        new Perry\UI\Widget\Text('init'),
        null,
        null,
        new Binding('count', '0'),
    );
    $out = $b->generate($container);
    expect($out)->toContain('StatefulWidget')
        ->and($out)->toContain('count');
});

test('Flutter generates all widget types', function () {
    $b = (new CodegenFactory())->get('flutter');

    $specs['Text'] = [new Perry\UI\Widget\Text('Hello'), ['Text(']];
    $specs['Button'] = [new Perry\UI\Widget\Button('Click'), ['ElevatedButton']];
    $specs['VStack'] = [new Perry\UI\Widget\VStack(new Perry\UI\Widget\Text('A')), ['Column(']];
    $specs['HStack'] = [new Perry\UI\Widget\HStack(new Perry\UI\Widget\Text('B')), ['Row(']];
    $specs['Spacer'] = [new Perry\UI\Widget\Spacer(), ['Spacer(']];
    $specs['Image'] = [new Perry\UI\Widget\Image('photo.png'), ['Image.asset']];
    $specs['ScrollView'] = [new Perry\UI\Widget\ScrollView(new Perry\UI\Widget\Text('S')), ['SingleChildScrollView']];
    $specs['TextInput'] = [new Perry\UI\Widget\TextInput(StateId::next(), 'Enter'), ['TextField']];
    $specs['TextEditor'] = [new Perry\UI\Widget\TextEditor(new Binding('val', '')), ['TextField']];
    $specs['Toggle'] = [new Perry\UI\Widget\Toggle('Dark'), ['Switch(']];
    $specs['Slider'] = [new Perry\UI\Widget\Slider(new Binding('sv', 50.0)), ['Slider(']];
    $specs['ListWidget'] = [new Perry\UI\Widget\ListWidget(new Perry\UI\Widget\Text('Item')), ['ListView']];
    $specs['NavigationView'] = [new Perry\UI\Widget\NavigationView(new Perry\UI\Widget\VStack(new Perry\UI\Widget\Text('P'))), ['AppBar']];
    $specs['TabView'] = [new Perry\UI\Widget\TabView(new Perry\UI\Widget\VStack(new Perry\UI\Widget\Text('T'))), ['TabBar']];
    $specs['WebView'] = [new Perry\UI\Widget\WebView('<p>hello</p>'), ["'WebView'"]];

    foreach ($specs as $label => [$w, $kws]) {
        $out = $b->generate($w);
        expect(strlen($out))->toBeGreaterThan(0, "Flutter $label output is empty");
        foreach ($kws as $kw) {
            expect(str_contains($out, $kw))->toBeTrue("Flutter $label missing: $kw");
        }
    }
});
