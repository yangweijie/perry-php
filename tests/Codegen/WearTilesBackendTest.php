<?php

declare(strict_types=1);

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

    foreach ($specs as $label => [$w, $kws]) {
        $out = $b->generate($w);
        expect(strlen($out))->toBeGreaterThan(0, "WearTiles $label output is empty");
        foreach ($kws as $kw) {
            expect(str_contains($out, $kw))->toBeTrue("WearTiles $label missing: $kw");
        }
    }
});
