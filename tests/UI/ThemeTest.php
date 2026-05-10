<?php

declare(strict_types=1);

use Perry\UI\Styling\Theme;
use Perry\UI\Styling\ThemeMode;
use Perry\UI\Styling\Style;
use Perry\UI\Styling\StyleProperty;
use Perry\UI\Styling\StyleResolver;
use Perry\UI\Widget\Text;

test('Theme::light provides default colors', function () {
    $theme = Theme::light();
    expect($theme->mode())->toBe(ThemeMode::Light);
    expect($theme->getColor('primary'))->toBe('#007aff');
    expect($theme->getColor('background'))->toBe('#ffffff');
    expect($theme->getColor('unknown'))->toBeNull();
});

test('Theme::dark provides default dark colors', function () {
    $theme = Theme::dark();
    expect($theme->mode())->toBe(ThemeMode::Dark);
    expect($theme->getColor('primary'))->toBe('#0a84ff');
    expect($theme->getColor('background'))->toBe('#000000');
});

test('Theme resolves @token values', function () {
    $theme = Theme::light();
    expect($theme->resolveValue('@primary'))->toBe('#007aff');
    expect($theme->resolveValue('@background'))->toBe('#ffffff');
    expect($theme->resolveValue('#ff0000'))->toBe('#ff0000');
    expect($theme->resolveValue('@missing'))->toBe('@missing');
});

test('Theme allows custom overrides', function () {
    $theme = Theme::light(['primary' => '#ff0000']);
    expect($theme->getColor('primary'))->toBe('#ff0000');
    expect($theme->getColor('background'))->toBe('#ffffff'); // default preserved
});

test('StyleResolver resolves @theme tokens in widget styles', function () {
    $theme = Theme::light();
    $style = (new Style())->set(StyleProperty::BackgroundColor, '@primary');
    $widget = (new Text('Test'))->style($style);
    $resolver = new StyleResolver();
    $resolver->resolve($widget, $theme);
    $resolved = $widget->getStyle();
    expect($resolved->get(StyleProperty::BackgroundColor))->toBe('#007aff');
});

test('StyleResolver resolves multiple theme token types', function () {
    $theme = Theme::light();
    $style = (new Style())
        ->set(StyleProperty::BackgroundColor, '@primary')
        ->set(StyleProperty::ForegroundColor, '@text')
        ->set(StyleProperty::BorderColor, '@border');
    $widget = (new Text('Multi'))->style($style);
    $resolver = new StyleResolver();
    $resolver->resolve($widget, $theme);
    $resolved = $widget->getStyle();
    expect($resolved->get(StyleProperty::BackgroundColor))->toBe('#007aff');
    expect($resolved->get(StyleProperty::ForegroundColor))->toBe('#000000');
    expect($resolved->get(StyleProperty::BorderColor))->toBe('#e0e0e0');
});

test('StyleResolver skips non-token color values', function () {
    $theme = Theme::light();
    $style = (new Style())->set(StyleProperty::BackgroundColor, '#ff0000');
    $widget = (new Text('Literal'))->style($style);
    $resolver = new StyleResolver();
    $resolver->resolve($widget, $theme);
    expect($widget->getStyle()->get(StyleProperty::BackgroundColor))->toBe('#ff0000');
});

test('StyleResolver passes null theme without error', function () {
    $style = (new Style())->set(StyleProperty::BackgroundColor, '@primary');
    $widget = (new Text('NullTheme'))->style($style);
    $resolver = new StyleResolver();
    $resolver->resolve($widget, null); // no theme
    // @primary should stay as-is since there is no theme
    expect($widget->getStyle()->get(StyleProperty::BackgroundColor))->toBe('@primary');
});

test('HtmlBackend emits theme CSS custom properties', function () {
    $theme = Theme::light();
    $backend = new \Perry\Codegen\HtmlBackend();
    $backend->setTheme($theme);
    $out = $backend->generate(new Text('ThemeTest'));
    expect($out)->toContain('--theme-primary: #007aff');
    expect($out)->toContain('--theme-background: #ffffff');
    expect($out)->toContain('prefers-color-scheme: dark');
    expect($out)->toContain('--theme-primary: #0a84ff');
});

test('HtmlBackend without theme emits no CSS custom properties', function () {
    $backend = new \Perry\Codegen\HtmlBackend();
    $out = $backend->generate(new Text('NoTheme'));
    expect($out)->not->toContain('--theme-');
});

test('Theme::toCssCustomProperties generates both light and dark', function () {
    $css = Theme::toCssCustomProperties();
    expect($css)->toContain(':root');
    expect($css)->toContain('--theme-primary: #007aff');
    expect($css)->toContain('--theme-background: #ffffff');
    expect($css)->toContain('prefers-color-scheme: dark');
    expect($css)->toContain('--theme-primary: #0a84ff');
    expect($css)->toContain('--theme-background: #000000');
});
