<?php

declare(strict_types=1);

use Perry\UI\Binding;
use Perry\UI\StateId;
use Perry\UI\Styling\Style;
use Perry\Codegen\CodegenFactory;
use Perry\Build\Target;

test('CodegenFactory registers WinUIBackend', function () {
    $factory = new CodegenFactory();
    expect($factory->available())->toContain('winui');
});

test('WinUIBackend supports Windows target', function () {
    $b = (new CodegenFactory())->get('winui');
    expect($b->supports(Target::Windows))->toBeTrue();
});

test('WinUIBackend name is winui', function () {
    $b = (new CodegenFactory())->get('winui');
    expect($b->name())->toBe('winui');
});

test('WinUI generate produces XAML with namespaces', function () {
    $b = (new CodegenFactory())->get('winui');
    $out = $b->generate(new Perry\UI\Widget\Text('Hello'));
    expect($out)->toContain('<Window')
        ->and($out)->toContain('http://schemas.microsoft.com/winfx');
});

test('WinUI generates style attributes', function () {
    $b = (new CodegenFactory())->get('winui');
    $widget = (new Perry\UI\Widget\Text('Styled'))
        ->style(Style::make()->fontSize(20)->foregroundColor('#ff0000')->padding(12));
    $out = $b->generate($widget);
    expect($out)->toContain('FontSize')
        ->and($out)->toContain('Foreground')
        ->and($out)->toContain('Padding');
});

test('WinUI generates all widget types', function () {
    $b = (new CodegenFactory())->get('winui');

    $specs['Text'] = [new Perry\UI\Widget\Text('Hello'), ['TextBlock']];
    $specs['Button'] = [new Perry\UI\Widget\Button('Click'), ['Button']];
    $specs['Spacer'] = [new Perry\UI\Widget\Spacer(), ['Rectangle']];
    $specs['WebView'] = [new Perry\UI\Widget\WebView('<p>h</p>'), ['WebView2']];
    $specs['TextEditor'] = [new Perry\UI\Widget\TextEditor(new Binding('val', '')), ['TextBox']];
    $specs['Toggle'] = [new Perry\UI\Widget\Toggle('Dark'), ['CheckBox']];
    $specs['Slider'] = [new Perry\UI\Widget\Slider(new Binding('sv', 50.0)), ['Slider']];
    $specs['Image'] = [new Perry\UI\Widget\Image('photo.png'), ['Image']];
    $specs['VStack'] = [new Perry\UI\Widget\VStack(new Perry\UI\Widget\Text('A')), ['StackPanel', 'Vertical']];
    $specs['HStack'] = [new Perry\UI\Widget\HStack(new Perry\UI\Widget\Text('B')), ['StackPanel', 'Horizontal']];
    $specs['ScrollView'] = [new Perry\UI\Widget\ScrollView(new Perry\UI\Widget\Text('S')), ['ScrollViewer']];
    $specs['TextInput'] = [new Perry\UI\Widget\TextInput(StateId::next(), 'Enter...'), ['TextBox']];
    $specs['ListWidget'] = [new Perry\UI\Widget\ListWidget(new Perry\UI\Widget\Text('item')), ['ItemsControl']];
    $specs['NavigationView'] = [new Perry\UI\Widget\NavigationView(new Perry\UI\Widget\Text('screen')), ['Frame']];
    $specs['TabView'] = [new Perry\UI\Widget\TabView(new Perry\UI\Widget\Text('tab')), ['TabControl']];
    $specs['Checkbox'] = [new Perry\UI\Widget\Checkbox('Dark'), ['CheckBox']];
    $specs['RadioButton'] = [new Perry\UI\Widget\RadioButton('A', 'g', 'a'), ['RadioButton', 'GroupName']];
    $specs['Dialog'] = [new Perry\UI\Widget\Dialog(null, new Perry\UI\Widget\Text('content')), ['Border']];
    $specs['Dropdown'] = [new Perry\UI\Widget\Dropdown(['a' => '1']), ['ComboBox']];
    $specs['Progress'] = [new Perry\UI\Widget\Progress(), ['ProgressBar']];
    $specs['Toast'] = [new Perry\UI\Widget\Toast('Hi'), ['TextBlock']];
    $specs['SegmentedControl'] = [new Perry\UI\Widget\SegmentedControl(['A' => 'a']), ['RadioButton']];
    $specs['ContextMenu'] = [new Perry\UI\Widget\ContextMenu(['X' => 'x']), ['MenuItem']];
    $specs['DatePicker'] = [new Perry\UI\Widget\DatePicker(), ['DatePicker']];

    foreach ($specs as $label => [$w, $kws]) {
        $out = $b->generate($w);
        expect(strlen($out))->toBeGreaterThan(0, "WinUI $label output is empty");
        foreach ($kws as $kw) {
            expect(str_contains($out, $kw))->toBeTrue("WinUI $label missing: $kw");
        }
    }
});
