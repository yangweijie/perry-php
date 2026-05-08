<?php

declare(strict_types=1);

use Perry\UI\Binding;
use Perry\UI\StateId;
use Perry\Codegen\CodegenFactory;

test('SwiftUI smoke: each widget generates output', function () {
    $b = (new CodegenFactory())->get('swiftui');
    $specs = [];
    $specs['Text'] = new Perry\UI\Widget\Text('Hello World');
    $specs['Button'] = new Perry\UI\Widget\Button('Click');
    $specs['VStack'] = new Perry\UI\Widget\VStack(new Perry\UI\Widget\Text('A'));
    $specs['HStack'] = new Perry\UI\Widget\HStack(new Perry\UI\Widget\Text('B'));
    $specs['Spacer'] = new Perry\UI\Widget\Spacer();
    $specs['Image'] = new Perry\UI\Widget\Image('photo.png');
    $specs['ScrollView'] = new Perry\UI\Widget\ScrollView(new Perry\UI\Widget\Text('S'));
    $specs['TextInput'] = new Perry\UI\Widget\TextInput(StateId::next(), 'Enter');
    $specs['TextEditor'] = new Perry\UI\Widget\TextEditor(new Binding('val', ''));
    $specs['Toggle'] = new Perry\UI\Widget\Toggle('Dark');
    $specs['Slider'] = new Perry\UI\Widget\Slider(new Binding('sv', 50.0));
    $specs['ListWidget'] = new Perry\UI\Widget\ListWidget(new Perry\UI\Widget\Text('Item'));
    $specs['NavigationView'] = new Perry\UI\Widget\NavigationView(new Perry\UI\Widget\VStack(new Perry\UI\Widget\Text('P')));
    $specs['TabView'] = new Perry\UI\Widget\TabView(new Perry\UI\Widget\VStack(new Perry\UI\Widget\Text('T')));
    $specs['WebView'] = new Perry\UI\Widget\WebView('<p>hello</p>');
    
    foreach ($specs as $label => $widget) {
        $out = $b->generate($widget);
        expect(strlen($out))->toBeGreaterThan(0, "SwiftUI $label empty");
    }
});

test('Compose smoke: each widget generates output', function () {
    $b = (new CodegenFactory())->get('compose');
    $specs = [];
    $specs['Text'] = new Perry\UI\Widget\Text('Hello');
    $specs['Button'] = new Perry\UI\Widget\Button('Click');
    $specs['VStack'] = new Perry\UI\Widget\VStack(new Perry\UI\Widget\Text('A'));
    $specs['Spacer'] = new Perry\UI\Widget\Spacer();
    $specs['WebView'] = new Perry\UI\Widget\WebView('<p>h</p>');
    $specs['TextEditor'] = new Perry\UI\Widget\TextEditor(new Binding('val', ''));
    $specs['Toggle'] = new Perry\UI\Widget\Toggle('Dark');
    $specs['Slider'] = new Perry\UI\Widget\Slider(new Binding('sv', 50.0));
    
    foreach ($specs as $label => $widget) {
        $out = $b->generate($widget);
        expect(strlen($out))->toBeGreaterThan(0, "Compose $label empty");
    }
});

test('AndroidXml smoke: each widget generates output', function () {
    $b = (new CodegenFactory())->get('android-xml');
    $specs['Text'] = new Perry\UI\Widget\Text('Hello');
    $specs['Button'] = new Perry\UI\Widget\Button('Click');
    $specs['Spacer'] = new Perry\UI\Widget\Spacer();
    $specs['WebView'] = new Perry\UI\Widget\WebView('<p>h</p>');
    $specs['TextEditor'] = new Perry\UI\Widget\TextEditor(new Binding('val', ''));
    
    foreach ($specs as $label => $widget) {
        $out = $b->generate($widget);
        expect(strlen($out))->toBeGreaterThan(0, "AndroidXml $label empty");
    }
});

test('Gtk4 smoke: each widget generates output', function () {
    $b = (new CodegenFactory())->get('gtk4');
    $specs['Text'] = new Perry\UI\Widget\Text('Hello');
    $specs['Button'] = new Perry\UI\Widget\Button('Click');
    $specs['Spacer'] = new Perry\UI\Widget\Spacer();
    $specs['WebView'] = new Perry\UI\Widget\WebView('<p>h</p>');
    $specs['TextEditor'] = new Perry\UI\Widget\TextEditor(new Binding('val', ''));
    
    foreach ($specs as $label => $widget) {
        $out = $b->generate($widget);
        expect(strlen($out))->toBeGreaterThan(0, "Gtk4 $label empty");
    }
});

test('WinUI smoke: each widget generates output', function () {
    $b = (new CodegenFactory())->get('winui');
    $specs['Text'] = new Perry\UI\Widget\Text('Hello');
    $specs['Button'] = new Perry\UI\Widget\Button('Click');
    $specs['Spacer'] = new Perry\UI\Widget\Spacer();
    $specs['WebView'] = new Perry\UI\Widget\WebView('<p>h</p>');
    $specs['TextEditor'] = new Perry\UI\Widget\TextEditor(new Binding('val', ''));
    
    foreach ($specs as $label => $widget) {
        $out = $b->generate($widget);
        expect(strlen($out))->toBeGreaterThan(0, "WinUI $label empty");
    }
});

test('ArkTS smoke: each widget generates output', function () {
    $b = (new CodegenFactory())->get('arkts');
    $specs['Text'] = new Perry\UI\Widget\Text('Hello');
    $specs['Button'] = new Perry\UI\Widget\Button('Click');
    $specs['VStack'] = new Perry\UI\Widget\VStack(new Perry\UI\Widget\Text('A'));
    $specs['HStack'] = new Perry\UI\Widget\HStack(new Perry\UI\Widget\Text('B'));
    $specs['Spacer'] = new Perry\UI\Widget\Spacer();
    $specs['Image'] = new Perry\UI\Widget\Image('photo.png');
    $specs['ScrollView'] = new Perry\UI\Widget\ScrollView(new Perry\UI\Widget\Text('S'));
    $specs['TextInput'] = new Perry\UI\Widget\TextInput(Perry\UI\StateId::next(), 'Enter');
    $specs['TextEditor'] = new Perry\UI\Widget\TextEditor(new Perry\UI\Binding('val', ''));
    $specs['Toggle'] = new Perry\UI\Widget\Toggle('Dark');
    $specs['Slider'] = new Perry\UI\Widget\Slider(new Perry\UI\Binding('sv', 50.0));
    $specs['ListWidget'] = new Perry\UI\Widget\ListWidget(new Perry\UI\Widget\Text('Item'));
    $specs['NavigationView'] = new Perry\UI\Widget\NavigationView(new Perry\UI\Widget\VStack(new Perry\UI\Widget\Text('P')));
    $specs['TabView'] = new Perry\UI\Widget\TabView(new Perry\UI\Widget\VStack(new Perry\UI\Widget\Text('T')));
    $specs['WebView'] = new Perry\UI\Widget\WebView('<p>hello</p>');
    
    foreach ($specs as $label => $widget) {
        $out = $b->generate($widget);
        expect(strlen($out))->toBeGreaterThan(0, "ArkTS $label empty");
    }
});

test('Glance smoke: each widget generates output', function () {
    $b = (new CodegenFactory())->get('glance');
    $specs['Text'] = new Perry\UI\Widget\Text('Hello');
    $specs['Button'] = new Perry\UI\Widget\Button('Click');
    $specs['VStack'] = new Perry\UI\Widget\VStack(new Perry\UI\Widget\Text('A'));
    $specs['HStack'] = new Perry\UI\Widget\HStack(new Perry\UI\Widget\Text('B'));
    $specs['Spacer'] = new Perry\UI\Widget\Spacer();
    $specs['ScrollView'] = new Perry\UI\Widget\ScrollView(new Perry\UI\Widget\Text('S'));
    $specs['Image'] = new Perry\UI\Widget\Image('photo.png');
    $specs['Toggle'] = new Perry\UI\Widget\Toggle('Dark');
    
    foreach ($specs as $label => $widget) {
        $out = $b->generate($widget);
        expect(strlen($out))->toBeGreaterThan(0, "Glance $label empty");
    }
});

test('WearTiles smoke: each widget generates output', function () {
    $b = (new CodegenFactory())->get('wear-tiles');
    $specs['Text'] = new Perry\UI\Widget\Text('Hello');
    $specs['Button'] = new Perry\UI\Widget\Button('Click');
    $specs['VStack'] = new Perry\UI\Widget\VStack(new Perry\UI\Widget\Text('A'));
    $specs['HStack'] = new Perry\UI\Widget\HStack(new Perry\UI\Widget\Text('B'));
    $specs['Spacer'] = new Perry\UI\Widget\Spacer();
    $specs['ScrollView'] = new Perry\UI\Widget\ScrollView(new Perry\UI\Widget\Text('S'));
    $specs['Image'] = new Perry\UI\Widget\Image('photo.png');
    
    foreach ($specs as $label => $widget) {
        $out = $b->generate($widget);
        expect(strlen($out))->toBeGreaterThan(0, "WearTiles $label empty");
    }
});

test('Flutter smoke: each widget generates output', function () {
    $b = (new CodegenFactory())->get('flutter');
    $specs['Text'] = new Perry\UI\Widget\Text('Hello');
    $specs['Button'] = new Perry\UI\Widget\Button('Click');
    $specs['VStack'] = new Perry\UI\Widget\VStack(new Perry\UI\Widget\Text('A'));
    $specs['HStack'] = new Perry\UI\Widget\HStack(new Perry\UI\Widget\Text('B'));
    $specs['Spacer'] = new Perry\UI\Widget\Spacer();
    $specs['Image'] = new Perry\UI\Widget\Image('photo.png');
    $specs['ScrollView'] = new Perry\UI\Widget\ScrollView(new Perry\UI\Widget\Text('S'));
    $specs['TextInput'] = new Perry\UI\Widget\TextInput(Perry\UI\StateId::next(), 'Enter');
    $specs['TextEditor'] = new Perry\UI\Widget\TextEditor(new Perry\UI\Binding('val', ''));
    $specs['Toggle'] = new Perry\UI\Widget\Toggle('Dark');
    $specs['Slider'] = new Perry\UI\Widget\Slider(new Perry\UI\Binding('sv', 50.0));
    $specs['ListWidget'] = new Perry\UI\Widget\ListWidget(new Perry\UI\Widget\Text('Item'));
    $specs['NavigationView'] = new Perry\UI\Widget\NavigationView(new Perry\UI\Widget\VStack(new Perry\UI\Widget\Text('P')));
    $specs['TabView'] = new Perry\UI\Widget\TabView(new Perry\UI\Widget\VStack(new Perry\UI\Widget\Text('T')));
    $specs['WebView'] = new Perry\UI\Widget\WebView('<p>hello</p>');
    
    foreach ($specs as $label => $widget) {
        $out = $b->generate($widget);
        expect(strlen($out))->toBeGreaterThan(0, "Flutter $label empty");
    }
});

test('Html smoke: each widget generates output', function () {
    $b = (new CodegenFactory())->get('html');
    $specs['Text'] = new Perry\UI\Widget\Text('Hello');
    $specs['Button'] = new Perry\UI\Widget\Button('Click');
    $specs['Spacer'] = new Perry\UI\Widget\Spacer();
    $specs['WebView'] = new Perry\UI\Widget\WebView('<p>h</p>');
    $specs['TextEditor'] = new Perry\UI\Widget\TextEditor(new Binding('val', ''));
    
    foreach ($specs as $label => $widget) {
        $out = $b->generate($widget);
        expect(strlen($out))->toBeGreaterThan(0, "Html $label empty");
    }
});
