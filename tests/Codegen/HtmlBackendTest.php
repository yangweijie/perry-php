<?php

declare(strict_types=1);

use Perry\UI\Binding;
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

test('Html generates all 16 widgets', function () {
    $b = (new CodegenFactory())->get('html');

    $specs['Text'] = [new Perry\UI\Widget\Text('Hello'), ['Hello']];
    $specs['Button'] = [new Perry\UI\Widget\Button('Click'), ['<button']];
    $specs['Spacer'] = [new Perry\UI\Widget\Spacer(), ['spacer']];
    $specs['WebView'] = [new Perry\UI\Widget\WebView('<p>h</p>'), ['<iframe']];
    $specs['TextEditor'] = [new Perry\UI\Widget\TextEditor(new Binding('val', '')), ['<textarea']];

    foreach ($specs as $label => [$w, $kws]) {
        $out = $b->generate($w);
        expect(strlen($out))->toBeGreaterThan(0, "Html $label output is empty");
        foreach ($kws as $kw) {
            expect(str_contains($out, $kw))->toBeTrue("Html $label missing: $kw");
        }
    }
});
