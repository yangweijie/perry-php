<?php

declare(strict_types=1);

use Perry\UI\Binding;
use Perry\UI\StateId;
use Perry\UI\Styling\Style;
use Perry\Codegen\CodegenFactory;
use Perry\Build\Target;

test('CodegenFactory registers SwiftUIBackend', function () {
    $factory = new CodegenFactory();
    expect($factory->available())->toContain('swiftui');
});

test('SwiftUIBackend supports Apple targets', function () {
    $b = (new CodegenFactory())->get('swiftui');
    expect($b->supports(Target::MacOs))->toBeTrue()
        ->and($b->supports(Target::IOS))->toBeTrue()
        ->and($b->supports(Target::TvOs))->toBeTrue()
        ->and($b->supports(Target::VisionOs))->toBeTrue()
        ->and($b->supports(Target::WatchOs))->toBeTrue();
});

test('SwiftUIBackend name is swiftui', function () {
    $b = (new CodegenFactory())->get('swiftui');
    expect($b->name())->toBe('swiftui');
});

test('SwiftUI generate produces valid Swift struct', function () {
    $b = (new CodegenFactory())->get('swiftui');
    $out = $b->generate(new Perry\UI\Widget\Text('Hello'));
    expect($out)->toContain('import SwiftUI')
        ->and($out)->toContain('struct')
        ->and($out)->toContain('Text(');
});

test('SwiftUI generate with AppContainer produces @State binding', function () {
    $b = (new CodegenFactory())->get('swiftui');
    $container = new Perry\UI\Widget\AppContainer(
        new Perry\UI\Widget\Text('init'),
        null,
        null,
        new Binding('count', '0'),
    );
    $out = $b->generate($container);
    expect($out)->toContain('@State')
        ->and($out)->toContain('count');
});

test('SwiftUI generates all widget types', function () {
    $b = (new CodegenFactory())->get('swiftui');

    $specs['Text'] = [new Perry\UI\Widget\Text('Hello'), ['Text(']];
    $specs['Button'] = [new Perry\UI\Widget\Button('Click'), ['Button(']];
    $specs['VStack'] = [new Perry\UI\Widget\VStack(new Perry\UI\Widget\Text('A')), ['VStack']];
    $specs['HStack'] = [new Perry\UI\Widget\HStack(new Perry\UI\Widget\Text('B')), ['HStack']];
    $specs['Spacer'] = [new Perry\UI\Widget\Spacer(), ['Spacer()']];
    $specs['Image'] = [new Perry\UI\Widget\Image('photo.png'), ['Image(']];
    $specs['ScrollView'] = [new Perry\UI\Widget\ScrollView(new Perry\UI\Widget\Text('S')), ['ScrollView']];
    $specs['TextInput'] = [new Perry\UI\Widget\TextInput(StateId::next(), 'Enter'), ['TextField']];
    $specs['TextEditor'] = [new Perry\UI\Widget\TextEditor(new Binding('val', '')), ['TextEditor']];
    $specs['Toggle'] = [new Perry\UI\Widget\Toggle('Dark'), ['Toggle(']];
    $specs['Slider'] = [new Perry\UI\Widget\Slider(new Binding('sv', 50.0)), ['Slider(']];
    $specs['ListWidget'] = [new Perry\UI\Widget\ListWidget(new Perry\UI\Widget\Text('Item')), ['List {']];
    $specs['NavigationView'] = [new Perry\UI\Widget\NavigationView(new Perry\UI\Widget\VStack(new Perry\UI\Widget\Text('Page'))), ['NavigationView']];
    $specs['TabView'] = [new Perry\UI\Widget\TabView(new Perry\UI\Widget\VStack(new Perry\UI\Widget\Text('T'))), ['TabView']];
    $specs['WebView'] = [new Perry\UI\Widget\WebView('<p>hello</p>'), ['WebViewWrapper']];
    $specs['Checkbox'] = [new Perry\UI\Widget\Checkbox('Dark'), ['Toggle', 'checkbox']];
    $specs['RadioButton'] = [new Perry\UI\Widget\RadioButton('A', 'g', 'a'), ['Button', '"A"']];
    $specs['Dialog'] = [new Perry\UI\Widget\Dialog(new Binding('dialogOpen', true), new Perry\UI\Widget\Text('content')), ['opacity']];
    $specs['Dropdown'] = [new Perry\UI\Widget\Dropdown(['a' => '1']), ['Picker']];
    $specs['Progress'] = [new Perry\UI\Widget\Progress(), ['ProgressView']];
    $specs['Toast'] = [new Perry\UI\Widget\Toast('Hi'), ['Text']];
    $specs['SegmentedControl'] = [new Perry\UI\Widget\SegmentedControl(['A' => 'a']), ['segmented']];
    $specs['ContextMenu'] = [new Perry\UI\Widget\ContextMenu(['X' => 'x']), ['contextMenu']];
    $specs['DatePicker'] = [new Perry\UI\Widget\DatePicker(), ['DatePicker']];

    foreach ($specs as $label => [$w, $kws]) {
        $out = $b->generate($w);
        expect(strlen($out))->toBeGreaterThan(0, "SwiftUI $label output is empty");
        foreach ($kws as $kw) {
            expect(str_contains($out, $kw))->toBeTrue("SwiftUI $label missing: $kw");
        }
    }
});

test('SwiftUI generates style modifiers', function () {
    $b = (new CodegenFactory())->get('swiftui');
    $widget = (new Perry\UI\Widget\Text('Styled'))
        ->style(Style::make()->fontSize(20)->foregroundColor('#ff0000'));
    $out = $b->generate($widget);
    expect($out)->toContain('.font')
        ->and($out)->toContain('.foregroundColor');
});
