<?php

declare(strict_types=1);

use Perry\UI\Binding;
use Perry\UI\StateId;
use Perry\UI\Styling\Style;
use Perry\Codegen\CodegenFactory;
use Perry\Build\Target;

test('CodegenFactory registers WearTilesBackend', function () {
    $factory = new CodegenFactory();
    expect($factory->available())->toContain('wear-tiles');
});

test('WearTilesBackend supports WearTiles target', function () {
    $b = (new CodegenFactory())->get('wear-tiles');
    expect($b->supports(Target::WearTiles))->toBeTrue();
});

test('WearTilesBackend name is wear-tiles', function () {
    $b = (new CodegenFactory())->get('wear-tiles');
    expect($b->name())->toBe('wear-tiles');
});

test('WearTiles generate produces TileService', function () {
    $b = (new CodegenFactory())->get('wear-tiles');
    $out = $b->generate(new Perry\UI\Widget\Text('Hello'));
    expect($out)->toContain('TileService')
        ->and($out)->toContain('LayoutElementBuilders');
});

test('WearTiles generates builder API style properties', function () {
    $b = (new CodegenFactory())->get('wear-tiles');
    $widget = (new Perry\UI\Widget\Text('Styled'))
        ->style(Style::make()->fontSize(20)->foregroundColor('#ff0000'));
    $out = $b->generate($widget);
    expect($out)->toContain('setFontSize(')
        ->and($out)->toContain('setColor(');
});

test('WearTiles generates all widget types', function () {
    $b = (new CodegenFactory())->get('wear-tiles');

    $specs['Text'] = [new Perry\UI\Widget\Text('Hello'), ['Text.Builder']];
    $specs['Button'] = [new Perry\UI\Widget\Button('Click'), ['Button.Builder']];
    $specs['VStack'] = [new Perry\UI\Widget\VStack(new Perry\UI\Widget\Text('A')), ['Column.Builder']];
    $specs['HStack'] = [new Perry\UI\Widget\HStack(new Perry\UI\Widget\Text('B')), ['Row.Builder']];
    $specs['Spacer'] = [new Perry\UI\Widget\Spacer(), ['Spacer.Builder']];
    $specs['Image'] = [new Perry\UI\Widget\Image('photo.png'), ['Image.Builder']];
    $specs['ScrollView'] = [new Perry\UI\Widget\ScrollView(new Perry\UI\Widget\Text('S')), ['Scroll.Builder']];
    $specs['TextInput'] = [new Perry\UI\Widget\TextInput(StateId::next(), 'Enter...'), ['Text.Builder', 'Enter']];
    $specs['TextEditor'] = [new Perry\UI\Widget\TextEditor(new Binding('val', '')), ['not supported in Wear Tiles']];
    $specs['Toggle'] = [new Perry\UI\Widget\Toggle('Dark'), ['Dark']];
    $specs['Slider'] = [new Perry\UI\Widget\Slider(new Binding('sv', 50.0)), ['not supported in Wear Tiles']];
    $specs['ListWidget'] = [new Perry\UI\Widget\ListWidget(new Perry\UI\Widget\Text('item')), ['Column.Builder']];
    $specs['NavigationView'] = [new Perry\UI\Widget\NavigationView(new Perry\UI\Widget\Text('screen')), ['not supported in Wear Tiles']];
    $specs['TabView'] = [new Perry\UI\Widget\TabView(new Perry\UI\Widget\Text('tab')), ['not supported in Wear Tiles']];
    $specs['WebView'] = [new Perry\UI\Widget\WebView('<p>h</p>'), ['not supported in Wear Tiles']];
    $specs['Checkbox'] = [new Perry\UI\Widget\Checkbox('Dark'), ['not supported in Wear Tiles']];
    $specs['RadioButton'] = [new Perry\UI\Widget\RadioButton('A', 'g', 'a'), ['not supported in Wear Tiles']];
    $specs['Dialog'] = [new Perry\UI\Widget\Dialog(null, new Perry\UI\Widget\Text('content')), ['not supported in Wear Tiles']];
    $specs['Dropdown'] = [new Perry\UI\Widget\Dropdown(['a' => '1']), ['not supported in Wear Tiles']];
    $specs['Progress'] = [new Perry\UI\Widget\Progress(), ['not supported in Wear Tiles']];
    $specs['Toast'] = [new Perry\UI\Widget\Toast('Hi'), ['not supported in Wear Tiles']];

    foreach ($specs as $label => [$w, $kws]) {
        $out = $b->generate($w);
        expect(strlen($out))->toBeGreaterThan(0, "WearTiles $label output is empty");
        foreach ($kws as $kw) {
            expect(str_contains($out, $kw))->toBeTrue("WearTiles $label missing: $kw");
        }
    }
});
