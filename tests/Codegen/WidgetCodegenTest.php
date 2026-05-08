<?php

declare(strict_types=1);

use Perry\UI\Binding;
use Perry\UI\StateId;
use Perry\UI\Styling\Style;
use Perry\UI\Styling\StyleProperty;
use Perry\Codegen\CodegenFactory;

// ---------------------------------------------------------------------------
// Backend factory
// ---------------------------------------------------------------------------

test('CodegenFactory instantiates all 11 backends', function () {
    $factory = new CodegenFactory();
    expect($factory->available())->toContain('swiftui', 'html', 'android-xml', 'compose', 'gtk4', 'winui', 'wasm', 'arkts', 'glance', 'wear-tiles', 'flutter');
});

test('all backends have a non-empty name', function () {
    $factory = new CodegenFactory();
    foreach ($factory->available() as $name) {
        expect($factory->get($name)->name())->not->toBeEmpty();
    }
});

// ---------------------------------------------------------------------------
// Per-backend: individual widget generation for each of 16 kinds
// ---------------------------------------------------------------------------

function backendGet(string $name): \Perry\Codegen\CodegenBackend
{
    return (new CodegenFactory())->get($name);
}

test('SwiftUI generates all 16 widgets', function () {
    $b = backendGet('swiftui');
    $specs['Text'] = [new Perry\UI\Widget\Text('Hello World'), ['Hello World']];
    $specs['Button'] = [new Perry\UI\Widget\Button('Click'), ['Click']];
    $specs['VStack'] = [new Perry\UI\Widget\VStack(new Perry\UI\Widget\Text('A')), ['A']];
    $specs['HStack'] = [new Perry\UI\Widget\HStack(new Perry\UI\Widget\Text('B')), ['B']];
    $specs['Spacer'] = [new Perry\UI\Widget\Spacer(), null];
    $specs['Image'] = [new Perry\UI\Widget\Image('photo.png'), ['photo']];
    $specs['ScrollView'] = [new Perry\UI\Widget\ScrollView(new Perry\UI\Widget\Text('S')), ['S']];
    $specs['TextInput'] = [new Perry\UI\Widget\TextInput(StateId::next(), 'Enter...'), ['Enter']];
    $specs['TextEditor'] = [new Perry\UI\Widget\TextEditor(new Binding('val', '')), null];
    $specs['Toggle'] = [new Perry\UI\Widget\Toggle('Dark Mode'), ['Dark']];
    $specs['Slider'] = [new Perry\UI\Widget\Slider(new Binding('sv', 50.0)), null];
    $specs['ListWidget'] = [new Perry\UI\Widget\ListWidget(new Perry\UI\Widget\Text('Item')), ['Item']];
    $specs['NavigationView'] = [new Perry\UI\Widget\NavigationView(new Perry\UI\Widget\VStack(new Perry\UI\Widget\Text('Page'))), ['Page']];
    $specs['TabView'] = [new Perry\UI\Widget\TabView(new Perry\UI\Widget\VStack(new Perry\UI\Widget\Text('Tab 1'))), ['Tab 1']];
    $specs['WebView'] = [new Perry\UI\Widget\WebView('<p>hello</p>'), ['hello']];
    foreach ($specs as $label => [$w, $kws]) {
        $out = $b->generate($w);
        expect(strlen($out))->toBeGreaterThan(0, "SwiftUI $label output is empty");
        if ($kws !== null) {
            foreach ($kws as $kw) {
                $this->assertStringContainsString($kw, $out, "SwiftUI $label missing: $kw");
            }
        }
    }
});

test('Compose generates all 16 widgets', function () {
    $b = backendGet('compose');
    $specs['Text'] = [new Perry\UI\Widget\Text('Hello World'), ['Hello World']];
    $specs['Button'] = [new Perry\UI\Widget\Button('Click'), ['Click']];
    $specs['VStack'] = [new Perry\UI\Widget\VStack(new Perry\UI\Widget\Text('A')), ['A']];
    $specs['HStack'] = [new Perry\UI\Widget\HStack(new Perry\UI\Widget\Text('B')), ['B']];
    $specs['Spacer'] = [new Perry\UI\Widget\Spacer(), null];
    $specs['Image'] = [new Perry\UI\Widget\Image('photo.png'), ['photo']];
    $specs['ScrollView'] = [new Perry\UI\Widget\ScrollView(new Perry\UI\Widget\Text('S')), ['S']];
    $specs['TextInput'] = [new Perry\UI\Widget\TextInput(StateId::next(), 'Enter...'), ['Enter']];
    $specs['TextEditor'] = [new Perry\UI\Widget\TextEditor(new Binding('val', '')), null];
    $specs['Toggle'] = [new Perry\UI\Widget\Toggle('Dark Mode'), ['Dark']];
    $specs['Slider'] = [new Perry\UI\Widget\Slider(new Binding('sv', 50.0)), null];
    $specs['ListWidget'] = [new Perry\UI\Widget\ListWidget(new Perry\UI\Widget\Text('Item')), ['Item']];
    $specs['NavigationView'] = [new Perry\UI\Widget\NavigationView(new Perry\UI\Widget\VStack(new Perry\UI\Widget\Text('Page'))), ['Page']];
    $specs['TabView'] = [new Perry\UI\Widget\TabView(new Perry\UI\Widget\VStack(new Perry\UI\Widget\Text('Tab 1'))), ['Tab 1']];
    $specs['WebView'] = [new Perry\UI\Widget\WebView('<p>hello</p>'), ['hello']];
    foreach ($specs as $label => [$w, $kws]) {
        $out = $b->generate($w);
        expect(strlen($out))->toBeGreaterThan(0, "Compose $label output is empty");
        if ($kws !== null) {
            foreach ($kws as $kw) {
                $this->assertStringContainsString($kw, $out, "Compose $label missing: $kw");
            }
        }
    }
});

test('AndroidXml generates all 16 widgets', function () {
    $b = backendGet('android-xml');
    $specs['Text'] = [new Perry\UI\Widget\Text('Hello World'), ['Hello World']];
    $specs['Button'] = [new Perry\UI\Widget\Button('Click'), ['Click']];
    $specs['VStack'] = [new Perry\UI\Widget\VStack(new Perry\UI\Widget\Text('A')), ['A']];
    $specs['HStack'] = [new Perry\UI\Widget\HStack(new Perry\UI\Widget\Text('B')), ['B']];
    $specs['Spacer'] = [new Perry\UI\Widget\Spacer(), null];
    $specs['Image'] = [new Perry\UI\Widget\Image('photo.png'), ['photo']];
    $specs['ScrollView'] = [new Perry\UI\Widget\ScrollView(new Perry\UI\Widget\Text('S')), ['S']];
    $specs['TextInput'] = [new Perry\UI\Widget\TextInput(StateId::next(), 'Enter...'), ['Enter']];
    $specs['TextEditor'] = [new Perry\UI\Widget\TextEditor(new Binding('val', '')), null];
    $specs['Toggle'] = [new Perry\UI\Widget\Toggle('Dark Mode'), ['Dark']];
    $specs['Slider'] = [new Perry\UI\Widget\Slider(new Binding('sv', 50.0)), null];
    $specs['ListWidget'] = [new Perry\UI\Widget\ListWidget(new Perry\UI\Widget\Text('Item')), ['Item']];
    $specs['NavigationView'] = [new Perry\UI\Widget\NavigationView(new Perry\UI\Widget\VStack(new Perry\UI\Widget\Text('Page'))), ['Page']];
    $specs['TabView'] = [new Perry\UI\Widget\TabView(new Perry\UI\Widget\VStack(new Perry\UI\Widget\Text('Tab 1'))), ['Tab 1']];
    $specs['WebView'] = [new Perry\UI\Widget\WebView('<p>hello</p>'), ['WebView']];
    foreach ($specs as $label => [$w, $kws]) {
        $out = $b->generate($w);
        expect(strlen($out))->toBeGreaterThan(0, "AndroidXml $label output is empty");
        if ($kws !== null) {
            foreach ($kws as $kw) {
                $this->assertStringContainsString($kw, $out, "AndroidXml $label missing: $kw");
            }
        }
    }
});

test('Gtk4 generates all 16 widgets', function () {
    $b = backendGet('gtk4');
    $specs['Text'] = [new Perry\UI\Widget\Text('Hello World'), ['Hello World']];
    $specs['Button'] = [new Perry\UI\Widget\Button('Click'), ['Click']];
    $specs['VStack'] = [new Perry\UI\Widget\VStack(new Perry\UI\Widget\Text('A')), ['A']];
    $specs['HStack'] = [new Perry\UI\Widget\HStack(new Perry\UI\Widget\Text('B')), ['B']];
    $specs['Spacer'] = [new Perry\UI\Widget\Spacer(), null];
    $specs['Image'] = [new Perry\UI\Widget\Image('photo.png'), ['photo']];
    $specs['ScrollView'] = [new Perry\UI\Widget\ScrollView(new Perry\UI\Widget\Text('S')), ['S']];
    $specs['TextInput'] = [new Perry\UI\Widget\TextInput(StateId::next(), 'Enter...'), ['Enter']];
    $specs['TextEditor'] = [new Perry\UI\Widget\TextEditor(new Binding('val', '')), null];
    $specs['Toggle'] = [new Perry\UI\Widget\Toggle('Dark Mode'), ['Dark']];
    $specs['Slider'] = [new Perry\UI\Widget\Slider(new Binding('sv', 50.0)), null];
    $specs['ListWidget'] = [new Perry\UI\Widget\ListWidget(new Perry\UI\Widget\Text('Item')), ['Item']];
    $specs['NavigationView'] = [new Perry\UI\Widget\NavigationView(new Perry\UI\Widget\VStack(new Perry\UI\Widget\Text('Page'))), ['Page']];
    $specs['TabView'] = [new Perry\UI\Widget\TabView(new Perry\UI\Widget\VStack(new Perry\UI\Widget\Text('Tab 1'))), ['Tab 1']];
    $specs['WebView'] = [new Perry\UI\Widget\WebView('<p>hello</p>'), ['GtkWebView']];
    foreach ($specs as $label => [$w, $kws]) {
        $out = $b->generate($w);
        expect(strlen($out))->toBeGreaterThan(0, "Gtk4 $label output is empty");
        if ($kws !== null) {
            foreach ($kws as $kw) {
                $this->assertStringContainsString($kw, $out, "Gtk4 $label missing: $kw");
            }
        }
    }
});

test('WinUI generates all 16 widgets', function () {
    $b = backendGet('winui');
    $specs['Text'] = [new Perry\UI\Widget\Text('Hello World'), ['Hello World']];
    $specs['Button'] = [new Perry\UI\Widget\Button('Click'), ['Click']];
    $specs['VStack'] = [new Perry\UI\Widget\VStack(new Perry\UI\Widget\Text('A')), ['A']];
    $specs['HStack'] = [new Perry\UI\Widget\HStack(new Perry\UI\Widget\Text('B')), ['B']];
    $specs['Spacer'] = [new Perry\UI\Widget\Spacer(), null];
    $specs['Image'] = [new Perry\UI\Widget\Image('photo.png'), ['photo']];
    $specs['ScrollView'] = [new Perry\UI\Widget\ScrollView(new Perry\UI\Widget\Text('S')), ['S']];
    $specs['TextInput'] = [new Perry\UI\Widget\TextInput(StateId::next(), 'Enter...'), ['Enter']];
    $specs['TextEditor'] = [new Perry\UI\Widget\TextEditor(new Binding('val', '')), null];
    $specs['Toggle'] = [new Perry\UI\Widget\Toggle('Dark Mode'), ['Dark']];
    $specs['Slider'] = [new Perry\UI\Widget\Slider(new Binding('sv', 50.0)), null];
    $specs['ListWidget'] = [new Perry\UI\Widget\ListWidget(new Perry\UI\Widget\Text('Item')), ['Item']];
    $specs['NavigationView'] = [new Perry\UI\Widget\NavigationView(new Perry\UI\Widget\VStack(new Perry\UI\Widget\Text('Page'))), ['Page']];
    $specs['TabView'] = [new Perry\UI\Widget\TabView(new Perry\UI\Widget\VStack(new Perry\UI\Widget\Text('Tab 1'))), ['Tab 1']];
    $specs['WebView'] = [new Perry\UI\Widget\WebView('<p>hello</p>'), ['WebView2']];
    foreach ($specs as $label => [$w, $kws]) {
        $out = $b->generate($w);
        expect(strlen($out))->toBeGreaterThan(0, "WinUI $label output is empty");
        if ($kws !== null) {
            foreach ($kws as $kw) {
                $this->assertStringContainsString($kw, $out, "WinUI $label missing: $kw");
            }
        }
    }
});

test('Html generates all 16 widgets', function () {
    $b = backendGet('html');
    $specs['Text'] = [new Perry\UI\Widget\Text('Hello World'), ['Hello World']];
    $specs['Button'] = [new Perry\UI\Widget\Button('Click'), ['Click']];
    $specs['VStack'] = [new Perry\UI\Widget\VStack(new Perry\UI\Widget\Text('A')), ['A']];
    $specs['HStack'] = [new Perry\UI\Widget\HStack(new Perry\UI\Widget\Text('B')), ['B']];
    $specs['Spacer'] = [new Perry\UI\Widget\Spacer(), null];
    $specs['Image'] = [new Perry\UI\Widget\Image('photo.png'), ['photo']];
    $specs['ScrollView'] = [new Perry\UI\Widget\ScrollView(new Perry\UI\Widget\Text('S')), ['S']];
    $specs['TextInput'] = [new Perry\UI\Widget\TextInput(StateId::next(), 'Enter...'), ['Enter']];
    $specs['TextEditor'] = [new Perry\UI\Widget\TextEditor(new Binding('val', '')), null];
    $specs['Toggle'] = [new Perry\UI\Widget\Toggle('Dark Mode'), ['Dark']];
    $specs['Slider'] = [new Perry\UI\Widget\Slider(new Binding('sv', 50.0)), null];
    $specs['ListWidget'] = [new Perry\UI\Widget\ListWidget(new Perry\UI\Widget\Text('Item')), ['Item']];
    $specs['NavigationView'] = [new Perry\UI\Widget\NavigationView(new Perry\UI\Widget\VStack(new Perry\UI\Widget\Text('Page'))), ['Page']];
    $specs['TabView'] = [new Perry\UI\Widget\TabView(new Perry\UI\Widget\VStack(new Perry\UI\Widget\Text('Tab 1'))), ['Tab 1']];
    $specs['WebView'] = [new Perry\UI\Widget\WebView('<p>hello</p>'), ['hello']];
    foreach ($specs as $label => [$w, $kws]) {
        $out = $b->generate($w);
        expect(strlen($out))->toBeGreaterThan(0, "Html $label output is empty");
        if ($kws !== null) {
            foreach ($kws as $kw) {
                $this->assertStringContainsString($kw, $out, "Html $label missing: $kw");
            }
        }
    }
});

// ---------------------------------------------------------------------------
// SwiftUI specific structure
// ---------------------------------------------------------------------------

test('SwiftUI AppContainer generates state bindings and frame', function () {
    $backend = backendGet('swiftui');
    $binding = new Binding('count', 0);
    $content = new Perry\UI\Widget\VStack(
        new Perry\UI\Widget\Text($binding),
    );
    $app = new Perry\UI\Widget\AppContainer($content, 400, 300, $binding);
    $output = $backend->generate($app);
    expect($output)->toContain('@State private var count');
    expect($output)->toContain('.frame(width: 400, height: 300)');
});

test('SwiftUI WebView includes WebKit import', function () {
    $backend = backendGet('swiftui');
    $wv = new Perry\UI\Widget\WebView('<b>hi</b>');
    $output = $backend->generate($wv);
    expect($output)->toContain('import WebKit');
    expect($output)->toContain('WKWebView');
});

test('SwiftUI TextEditor generates binding syntax', function () {
    $backend = backendGet('swiftui');
    $te = new Perry\UI\Widget\TextEditor(new Binding('notes', ''));
    $output = $backend->generate($te);
    expect($output)->toContain('TextEditor(text: $notes)');
});

// ---------------------------------------------------------------------------
// ArkTS specific structure
// ---------------------------------------------------------------------------

test('ArkTS AppContainer generates state bindings and frame', function () {
    $backend = backendGet('arkts');
    $binding = new Binding('count', 0);
    $content = new Perry\UI\Widget\VStack(
        new Perry\UI\Widget\Text($binding),
    );
    $app = new Perry\UI\Widget\AppContainer($content, 400, 300, $binding);
    $output = $backend->generate($app);
    expect($output)->toContain('@State count: number = 0;');
    expect($output)->toContain('.width(400)');
    expect($output)->toContain('.height(300)');
    expect($output)->toContain('@Component');
    expect($output)->toContain('build()');
});

test('ArkTS WebView generates Web component', function () {
    $backend = backendGet('arkts');
    $wv = new Perry\UI\Widget\WebView('<b>hi</b>');
    $output = $backend->generate($wv);
    expect($output)->toContain('Web({');
    expect($output)->toContain('<b>hi</b>');
});

test('ArkTS TextEditor generates TextArea', function () {
    $backend = backendGet('arkts');
    $te = new Perry\UI\Widget\TextEditor(new Binding('notes', ''));
    $output = $backend->generate($te);
    expect($output)->toContain('TextArea');
});

// ---------------------------------------------------------------------------
// Compose specific structure
// ---------------------------------------------------------------------------

test('Compose generates Kotlin imports', function () {
    $backend = backendGet('compose');
    $output = $backend->generate(new Perry\UI\Widget\Text('Hi'));
    expect($output)->toContain('import androidx.compose');
    expect($output)->toContain('setContent');
    expect($output)->toContain('Text(text = "Hi")');
});

test('Compose AppContainer generates remember state', function () {
    $backend = backendGet('compose');
    $binding = new Binding('name', 'World');
    $app = new Perry\UI\Widget\AppContainer(
        new Perry\UI\Widget\VStack(new Perry\UI\Widget\Text($binding)),
        400, 600, $binding
    );
    $output = $backend->generate($app);
    expect($output)->toContain('var name by remember');
    expect($output)->toContain('.size(width = 400.dp, height = 600.dp)');
});

test('Compose WebView uses AndroidView', function () {
    $backend = backendGet('compose');
    $output = $backend->generate(new Perry\UI\Widget\WebView('<p>test</p>'));
    expect($output)->toContain('AndroidView');
    expect($output)->toContain('android.webkit.WebView');
});

test('Compose TextEditor uses OutlinedTextField', function () {
    $backend = backendGet('compose');
    $output = $backend->generate(new Perry\UI\Widget\TextEditor(new Binding('bio', '')));
    expect($output)->toContain('OutlinedTextField');
});

// ---------------------------------------------------------------------------
// Android XML specific structure
// ---------------------------------------------------------------------------

test('AndroidXml generates XML markup with LinearLayout', function () {
    $backend = backendGet('android-xml');
    $output = $backend->generate(new Perry\UI\Widget\Text('Hello'));
    expect($output)->toContain('<?xml version="1.0" encoding="utf-8"?>');
    expect($output)->toContain('<LinearLayout');
});

// ---------------------------------------------------------------------------
// GTK4 specific structure
// ---------------------------------------------------------------------------

test('Gtk4 generates XML interface with GtkApplicationWindow', function () {
    $backend = backendGet('gtk4');
    $output = $backend->generate(new Perry\UI\Widget\Text('Hello'));
    expect($output)->toContain('<?xml version="1.0"');
    expect($output)->toContain('GtkApplicationWindow');
});

test('Gtk4 WebView generates GtkWebView', function () {
    $backend = backendGet('gtk4');
    $output = $backend->generate(new Perry\UI\Widget\WebView('<b>bold</b>'));
    expect($output)->toContain('GtkWebView');
});

test('Gtk4 TextEditor generates GtkTextView', function () {
    $backend = backendGet('gtk4');
    $output = $backend->generate(new Perry\UI\Widget\TextEditor(new Binding('desc', '')));
    expect($output)->toContain('GtkTextView');
});

// ---------------------------------------------------------------------------
// WinUI specific structure
// ---------------------------------------------------------------------------

test('WinUI generates XAML with Window declaration', function () {
    $backend = backendGet('winui');
    $output = $backend->generate(new Perry\UI\Widget\Text('Hello'));
    expect($output)->toContain('<Window x:Class="PerryApp.MainWindow"');
});

test('WinUI WebView2 generates proper control', function () {
    $backend = backendGet('winui');
    $output = $backend->generate(new Perry\UI\Widget\WebView('<p>test</p>'));
    expect($output)->toContain('WebView2');
});

test('WinUI TextEditor generates TextBox with AcceptsReturn', function () {
    $backend = backendGet('winui');
    $output = $backend->generate(new Perry\UI\Widget\TextEditor(new Binding('notes', '')));
    expect($output)->toContain('TextBox');
    expect($output)->toContain('AcceptsReturn');
});

// ---------------------------------------------------------------------------
// HTML specific structure
// ---------------------------------------------------------------------------

test('Html generates HTML5 document structure', function () {
    $backend = backendGet('html');
    $output = $backend->generate(new Perry\UI\Widget\Text('Hello'));
    expect($output)->toContain('<!DOCTYPE html>');
    expect($output)->toContain('<html lang="en">');
    expect($output)->toContain('<style>');
});

test('Html WebView generates iframe with srcdoc', function () {
    $backend = backendGet('html');
    $output = $backend->generate(new Perry\UI\Widget\WebView('<p>hello</p>'));
    expect($output)->toContain('iframe');
    expect($output)->toContain('srcdoc');
});

test('Html TextEditor generates textarea', function () {
    $backend = backendGet('html');
    $output = $backend->generate(new Perry\UI\Widget\TextEditor(new Binding('notes', '')));
    expect($output)->toContain('<textarea');
});

// ---------------------------------------------------------------------------
// Container nesting
// ---------------------------------------------------------------------------

test('ScrollView nests VStack across all backends', function () {
    $scroll = new Perry\UI\Widget\ScrollView(
        new Perry\UI\Widget\VStack(
            new Perry\UI\Widget\Text('Item 1'),
            new Perry\UI\Widget\Text('Item 2')
        )
    );
    $factory = new CodegenFactory();
    foreach ($factory->available() as $name) {
        $b = $factory->get($name);
        $out = $b->generate($scroll);
        expect(strlen($out))->toBeGreaterThan(0, "$name scroll nesting empty");
    }
});

test('TabView generates structure across all backends', function () {
    $tabView = new Perry\UI\Widget\TabView(
        new Perry\UI\Widget\VStack(new Perry\UI\Widget\Text('Tab A')),
        new Perry\UI\Widget\VStack(new Perry\UI\Widget\Text('Tab B'))
    );
    $factory = new CodegenFactory();
    foreach ($factory->available() as $name) {
        $b = $factory->get($name);
        $out = $b->generate($tabView);
        expect(strlen($out))->toBeGreaterThan(0, "$name TabView empty");
    }
});

test('NavigationView generates structure across all backends', function () {
    $nav = new Perry\UI\Widget\NavigationView(
        new Perry\UI\Widget\VStack(new Perry\UI\Widget\Text('Screen 1')),
        new Perry\UI\Widget\VStack(new Perry\UI\Widget\Text('Screen 2'))
    );
    $factory = new CodegenFactory();
    foreach ($factory->available() as $name) {
        $b = $factory->get($name);
        $out = $b->generate($nav);
        expect(strlen($out))->toBeGreaterThan(0, "$name NavView empty");
    }
});

test('ListWidget generates structure across all backends', function () {
    $list = new Perry\UI\Widget\ListWidget(
        new Perry\UI\Widget\Text('Row 1'),
        new Perry\UI\Widget\Text('Row 2')
    );
    $factory = new CodegenFactory();
    foreach ($factory->available() as $name) {
        $b = $factory->get($name);
        $out = $b->generate($list);
        expect(strlen($out))->toBeGreaterThan(0, "$name ListWidget empty");
    }
});

// ---------------------------------------------------------------------------
// Styling
// ---------------------------------------------------------------------------

test('Styled Text generates SwiftUI modifiers', function () {
    $style = (new Style())
        ->set(StyleProperty::FontSize, 24)
        ->set(StyleProperty::ForegroundColor, '#ff0000')
        ->set(StyleProperty::Padding, 16);
    $text = (new Perry\UI\Widget\Text('Styled'))->style($style);
    $output = backendGet('swiftui')->generate($text);
    expect($output)->toContain('font(.system(size: 24))');
    expect($output)->toContain('foregroundColor');
    expect($output)->toContain('padding(16)');
});

test('Styled Button generates Android XML dimensions', function () {
    $style = (new Style())
        ->set(StyleProperty::Width, 100)
        ->set(StyleProperty::Height, 50)
        ->set(StyleProperty::CornerRadius, 8);
    $button = (new Perry\UI\Widget\Button('Tap'))->style($style);
    $output = backendGet('android-xml')->generate($button);
    expect($output)->toContain('android:layout_width="100dp"');
    expect($output)->toContain('android:layout_height="50dp"');
});

test('ArkTS generates all 16 widgets', function () {
    $b = backendGet('arkts');
    $specs['Text'] = [new Perry\UI\Widget\Text('Hello World'), ['Hello World', 'Text(']];
    $specs['Button'] = [new Perry\UI\Widget\Button('Click'), ['Button(']];
    $specs['VStack'] = [new Perry\UI\Widget\VStack(new Perry\UI\Widget\Text('A')), ['Column()']];
    $specs['HStack'] = [new Perry\UI\Widget\HStack(new Perry\UI\Widget\Text('B')), ['Row()']];
    $specs['Spacer'] = [new Perry\UI\Widget\Spacer(), ['Blank()']];
    $specs['Image'] = [new Perry\UI\Widget\Image('photo.png'), ['Image({']];
    $specs['ScrollView'] = [new Perry\UI\Widget\ScrollView(new Perry\UI\Widget\Text('S')), ['Scroll()']];
    $specs['TextInput'] = [new Perry\UI\Widget\TextInput(StateId::next(), 'Enter...'), ['TextInput']];
    $specs['TextEditor'] = [new Perry\UI\Widget\TextEditor(new Binding('val', '')), ['TextArea']];
    $specs['Toggle'] = [new Perry\UI\Widget\Toggle('Dark Mode'), ['Toggle', 'ToggleType']];
    $specs['Slider'] = [new Perry\UI\Widget\Slider(new Binding('sv', 50.0)), ['Slider']];
    $specs['ListWidget'] = [new Perry\UI\Widget\ListWidget(new Perry\UI\Widget\Text('Item')), ['List()']];
    $specs['NavigationView'] = [new Perry\UI\Widget\NavigationView(new Perry\UI\Widget\VStack(new Perry\UI\Widget\Text('Page'))), ['NavDestination']];
    $specs['TabView'] = [new Perry\UI\Widget\TabView(new Perry\UI\Widget\VStack(new Perry\UI\Widget\Text('Tab 1'))), ['Tabs()']];
    $specs['WebView'] = [new Perry\UI\Widget\WebView('<p>hello</p>'), ['Web({']];
    foreach ($specs as $label => [$w, $kws]) {
        $out = $b->generate($w);
        expect(strlen($out))->toBeGreaterThan(0, "ArkTS $label output is empty");
        if ($kws !== null) {
            foreach ($kws as $kw) {
                $this->assertStringContainsString($kw, $out, "ArkTS $label missing: $kw");
            }
        }
    }
});

test('Glance generates all 16 widgets', function () {
    $b = backendGet('glance');
    $specs['Text'] = [new Perry\UI\Widget\Text('Hello World'), ['Hello World']];
    $specs['Button'] = [new Perry\UI\Widget\Button('Click'), ['Click']];
    $specs['VStack'] = [new Perry\UI\Widget\VStack(new Perry\UI\Widget\Text('A')), ['Column']];
    $specs['HStack'] = [new Perry\UI\Widget\HStack(new Perry\UI\Widget\Text('B')), ['Row']];
    $specs['Spacer'] = [new Perry\UI\Widget\Spacer(), ['Spacer']];
    $specs['Image'] = [new Perry\UI\Widget\Image('photo.png'), ['ImageProvider']];
    $specs['ScrollView'] = [new Perry\UI\Widget\ScrollView(new Perry\UI\Widget\Text('S')), ['Column']];
    $specs['TextInput'] = [new Perry\UI\Widget\TextInput(StateId::next(), 'Enter...'), ['Enter']];
    $specs['TextEditor'] = [new Perry\UI\Widget\TextEditor(new Binding('val', '')), ['TextEditor']];
    $specs['Toggle'] = [new Perry\UI\Widget\Toggle('Dark Mode'), ['Dark']];
    $specs['Slider'] = [new Perry\UI\Widget\Slider(new Binding('sv', 50.0)), ['Slider']];
    $specs['ListWidget'] = [new Perry\UI\Widget\ListWidget(new Perry\UI\Widget\Text('Item')), ['LazyColumn']];
    $specs['NavigationView'] = [new Perry\UI\Widget\NavigationView(new Perry\UI\Widget\VStack(new Perry\UI\Widget\Text('Page'))), ['NavigationView']];
    $specs['TabView'] = [new Perry\UI\Widget\TabView(new Perry\UI\Widget\VStack(new Perry\UI\Widget\Text('Tab 1'))), ['TabView']];
    $specs['WebView'] = [new Perry\UI\Widget\WebView('<p>hello</p>'), ['WebView']];
    foreach ($specs as $label => [$w, $kws]) {
        $out = $b->generate($w);
        expect(strlen($out))->toBeGreaterThan(0, "Glance $label output is empty");
        if ($kws !== null) {
            foreach ($kws as $kw) {
                $this->assertStringContainsString($kw, $out, "Glance $label missing: $kw");
            }
        }
    }
});

test('Glance AppContainer generates Kotlin class with bindings', function () {
    $b = backendGet('glance');
    $app = new Perry\UI\Widget\AppContainer(
        new Perry\UI\Widget\Text('Hello'),
        300, 200,
        new Binding('count', 0)
    );
    $out = $b->generate($app);
    expect($out)->toContain('class PerryWidget');
    expect($out)->toContain('class PerryWidgetReceiver');
    expect($out)->toContain('GlanceAppWidget');
    // Must emit binding as mutable state
    expect($out)->toContain('count by remember');
    expect($out)->toContain('mutableStateOf(0)');
    // Must emit window dimensions
    expect($out)->toContain('.width(300.dp)');
    expect($out)->toContain('.height(200.dp)');
});

test('WearTiles generates all 16 widgets', function () {
    $b = backendGet('wear-tiles');
    $specs['Text'] = [new Perry\UI\Widget\Text('Hello World'), ['Hello World']];
    $specs['Button'] = [new Perry\UI\Widget\Button('Click'), ['Click']];
    $specs['VStack'] = [new Perry\UI\Widget\VStack(new Perry\UI\Widget\Text('A')), ['Column']];
    $specs['HStack'] = [new Perry\UI\Widget\HStack(new Perry\UI\Widget\Text('B')), ['Row']];
    $specs['Spacer'] = [new Perry\UI\Widget\Spacer(), ['Spacer']];
    $specs['Image'] = [new Perry\UI\Widget\Image('photo.png'), ['Image']];
    $specs['ScrollView'] = [new Perry\UI\Widget\ScrollView(new Perry\UI\Widget\Text('S')), ['Scroll']];
    $specs['TextInput'] = [new Perry\UI\Widget\TextInput(StateId::next(), 'Enter...'), ['Enter']];
    $specs['TextEditor'] = [new Perry\UI\Widget\TextEditor(new Binding('val', '')), ['TextEditor']];
    $specs['Toggle'] = [new Perry\UI\Widget\Toggle('Dark Mode'), ['Dark']];
    $specs['Slider'] = [new Perry\UI\Widget\Slider(new Binding('sv', 50.0)), ['Slider']];
    $specs['ListWidget'] = [new Perry\UI\Widget\ListWidget(new Perry\UI\Widget\Text('Item')), ['Column']];
    $specs['NavigationView'] = [new Perry\UI\Widget\NavigationView(new Perry\UI\Widget\VStack(new Perry\UI\Widget\Text('Page'))), ['Navigation']];
    $specs['TabView'] = [new Perry\UI\Widget\TabView(new Perry\UI\Widget\VStack(new Perry\UI\Widget\Text('Tab 1'))), ['TabView']];
    $specs['WebView'] = [new Perry\UI\Widget\WebView('<p>hello</p>'), ['WebView']];
    foreach ($specs as $label => [$w, $kws]) {
        $out = $b->generate($w);
        expect(strlen($out))->toBeGreaterThan(0, "WearTiles $label output is empty");
        if ($kws !== null) {
            foreach ($kws as $kw) {
                $this->assertStringContainsString($kw, $out, "WearTiles $label missing: $kw");
            }
        }
    }
});

test('WearTiles AppContainer generates TileService class with bindings', function () {
    $b = backendGet('wear-tiles');
    $app = new Perry\UI\Widget\AppContainer(
        new Perry\UI\Widget\Text('Hello'),
        320, 180,
        new Binding('count', 0)
    );
    $out = $b->generate($app);
    expect($out)->toContain('class PerryTile');
    expect($out)->toContain('TileService');
    expect($out)->toContain('onTileRequest');
    // Must emit binding as field
    expect($out)->toContain('var count: Int = 0');
    // Must emit window dimensions
    expect($out)->toContain('.setWidth(320)');
    expect($out)->toContain('.setHeight(180)');
});

// ---------------------------------------------------------------------------
// Flutter backend tests
// ---------------------------------------------------------------------------

test('Flutter generates all 16 widgets', function () {
    $b = backendGet('flutter');
    $specs['Text'] = [new Perry\UI\Widget\Text('Hello World'), ['Hello World']];
    $specs['Button'] = [new Perry\UI\Widget\Button('Click'), ['Click']];
    $specs['VStack'] = [new Perry\UI\Widget\VStack(new Perry\UI\Widget\Text('A')), ['Column']];
    $specs['HStack'] = [new Perry\UI\Widget\HStack(new Perry\UI\Widget\Text('B')), ['Row']];
    $specs['Spacer'] = [new Perry\UI\Widget\Spacer(), ['Spacer']];
    $specs['Image'] = [new Perry\UI\Widget\Image('photo.png'), ['photo']];
    $specs['ScrollView'] = [new Perry\UI\Widget\ScrollView(new Perry\UI\Widget\Text('S')), ['SingleChildScrollView']];
    $specs['TextInput'] = [new Perry\UI\Widget\TextInput(StateId::next(), 'Enter...'), ['Enter']];
    $specs['TextEditor'] = [new Perry\UI\Widget\TextEditor(new Binding('val', '')), ['TextField']];
    $specs['Toggle'] = [new Perry\UI\Widget\Toggle('Dark Mode'), ['Dark', 'Switch']];
    $specs['Slider'] = [new Perry\UI\Widget\Slider(new Binding('sv', 50.0)), ['Slider']];
    $specs['ListWidget'] = [new Perry\UI\Widget\ListWidget(new Perry\UI\Widget\Text('Item')), ['ListView']];
    $specs['NavigationView'] = [new Perry\UI\Widget\NavigationView(new Perry\UI\Widget\VStack(new Perry\UI\Widget\Text('Page'))), ['Navigation']];
    $specs['TabView'] = [new Perry\UI\Widget\TabView(new Perry\UI\Widget\VStack(new Perry\UI\Widget\Text('Tab 1'))), ['TabBarView']];
    $specs['WebView'] = [new Perry\UI\Widget\WebView('<p>hello</p>'), ['WebView']];
    foreach ($specs as $label => [$w, $kws]) {
        $out = $b->generate($w);
        expect(strlen($out))->toBeGreaterThan(0, "Flutter $label output is empty");
        if ($kws !== null) {
            foreach ($kws as $kw) {
                $this->assertStringContainsString($kw, $out, "Flutter $label missing: $kw");
            }
        }
    }
});

test('Flutter AppContainer generates StatefulWidget class', function () {
    $b = backendGet('flutter');
    $app = new Perry\UI\Widget\AppContainer(
        new Perry\UI\Widget\Text('Hello'),
        null, null,
        new Binding('count', 0)
    );
    $out = $b->generate($app);
    expect($out)->toContain('class PerryApp extends StatefulWidget');
    expect($out)->toContain('_PerryAppState');
    expect($out)->toContain('MaterialApp');
});

// ---------------------------------------------------------------------------
// Full app integration (calculator example via shell)
// ---------------------------------------------------------------------------

test('calculator generates SwiftUI output', function () {
    $out = shell_exec('php examples/calculator.php macos 2>&1');
    expect($out)->toContain('Perry Calculator');
    expect($out)->toContain('import SwiftUI');
});

test('calculator generates HTML output', function () {
    $out = shell_exec('php examples/calculator.php web 2>&1');
    expect($out)->toContain('Perry Calculator');
    expect($out)->toContain('Target: web');
});

test('calculator generates GTK4 output', function () {
    $out = shell_exec('php examples/calculator.php linux 2>&1');
    expect($out)->toContain('Perry Calculator');
    expect($out)->toContain('GtkApplicationWindow');
});

test('calculator generates WinUI output', function () {
    $out = shell_exec('php examples/calculator.php windows 2>&1');
    expect($out)->toContain('Perry Calculator');
    expect($out)->toContain('Window x:Class="PerryApp.MainWindow"');
});

test('calculator generates Compose output', function () {
    $out = shell_exec('php examples/calculator.php compose 2>&1');
    expect($out)->toContain('Perry Calculator');
    expect($out)->toContain('Target: compose');
});

test('calculator generates Wasm output', function () {
    $out = shell_exec('php examples/calculator.php wasm 2>&1');
    expect($out)->toContain('Perry Calculator');
    expect($out)->toContain('Target: wasm');
});

test('calculator generates ArkTS output', function () {
    $out = shell_exec('php examples/calculator.php arkts 2>&1');
    expect($out)->toContain('Perry Calculator');
    expect($out)->toContain('Target: arkts');
});

test('calculator generates Glance output', function () {
    $out = shell_exec('php examples/calculator.php glance 2>&1');
    expect($out)->toContain('Perry Calculator');
    expect($out)->toContain('Target: glance');
});

test('calculator generates WearTiles output', function () {
    $out = shell_exec('php examples/calculator.php wear-tiles 2>&1');
    expect($out)->toContain('Perry Calculator');
    expect($out)->toContain('Target: wear-tiles');
});

test('calculator generates Flutter output', function () {
    $out = shell_exec('php examples/calculator.php flutter 2>&1');
    expect($out)->toContain('Perry Calculator');
    expect($out)->toContain('Target: flutter');
});

// ---------------------------------------------------------------------------
// Boundary cases
// ---------------------------------------------------------------------------

test('empty VStack', function () {
    $factory = new CodegenFactory();
    foreach ($factory->available() as $name) {
        $b = $factory->get($name);
        expect(strlen($b->generate(new Perry\UI\Widget\VStack())))->toBeGreaterThan(0, "$name empty VStack empty");
    }
});

test('Spacer alone', function () {
    $factory = new CodegenFactory();
    foreach ($factory->available() as $name) {
        $b = $factory->get($name);
        expect(strlen($b->generate(new Perry\UI\Widget\Spacer())))->toBeGreaterThan(0, "$name Spacer empty");
    }
});

test('Toggle without binding', function () {
    $factory = new CodegenFactory();
    foreach ($factory->available() as $name) {
        $b = $factory->get($name);
        expect(strlen($b->generate(new Perry\UI\Widget\Toggle('Enable'))))->toBeGreaterThan(0, "$name Toggle empty");
    }
});

test('Slider with defaults', function () {
    $factory = new CodegenFactory();
    foreach ($factory->available() as $name) {
        $b = $factory->get($name);
        $out = $b->generate(new Perry\UI\Widget\Slider(new Binding('val', 50.0)));
        expect(strlen($out))->toBeGreaterThan(0, "$name Slider empty");
    }
});
