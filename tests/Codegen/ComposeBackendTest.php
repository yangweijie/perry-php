<?php

declare(strict_types=1);

use Perry\UI\Binding;
use Perry\UI\StateId;
use Perry\UI\Styling\Style;
use Perry\Codegen\CodegenFactory;
use Perry\Build\Target;

test('CodegenFactory registers ComposeBackend', function () {
    $factory = new CodegenFactory();
    expect($factory->available())->toContain('compose');
});

test('ComposeBackend supports Android target', function () {
    $b = (new CodegenFactory())->get('compose');
    expect($b->supports(Target::Android))->toBeTrue();
});

test('ComposeBackend name is compose', function () {
    $b = (new CodegenFactory())->get('compose');
    expect($b->name())->toBe('compose');
});

test('Compose generate produces Kotlin composable', function () {
    $b = (new CodegenFactory())->get('compose');
    $out = $b->generate(new Perry\UI\Widget\Text('Hello'));
    expect($out)->toContain('fun')
        ->and($out)->toContain('Text(');
});

test('Compose generate with AppContainer produces state', function () {
    $b = (new CodegenFactory())->get('compose');
    $container = new Perry\UI\Widget\AppContainer(
        new Perry\UI\Widget\Text('init'),
        null,
        null,
        new Binding('count', '0'),
    );
    $out = $b->generate($container);
    expect($out)->toContain('var count by remember');
});

test('Compose generates all widget types', function () {
    $b = (new CodegenFactory())->get('compose');

    $specs['Text'] = [new Perry\UI\Widget\Text('Hello'), ['Text(']];
    $specs['Button'] = [new Perry\UI\Widget\Button('Click'), ['Button(']];
    $specs['Spacer'] = [new Perry\UI\Widget\Spacer(), ['Spacer(']];
    $specs['WebView'] = [new Perry\UI\Widget\WebView('<p>h</p>'), ['AndroidView']];
    $specs['TextEditor'] = [new Perry\UI\Widget\TextEditor(new Binding('val', '')), ['OutlinedTextField']];
    $specs['Toggle'] = [new Perry\UI\Widget\Toggle('Dark'), ['Switch(']];
    $specs['Slider'] = [new Perry\UI\Widget\Slider(new Binding('sv', 50.0)), ['Slider(']];
    $specs['VStack'] = [new Perry\UI\Widget\VStack(new Perry\UI\Widget\Text('A')), ['Column(']];
    $specs['HStack'] = [new Perry\UI\Widget\HStack(new Perry\UI\Widget\Text('B')), ['Row(']];
    $specs['Image'] = [new Perry\UI\Widget\Image('photo.png'), ['Image(']];
    $specs['ScrollView'] = [new Perry\UI\Widget\ScrollView(new Perry\UI\Widget\Text('S')), ['ScrollState']];
    $specs['TextInput'] = [new Perry\UI\Widget\TextInput(StateId::next(), 'Enter'), ['TextField']];
    $specs['ListWidget'] = [new Perry\UI\Widget\ListWidget(new Perry\UI\Widget\Text('Item')), ['LazyColumn']];
    $specs['NavigationView'] = [new Perry\UI\Widget\NavigationView(new Perry\UI\Widget\VStack(new Perry\UI\Widget\Text('P'))), ['fillMaxSize']];
    $specs['TabView'] = [new Perry\UI\Widget\TabView(new Perry\UI\Widget\VStack(new Perry\UI\Widget\Text('T'))), ['TabRow']];
    $specs['Checkbox'] = [new Perry\UI\Widget\Checkbox('Enable'), ['Checkbox(']];
    $specs['RadioButton'] = [new Perry\UI\Widget\RadioButton('Option A', 'group1', 'a'), ['RadioButton(']];
    $specs['Dialog'] = [new Perry\UI\Widget\Dialog(null, new Perry\UI\Widget\Text('Content')), ['Dialog(']];
    $specs['Dropdown'] = [new Perry\UI\Widget\Dropdown(['One' => '1', 'Two' => '2']), ['ExposedDropdownMenuBox']];
    $specs['Progress'] = [new Perry\UI\Widget\Progress(), ['LinearProgressIndicator']];
    $specs['Toast'] = [new Perry\UI\Widget\Toast('Hello'), ['Hello']];
    $specs['SegmentedControl'] = [new Perry\UI\Widget\SegmentedControl(['A' => 'a']), ['ExposedDropdownMenuBox']];
    $specs['ContextMenu'] = [new Perry\UI\Widget\ContextMenu(['X' => 'x']), ['DropdownMenu']];
    $specs['DatePicker'] = [new Perry\UI\Widget\DatePicker(), ['DatePicker']];

    foreach ($specs as $label => [$w, $kws]) {
        $out = $b->generate($w);
        expect(strlen($out))->toBeGreaterThan(0, "Compose $label output is empty");
        foreach ($kws as $kw) {
            expect(str_contains($out, $kw))->toBeTrue("Compose $label missing: $kw");
        }
    }
});

test('Compose generates style modifiers', function () {
    $b = (new CodegenFactory())->get('compose');
    $widget = (new Perry\UI\Widget\Text('Styled'))
        ->style(Style::make()->fontSize(20)->foregroundColor('#ff0000'));
    $out = $b->generate($widget);
    expect($out)->toContain('fontSize = 20.sp')
        ->and($out)->toContain('color = Color(0xFFff0000)');
});
