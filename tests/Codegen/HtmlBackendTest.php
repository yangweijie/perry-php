<?php

declare(strict_types=1);

use Perry\UI\Binding;
use Perry\UI\StateId;
use Perry\UI\Styling\Style;
use Perry\Codegen\CodegenFactory;
use Perry\Build\Target;

test('CodegenFactory registers HtmlBackend', function () {
    $factory = new CodegenFactory();
    expect($factory->available())->toContain('html');
});

test('HtmlBackend supports Web target', function () {
    $b = (new CodegenFactory())->get('html');
    expect($b->supports(Target::Web))->toBeTrue();
});

test('HtmlBackend name is html', function () {
    $b = (new CodegenFactory())->get('html');
    expect($b->name())->toBe('html');
});

test('Html generate produces valid HTML document', function () {
    $b = (new CodegenFactory())->get('html');
    $out = $b->generate(new Perry\UI\Widget\Text('Hello'));
    expect($out)->toContain('<!DOCTYPE html>')
        ->and($out)->toContain('<html')
        ->and($out)->toContain('<head>')
        ->and($out)->toContain('<body>')
        ->and($out)->toContain('</body>');
});

test('Html generate produces inline CSS', function () {
    $b = (new CodegenFactory())->get('html');
    $widget = (new Perry\UI\Widget\Text('Styled'))
        ->style(Style::make()->fontSize(20)->foregroundColor('#ff0000')->padding(12));
    $out = $b->generate($widget);
    expect($out)->toContain('font-size')
        ->and($out)->toContain('color')
        ->and($out)->toContain('padding');
});

test('Html generates all widget types', function () {
    $b = (new CodegenFactory())->get('html');

    $specs['Text'] = [new Perry\UI\Widget\Text('Hello'), ['Hello']];
    $specs['Button'] = [new Perry\UI\Widget\Button('Click'), ['<button']];
    $specs['Spacer'] = [new Perry\UI\Widget\Spacer(), ['spacer']];
    $specs['WebView'] = [new Perry\UI\Widget\WebView('<p>h</p>'), ['<iframe']];
    $specs['TextEditor'] = [new Perry\UI\Widget\TextEditor(new Binding('val', '')), ['<textarea']];
    $specs['VStack'] = [new Perry\UI\Widget\VStack(new Perry\UI\Widget\Text('A')), ['vstack']];
    $specs['HStack'] = [new Perry\UI\Widget\HStack(new Perry\UI\Widget\Text('B')), ['hstack']];
    $specs['Image'] = [new Perry\UI\Widget\Image('photo.png'), ['<img']];
    $specs['ScrollView'] = [new Perry\UI\Widget\ScrollView(new Perry\UI\Widget\Text('S')), ['overflow:auto']];
    $specs['Toggle'] = [new Perry\UI\Widget\Toggle('Dark'), ['checkbox']];
    $specs['Slider'] = [new Perry\UI\Widget\Slider(new Binding('sv', 50.0)), ['range']];
    $specs['ListWidget'] = [new Perry\UI\Widget\ListWidget(new Perry\UI\Widget\Text('item')), ['class="list"']];
    $specs['NavigationView'] = [new Perry\UI\Widget\NavigationView(new Perry\UI\Widget\Text('screen')), ['class="nav-view"']];
    $specs['TabView'] = [new Perry\UI\Widget\TabView(new Perry\UI\Widget\Text('tab')), ['class="tab-view"']];
    $specs['TextInput'] = [new Perry\UI\Widget\TextInput(StateId::next(), 'Enter...'), ['type="text"']];
    $specs['Checkbox'] = [new Perry\UI\Widget\Checkbox('Dark'), ['checkbox']];
    $specs['RadioButton'] = [new Perry\UI\Widget\RadioButton('A', 'g', 'a'), ['radio']];
    $specs['Dialog'] = [new Perry\UI\Widget\Dialog(null, new Perry\UI\Widget\Text('content')), ['dialog']];
    $specs['Dropdown'] = [new Perry\UI\Widget\Dropdown(['a' => '1']), ['select']];
    $specs['Progress'] = [new Perry\UI\Widget\Progress(), ['progress']];
    $specs['Toast'] = [new Perry\UI\Widget\Toast('Hi'), ['toast']];
    $specs['SegmentedControl'] = [new Perry\UI\Widget\SegmentedControl(['A' => 'a']), ['button']];
    $specs['ContextMenu'] = [new Perry\UI\Widget\ContextMenu(['X' => 'x']), ['context']];
    $specs['DatePicker'] = [new Perry\UI\Widget\DatePicker(), ['date']];

    foreach ($specs as $label => [$w, $kws]) {
        $out = $b->generate($w);
        expect(strlen($out))->toBeGreaterThan(0, "Html $label output is empty");
        foreach ($kws as $kw) {
            expect(str_contains($out, $kw))->toBeTrue("Html $label missing: $kw");
        }
    }
});
