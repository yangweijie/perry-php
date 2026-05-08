<?php

declare(strict_types=1);

use Perry\UI\Binding;
use Perry\UI\StateId;
use Perry\UI\Styling\Style;
use Perry\UI\Styling\StyleProperty;
use Perry\Codegen\CodegenFactory;
use Perry\Build\Target;

// ---------------------------------------------------------------------------
// Factory registration
// ---------------------------------------------------------------------------

test('CodegenFactory registers WasmBackend', function () {
    $factory = new CodegenFactory();
    expect($factory->available())->toContain('wasm');
});

test('WasmBackend supports Wasm and Web targets', function () {
    $b = (new CodegenFactory())->get('wasm');
    expect($b->supports(Target::Wasm))->toBeTrue()
        ->and($b->supports(Target::Web))->toBeTrue();
});

test('WasmBackend backend name is wasm', function () {
    $b = (new CodegenFactory())->get('wasm');
    expect($b->name())->toBe('wasm');
});

// ---------------------------------------------------------------------------
// HTML structure
// ---------------------------------------------------------------------------

test('generate produces valid HTML document', function () {
    $b = (new CodegenFactory())->get('wasm');
    $out = $b->generate(new Perry\UI\Widget\Text('Hello WASM'));
    expect($out)->toContain('<!DOCTYPE html>')
        ->and($out)->toContain('<html')
        ->and($out)->toContain('</html>')
        ->and($out)->toContain('<head>')
        ->and($out)->toContain('</head>')
        ->and($out)->toContain('<body>')
        ->and($out)->toContain('</body>')
        ->and($out)->toContain('id="perry-root"');
});

test('generate embeds wasm_runtime.js', function () {
    $b = (new CodegenFactory())->get('wasm');
    $out = $b->generate(new Perry\UI\Widget\Text('Test'));
    expect($out)->toContain('perry_ui_createWidget')
        ->and($out)->toContain('perry_ui_mount');
});

test('generate with AppContainer produces state-synced output', function () {
    $b = (new CodegenFactory())->get('wasm');
    $container = new Perry\UI\Widget\AppContainer(
        new Perry\UI\Widget\Text('init'),
        null,
        null,
        new Binding('count', '0'),
    );
    $out = $b->generate($container);
    expect($out)->toContain('perry_ui_mount');
});

// ---------------------------------------------------------------------------
// 16 widget kinds
// ---------------------------------------------------------------------------

test('Wasm backend generates all 16 widgets', function () {
    $b = (new CodegenFactory())->get('wasm');

    $specs['Text'] = [new Perry\UI\Widget\Text('Hello WASM'), ['span', 'Hello WASM']];
    $specs['Button'] = [new Perry\UI\Widget\Button('Click'), ['button', 'Click']];
    $specs['VStack'] = [new Perry\UI\Widget\VStack(new Perry\UI\Widget\Text('A')), ['perry_ui_addChild', 'perry-flex-col']];
    $specs['HStack'] = [new Perry\UI\Widget\HStack(new Perry\UI\Widget\Text('B')), ['perry_ui_addChild', 'perry-flex-row']];
    $specs['Spacer'] = [new Perry\UI\Widget\Spacer(), ['perry-spacer']];
    $specs['Image'] = [new Perry\UI\Widget\Image('photo.png'), ['perry_ui_setAttribute']];
    $specs['ScrollView'] = [new Perry\UI\Widget\ScrollView(new Perry\UI\Widget\Text('S')), ['perry-scroll']];
    $specs['TextInput'] = [new Perry\UI\Widget\TextInput(StateId::next(), 'Enter...'), ['placeholder']];
    $specs['TextEditor'] = [new Perry\UI\Widget\TextEditor(new Binding('val', '')), ['textarea']];
    $specs['Toggle'] = [new Perry\UI\Widget\Toggle('Dark Mode'), ['checkbox']];
    $specs['Slider'] = [new Perry\UI\Widget\Slider(new Binding('sv', 50.0)), ['range']];
    $specs['ListWidget'] = [new Perry\UI\Widget\ListWidget(new Perry\UI\Widget\Text('Item')), ['perry-list']];
    $specs['NavigationView'] = [new Perry\UI\Widget\NavigationView(new Perry\UI\Widget\VStack(new Perry\UI\Widget\Text('Page'))), ['perry-nav-view']];
    $specs['TabView'] = [new Perry\UI\Widget\TabView(new Perry\UI\Widget\VStack(new Perry\UI\Widget\Text('Tab 1'))), ['perry-tab-view']];
    $specs['WebView'] = [new Perry\UI\Widget\WebView('<p>hello</p>'), ['iframe', 'srcdoc']];

    foreach ($specs as $label => [$w, $kws]) {
        $out = $b->generate($w);
        expect(strlen($out))->toBeGreaterThan(0, "Wasm $label output is empty");
        if ($kws !== null) {
            foreach ($kws as $kw) {
                expect(str_contains($out, $kw))->toBeTrue("Wasm $label missing: $kw");
            }
        }
    }
});

// ---------------------------------------------------------------------------
// JS syntax checks
// ---------------------------------------------------------------------------

test('generated JS has valid variable declarations', function () {
    $b = (new CodegenFactory())->get('wasm');
    $vstack = new Perry\UI\Widget\VStack(
        new Perry\UI\Widget\Text('A'),
        new Perry\UI\Widget\Text('B'),
    );
    $out = $b->generate($vstack);
    // Verify handle assignments
    expect($out)->toMatch('/var w1 = perry_ui_createWidget/')
        ->and($out)->toMatch('/var w2 = perry_ui_createWidget/')
        ->and($out)->toMatch('/var w3 = perry_ui_createWidget/');
});

test('generated JS has correct addChild chains', function () {
    $b = (new CodegenFactory())->get('wasm');
    $vstack = new Perry\UI\Widget\VStack(
        new Perry\UI\Widget\Text('A'),
        new Perry\UI\Widget\Text('B'),
    );
    $out = $b->generate($vstack);
    // w1 is VStack, w2 is Text A, w3 is Text B
    expect($out)->toContain('perry_ui_addChild(w1, w2);')
        ->and($out)->toContain('perry_ui_addChild(w1, w3);');
});

test('generated JS mounts to root element', function () {
    $b = (new CodegenFactory())->get('wasm');
    $out = $b->generate(new Perry\UI\Widget\Text('A'));
    expect($out)->toMatch('/perry_ui_mount\(1\)/');
});

// ---------------------------------------------------------------------------
// Style emission
// ---------------------------------------------------------------------------

test('styles generate perry_ui_setStyle calls', function () {
    $b = (new CodegenFactory())->get('wasm');
    $text = (new Perry\UI\Widget\Text('Styled'))
        ->style(Style::make()
            ->fontSize(24)
            ->foregroundColor('#ff0')
            ->backgroundColor('#333')
            ->padding(12)
            ->cornerRadius(8)
            ->opacity(0.9)
            ->width(200)
            ->height(100)
        );
    $out = $b->generate($text);
    expect($out)->toContain("perry_ui_setStyle(w1, 'font-size', '24px')")
        ->and($out)->toContain("perry_ui_setStyle(w1, 'color', '#ff0')")
        ->and($out)->toContain("perry_ui_setStyle(w1, 'background-color', '#333')")
        ->and($out)->toContain("perry_ui_setStyle(w1, 'padding', '12px')")
        ->and($out)->toContain("perry_ui_setStyle(w1, 'border-radius', '8px')")
        ->and($out)->toContain("perry_ui_setStyle(w1, 'opacity', '0.9')")
        ->and($out)->toContain("perry_ui_setStyle(w1, 'width', '200px')")
        ->and($out)->toContain("perry_ui_setStyle(w1, 'height', '100px')");
});

test('font weight maps correctly', function () {
    $b = (new CodegenFactory())->get('wasm');
    $text = (new Perry\UI\Widget\Text('Bold'))
        ->style(Style::make()->fontWeight('bold'));
    $out = $b->generate($text);
    expect($out)->toContain("font-weight', 'bold'");
});

test('text alignment maps correctly', function () {
    $b = (new CodegenFactory())->get('wasm');
    $text = (new Perry\UI\Widget\Text('Centered'))
        ->style(Style::make()->textAlignment('center'));
    $out = $b->generate($text);
    expect($out)->toContain("text-align', 'center'");
});

// ---------------------------------------------------------------------------
// Styling: borders, padding sides, shadow via generic set
// ---------------------------------------------------------------------------

test('border styles via generic set', function () {
    $b = (new CodegenFactory())->get('wasm');
    $text = (new Perry\UI\Widget\Text('Bordered'))
        ->style(Style::make()
            ->set(\Perry\UI\Styling\StyleProperty::Margin, 16)
            ->border(2, '#f00')
        );
    $out = $b->generate($text);
    expect($out)->toContain("border-width', '2px'")
        ->and($out)->toContain("border-color', '#f00'");
});

test('directional padding via paddingAll', function () {
    $b = (new CodegenFactory())->get('wasm');
    $text = (new Perry\UI\Widget\Text('Padded'))
        ->style(Style::make()
            ->paddingAll(10, 20, 5, 15)
        );
    $out = $b->generate($text);
    expect($out)->toContain("padding-top', '10px'")
        ->and($out)->toContain("padding-bottom', '20px'")
        ->and($out)->toContain("padding-left', '5px'")
        ->and($out)->toContain("padding-right', '15px'");
});

test('shadow style via shadow method', function () {
    $b = (new CodegenFactory())->get('wasm');
    $text = (new Perry\UI\Widget\Text('Shadow'))
        ->style(Style::make()
            ->shadow('#000', 8, 2, 4)
        );
    $out = $b->generate($text);
    expect($out)->toContain('box-shadow')
        ->and($out)->toContain('2px')
        ->and($out)->toContain('4px');
});

test('min/max dimensions via generic set', function () {
    $b = (new CodegenFactory())->get('wasm');
    $text = (new Perry\UI\Widget\Text('Sized'))
        ->style(Style::make()
            ->set(\Perry\UI\Styling\StyleProperty::MinWidth, 100)
            ->set(\Perry\UI\Styling\StyleProperty::MinHeight, 50)
            ->set(\Perry\UI\Styling\StyleProperty::MaxWidth, 500)
            ->set(\Perry\UI\Styling\StyleProperty::MaxHeight, 300)
        );
    $out = $b->generate($text);
    expect($out)->toContain("min-width', '100px'")
        ->and($out)->toContain("min-height', '50px'")
        ->and($out)->toContain("max-width', '500px'")
        ->and($out)->toContain("max-height', '300px'");
});

test('line spacing via generic set', function () {
    $b = (new CodegenFactory())->get('wasm');
    $text = (new Perry\UI\Widget\Text('Spaced'))
        ->style(Style::make()
            ->set(\Perry\UI\Styling\StyleProperty::LineSpacing, 24)
        );
    $out = $b->generate($text);
    expect($out)->toContain("line-height', '24px'");
});

// ---------------------------------------------------------------------------
// Actions
// ---------------------------------------------------------------------------

test('action functions are generated for button clicks', function () {
    $b = (new CodegenFactory())->get('wasm');
    $btn = new Perry\UI\Widget\Button('Save', function () {
        echo 'saved';
    });
    $out = $b->generate($btn);
    expect($out)->toContain('perry_ui_onClick')
        ->and(str_contains($out, 'action_0'))->toBeTrue();
});

test('setValue action via Action object in constructor', function () {
    $b = (new CodegenFactory())->get('wasm');
    $action = new \Perry\UI\Action(
        \Perry\UI\ActionType::SetValue,
        target: new Binding('count', '0'),
        value: '42',
    );
    $btn = new Perry\UI\Widget\Button('Set', $action);
    $out = $b->generate($btn);
    expect($out)->toContain('count.value = \'42\'');
});

test('custom Script action via Action object in constructor', function () {
    $b = (new CodegenFactory())->get('wasm');
    $action = new \Perry\UI\Action(
        \Perry\UI\ActionType::Custom,
        customCode: 'alert("hello");',
    );
    $btn = new Perry\UI\Widget\Button('Custom', $action);
    $out = $b->generate($btn);
    expect($out)->toContain('alert("hello")');
});

// ---------------------------------------------------------------------------
// Text with binding / state
// ---------------------------------------------------------------------------

test('text with state binding emits setTextContent fallback', function () {
    $b = (new CodegenFactory())->get('wasm');
    $binding = new Binding('title', 'Hello');
    $text = new Perry\UI\Widget\Text($binding);
    $container = new Perry\UI\Widget\AppContainer(
        $text,
        null,
        null,
        $binding,
    );
    $out = $b->generate($container);
    expect($out)->toContain('perry_ui_createWidget');
});

// ---------------------------------------------------------------------------
// Nested containers
// ---------------------------------------------------------------------------

test('nested VStack and HStack generate correct hierarchy', function () {
    $b = (new CodegenFactory())->get('wasm');
    $ui = new Perry\UI\Widget\VStack(
        new Perry\UI\Widget\Text('Top'),
        new Perry\UI\Widget\HStack(
            new Perry\UI\Widget\Text('Left'),
            new Perry\UI\Widget\Text('Right'),
        ),
    );
    $out = $b->generate($ui);

    // VStack w1, Text w2, HStack w3, Text w4, Text w5
    expect($out)->toContain('perry_ui_addChild(w1, w2);')
        ->and($out)->toContain('perry_ui_addChild(w1, w3);')
        ->and($out)->toContain('perry_ui_addChild(w3, w4);')
        ->and($out)->toContain('perry_ui_addChild(w3, w5);');
});

// ---------------------------------------------------------------------------
// Toggle inner widget structure
// ---------------------------------------------------------------------------

test('toggle generates label, checkbox input, and span', function () {
    $b = (new CodegenFactory())->get('wasm');
    $toggle = new Perry\UI\Widget\Toggle('Wi-Fi');
    $out = $b->generate($toggle);
    expect($out)->toContain("perry_ui_setTextContent")
        ->and($out)->toContain("perry_ui_addChild")
        ->and($out)->toContain("'checkbox'");
});
