<?php

declare(strict_types=1);

use Perry\UI\Styling\Style;
use Perry\Codegen\CodegenFactory;
use Perry\Build\Target;

test('CodegenFactory registers GlanceBackend', function () {
    $factory = new CodegenFactory();
    expect($factory->available())->toContain('glance');
});

test('GlanceBackend supports Glance target', function () {
    $b = (new CodegenFactory())->get('glance');
    expect($b->supports(Target::Glance))->toBeTrue();
});

test('GlanceBackend name is glance', function () {
    $b = (new CodegenFactory())->get('glance');
    expect($b->name())->toBe('glance');
});

test('Glance generate produces GlanceAppWidget class', function () {
    $b = (new CodegenFactory())->get('glance');
    $out = $b->generate(new Perry\UI\Widget\Text('Hello'));
    expect($out)->toContain('GlanceAppWidget')
        ->and($out)->toContain('Text(');
});

test('Glance generates style properties as modifiers', function () {
    $b = (new CodegenFactory())->get('glance');
    $widget = (new Perry\UI\Widget\Text('Styled'))
        ->style(Style::make()->fontSize(18)->foregroundColor('#ff0000'));
    $out = $b->generate($widget);
    expect($out)->toContain('TextStyle(')
        ->and($out)->toContain('fontSize');
});

test('Glance generates layout containers', function () {
    $b = (new CodegenFactory())->get('glance');
    $vstack = new Perry\UI\Widget\VStack(new Perry\UI\Widget\Text('A'));
    $out = $b->generate($vstack);
    expect($out)->toContain('Column(');
});

test('Glance generates all widget types', function () {
    $b = (new CodegenFactory())->get('glance');

    $specs['Text'] = [new Perry\UI\Widget\Text('Hello'), ['Text(']];
    $specs['Button'] = [new Perry\UI\Widget\Button('Click'), ['Text(']];
    $specs['VStack'] = [new Perry\UI\Widget\VStack(new Perry\UI\Widget\Text('A')), ['Column(']];
    $specs['HStack'] = [new Perry\UI\Widget\HStack(new Perry\UI\Widget\Text('B')), ['Row(']];
    $specs['Spacer'] = [new Perry\UI\Widget\Spacer(), ['Spacer(']];
    $specs['Image'] = [new Perry\UI\Widget\Image('photo.png'), ['Image(']];
    $specs['ScrollView'] = [new Perry\UI\Widget\ScrollView(new Perry\UI\Widget\Text('S')), ['fillMaxWidth']];
    $specs['Toggle'] = [new Perry\UI\Widget\Toggle('Dark'), []];

    foreach ($specs as $label => [$w, $kws]) {
        $out = $b->generate($w);
        expect(strlen($out))->toBeGreaterThan(0, "Glance $label output is empty");
        foreach ($kws as $kw) {
            expect(str_contains($out, $kw))->toBeTrue("Glance $label missing: $kw");
        }
    }
});
