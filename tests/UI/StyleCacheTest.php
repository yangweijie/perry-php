<?php

declare(strict_types=1);

use Perry\UI\Styling\StyleCache;
use Perry\UI\Styling\Style;
use Perry\UI\Styling\StyleProperty;
use Perry\UI\Styling\StyleResolver;
use Perry\UI\Widget\Text;

test('StyleCache stores and retrieves values', function () {
    $cache = new StyleCache();
    $cache->set('key1', 'value1');
    expect($cache->has('key1'))->toBeTrue();
    expect($cache->get('key1'))->toBe('value1');
    expect($cache->has('missing'))->toBeFalse();
});

test('StyleCache::clear removes all entries', function () {
    $cache = new StyleCache();
    $cache->set('a', 1);
    $cache->set('b', 2);
    $cache->clear();
    expect($cache->has('a'))->toBeFalse();
    expect($cache->has('b'))->toBeFalse();
});

test('StyleCache::remove deletes single entry', function () {
    $cache = new StyleCache();
    $cache->set('a', 1);
    $cache->set('b', 2);
    $cache->remove('a');
    expect($cache->has('a'))->toBeFalse();
    expect($cache->has('b'))->toBeTrue();
});

test('StyleCache::keyForWidget generates consistent keys', function () {
    $w = new Text('test');
    $k1 = StyleCache::keyForWidget($w);
    $k2 = StyleCache::keyForWidget($w);
    expect($k1)->toBe($k2);
    expect($k1)->toMatch('/^w_\d+$/');
});

test('StyleCache::keyForStyle generates same key for equal styles', function () {
    $s1 = (new Style())->set(StyleProperty::FontSize, 16);
    $s2 = (new Style())->set(StyleProperty::FontSize, 16);
    expect(StyleCache::keyForStyle($s1))->toBe(StyleCache::keyForStyle($s2));
});

test('StyleCache::keyForStyle generates different keys for different styles', function () {
    $s1 = (new Style())->set(StyleProperty::FontSize, 16);
    $s2 = (new Style())->set(StyleProperty::FontSize, 20);
    expect(StyleCache::keyForStyle($s1))->not->toBe(StyleCache::keyForStyle($s2));
});

test('StyleResolver uses cache to avoid re-resolution', function () {
    $cache = new StyleCache();
    $resolver = new StyleResolver();

    $style = (new Style())->set(StyleProperty::FontSize, 16);
    $widget = (new Text('Cached'))->style($style);

    // First resolve — populates cache
    $resolver->resolve($widget, null, $cache);

    // Verify widget was resolved
    expect($widget->getStyle())->not->toBeNull();

    // Second resolve on same widget — should hit cache
    $widget->setStyle(null);
    $resolver->resolve($widget, null, $cache);
    expect($widget->getStyle())->not->toBeNull();
    expect($widget->getStyle()->get(StyleProperty::FontSize))->toBe(16);
});

test('HtmlBackend cache caches styleToCssArray results', function () {
    $cache = new StyleCache();
    $backend = new \Perry\Codegen\HtmlBackend();
    $backend->setCache($cache);

    $style = (new Style())
        ->set(StyleProperty::FontSize, 16)
        ->set(StyleProperty::ForegroundColor, '#ff0000');

    $widget = (new Text('CacheTest'))->style($style);
    $out1 = $backend->generate($widget);

    // Cache should have stored the CSS key
    $cssKey = StyleCache::keyForCss($style);
    expect($cache->has($cssKey))->toBeTrue();
    expect($cache->get($cssKey))->toBeArray();
});

test('StyleResolver cache stores per-breakpoint keys', function () {
    $cache = new StyleCache();
    $resolver = new StyleResolver();
    $bp = \Perry\UI\Styling\Breakpoint::Sm;

    $base = (new Style())->set(StyleProperty::FontSize, 16);
    $variant = (new Style())->set(StyleProperty::FontSize, 24);
    $base->forBreakpoint($bp, $variant);

    $widget = (new Text('BpCached'))->style($base);

    $resolver->resolveForBreakpoint($widget, $bp, null, $cache);

    // Verify cached
    $key = StyleCache::keyForWidget($widget) . '_sm';
    expect($cache->has($key))->toBeTrue();
});
