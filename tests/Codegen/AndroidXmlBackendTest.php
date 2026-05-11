<?php

declare(strict_types=1);

use Perry\UI\Binding;
use Perry\UI\StateId;
use Perry\UI\Styling\Style;
use Perry\Codegen\CodegenFactory;
use Perry\Build\Target;

test('CodegenFactory registers AndroidXmlBackend', function () {
    $factory = new CodegenFactory();
    expect($factory->available())->toContain('android-xml');
});

test('AndroidXmlBackend supports Android target', function () {
    $b = (new CodegenFactory())->get('android-xml');
    expect($b->supports(Target::Android))->toBeTrue();
});

test('AndroidXmlBackend name is android-xml', function () {
    $b = (new CodegenFactory())->get('android-xml');
    expect($b->name())->toBe('android-xml');
});

test('AndroidXml generate produces valid XML layout', function () {
    $b = (new CodegenFactory())->get('android-xml');
    $out = $b->generate(new Perry\UI\Widget\Text('Hello'));
    expect($out)->toContain('<?xml')
        ->and($out)->toContain('layout')
        ->and($out)->toContain('TextView');
});

test('AndroidXml generates style attributes', function () {
    $b = (new CodegenFactory())->get('android-xml');
    $widget = (new Perry\UI\Widget\Text('Styled'))
        ->style(Style::make()->fontSize(20)->foregroundColor('#ff0000')->padding(12));
    $out = $b->generate($widget);
    expect($out)->toContain('textSize')
        ->and($out)->toContain('textColor')
        ->and($out)->toContain('padding');
});

test('AndroidXml generates all widget types', function () {
    $b = (new CodegenFactory())->get('android-xml');

    $specs['Text'] = [new Perry\UI\Widget\Text('Hello'), ['TextView']];
    $specs['Button'] = [new Perry\UI\Widget\Button('Click'), ['Button']];
    $specs['Spacer'] = [new Perry\UI\Widget\Spacer(), ['Space']];
    $specs['WebView'] = [new Perry\UI\Widget\WebView('<p>h</p>'), ['WebView']];
    $specs['TextEditor'] = [new Perry\UI\Widget\TextEditor(new Binding('val', '')), ['EditText']];
    $specs['Toggle'] = [new Perry\UI\Widget\Toggle('Dark'), ['Switch']];
    $specs['Slider'] = [new Perry\UI\Widget\Slider(new Binding('sv', 50.0)), ['SeekBar']];
    $specs['Image'] = [new Perry\UI\Widget\Image('photo.png'), ['ImageView']];
    $specs['VStack'] = [new Perry\UI\Widget\VStack(new Perry\UI\Widget\Text('A')), ['LinearLayout', 'vertical']];
    $specs['HStack'] = [new Perry\UI\Widget\HStack(new Perry\UI\Widget\Text('B')), ['LinearLayout']];
    $specs['ScrollView'] = [new Perry\UI\Widget\ScrollView(new Perry\UI\Widget\Text('S')), ['ScrollView']];
    $specs['TextInput'] = [new Perry\UI\Widget\TextInput(StateId::next(), 'Enter...'), ['EditText']];
    $specs['ListWidget'] = [new Perry\UI\Widget\ListWidget(new Perry\UI\Widget\Text('item')), ['LinearLayout']];
    $specs['NavigationView'] = [new Perry\UI\Widget\NavigationView(new Perry\UI\Widget\Text('screen')), ['FrameLayout']];
    $specs['TabView'] = [new Perry\UI\Widget\TabView(new Perry\UI\Widget\Text('tab')), ['LinearLayout']];

    foreach ($specs as $label => [$w, $kws]) {
        $out = $b->generate($w);
        expect(strlen($out))->toBeGreaterThan(0, "AndroidXml $label output is empty");
        foreach ($kws as $kw) {
            expect(str_contains($out, $kw))->toBeTrue("AndroidXml $label missing: $kw");
        }
    }
});
