<?php

declare(strict_types=1);

use Perry\UI\Binding;
use Perry\UI\StateId;
use Perry\UI\Styling\Style;
use Perry\Codegen\CodegenFactory;
use Perry\Build\Target;

test('CodegenFactory registers ArkTsBackend', function () {
    $factory = new CodegenFactory();
    expect($factory->available())->toContain('arkts');
});

test('ArkTsBackend supports HarmonyOS target', function () {
    $b = (new CodegenFactory())->get('arkts');
    expect($b->supports(Target::HarmonyOS))->toBeTrue();
});

test('ArkTsBackend name is arkts', function () {
    $b = (new CodegenFactory())->get('arkts');
    expect($b->name())->toBe('arkts');
});

test('ArkTs generate produces ArkUI component tree', function () {
    $b = (new CodegenFactory())->get('arkts');
    $out = $b->generate(new Perry\UI\Widget\Text('Hello'));
    expect($out)->toContain('@Entry')
        ->and($out)->toContain('@Component')
        ->and($out)->toContain('Text(');
});

test('ArkTs generate with AppContainer produces @State binding', function () {
    $b = (new CodegenFactory())->get('arkts');
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

test('ArkTs generates style properties', function () {
    $b = (new CodegenFactory())->get('arkts');
    $widget = (new Perry\UI\Widget\Text('Styled'))
        ->style(Style::make()->fontSize(20)->foregroundColor('#ff0000'));
    $out = $b->generate($widget);
    expect($out)->toContain('.fontSize(')
        ->and($out)->toContain('.fontColor(');
});

test('ArkTs generates all widget types', function () {
    $b = (new CodegenFactory())->get('arkts');

    $specs['Text'] = [new Perry\UI\Widget\Text('Hello'), ['Text(']];
    $specs['Button'] = [new Perry\UI\Widget\Button('Click'), ['Button(']];
    $specs['VStack'] = [new Perry\UI\Widget\VStack(new Perry\UI\Widget\Text('A')), ['Column(']];
    $specs['HStack'] = [new Perry\UI\Widget\HStack(new Perry\UI\Widget\Text('B')), ['Row(']];
    $specs['Spacer'] = [new Perry\UI\Widget\Spacer(), ['Blank(']];
    $specs['Image'] = [new Perry\UI\Widget\Image('photo.png'), ['Image(']];
    $specs['ScrollView'] = [new Perry\UI\Widget\ScrollView(new Perry\UI\Widget\Text('S')), ['Scroll(']];
    $specs['TextInput'] = [new Perry\UI\Widget\TextInput(StateId::next(), 'Enter'), ['TextInput(']];
    $specs['TextEditor'] = [new Perry\UI\Widget\TextEditor(new Binding('val', '')), ['TextArea(']];
    $specs['Toggle'] = [new Perry\UI\Widget\Toggle('Dark'), ['Toggle(']];
    $specs['Slider'] = [new Perry\UI\Widget\Slider(new Binding('sv', 50.0)), ['Slider(']];
    $specs['ListWidget'] = [new Perry\UI\Widget\ListWidget(new Perry\UI\Widget\Text('Item')), ['List(']];
    $specs['NavigationView'] = [new Perry\UI\Widget\NavigationView(new Perry\UI\Widget\VStack(new Perry\UI\Widget\Text('P'))), ['NavDestination']];
    $specs['TabView'] = [new Perry\UI\Widget\TabView(new Perry\UI\Widget\VStack(new Perry\UI\Widget\Text('T'))), ['Tabs(']];
    $specs['WebView'] = [new Perry\UI\Widget\WebView('<p>hello</p>'), ['Web(']];
    $specs['Checkbox'] = [new Perry\UI\Widget\Checkbox('Enable'), ['Checkbox(']];
    $specs['RadioButton'] = [new Perry\UI\Widget\RadioButton('Opt A', 'g', 'a'), ['Radio(']];
    $specs['Dialog'] = [new Perry\UI\Widget\Dialog(null, new Perry\UI\Widget\Text('C')), ['AlertDialog']];
    $specs['Dropdown'] = [new Perry\UI\Widget\Dropdown(['One' => '1']), ['Select(']];
    $specs['Progress'] = [new Perry\UI\Widget\Progress(), ['Progress(']];
    $specs['Toast'] = [new Perry\UI\Widget\Toast('Hi'), ['Hi']];
    $specs['SegmentedControl'] = [new Perry\UI\Widget\SegmentedControl(['A' => '1']), ['Tabs']];
    $specs['ContextMenu'] = [new Perry\UI\Widget\ContextMenu(['X' => 'x']), ['bindPopup']];
    $specs['DatePicker'] = [new Perry\UI\Widget\DatePicker(), ['DatePicker']];

    foreach ($specs as $label => [$w, $kws]) {
        $out = $b->generate($w);
        expect(strlen($out))->toBeGreaterThan(0, "ArkTs $label output is empty");
        foreach ($kws as $kw) {
            expect(str_contains($out, $kw))->toBeTrue("ArkTs $label missing: $kw");
        }
    }
});
