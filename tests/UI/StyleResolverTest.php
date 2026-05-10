<?php

declare(strict_types=1);

use Perry\UI\Styling\Style;
use Perry\UI\Styling\StyleProperty;
use Perry\UI\Styling\StyleResolver;
use Perry\UI\Widget\VStack;
use Perry\UI\Widget\HStack;
use Perry\UI\Widget\Text;

test('resolver leaves widget with no parent and no style unchanged', function () {
    $text = new Text('hello');
    $resolver = new StyleResolver();
    $resolver->resolve($text);

    expect($text->getStyle())->toBeNull();
});

test('resolver keeps own style when no parent', function () {
    $style = Style::make()->fontSize(16);
    $text = (new Text('hello'))->style($style);

    $resolver = new StyleResolver();
    $resolver->resolve($text);

    expect($text->getStyle())->not->toBeNull();
    expect($text->getStyle()->get(StyleProperty::FontSize))->toEqual(16);
});

test('resolver inherits parent style to child with no own style', function () {
    $parentStyle = Style::make()->fontSize(16)->foregroundColor('#333');
    $parent = (new VStack())->style($parentStyle);
    $child = new Text('hello');
    $parent->addChild($child);

    $resolver = new StyleResolver();
    $resolver->resolve($parent);

    expect($parent->getStyle()->get(StyleProperty::FontSize))->toEqual(16);
    expect($parent->getStyle()->get(StyleProperty::ForegroundColor))->toBe('#333');

    expect($child->getStyle())->not->toBeNull();
    expect($child->getStyle()->get(StyleProperty::FontSize))->toEqual(16);
    expect($child->getStyle()->get(StyleProperty::ForegroundColor))->toBe('#333');
});

test('resolver allows child to override inherited property', function () {
    $parentStyle = Style::make()->fontSize(16)->foregroundColor('#333');
    $childStyle = Style::make()->fontSize(20);

    $parent = (new VStack())->style($parentStyle);
    $child = (new Text('hello'))->style($childStyle);
    $parent->addChild($child);

    $resolver = new StyleResolver();
    $resolver->resolve($parent);

    expect($child->getStyle()->get(StyleProperty::FontSize))->toEqual(20);
    expect($child->getStyle()->get(StyleProperty::ForegroundColor))->toBe('#333');
});

test('resolver works with deeply nested tree', function () {
    $rootStyle = Style::make()->fontSize(14)->foregroundColor('#000');
    $midStyle = Style::make()->foregroundColor('#666');
    $leafStyle = Style::make()->fontSize(18);

    $leaf = (new Text('leaf'))->style($leafStyle);
    $mid = (new HStack())->style($midStyle);
    $mid->addChild($leaf);
    $root = (new VStack())->style($rootStyle);
    $root->addChild($mid);

    $resolver = new StyleResolver();
    $resolver->resolve($root);

    expect($root->getStyle()->get(StyleProperty::FontSize))->toEqual(14);
    expect($root->getStyle()->get(StyleProperty::ForegroundColor))->toBe('#000');

    expect($mid->getStyle()->get(StyleProperty::FontSize))->toEqual(14);
    expect($mid->getStyle()->get(StyleProperty::ForegroundColor))->toBe('#666');

    expect($leaf->getStyle()->get(StyleProperty::FontSize))->toEqual(18);
    expect($leaf->getStyle()->get(StyleProperty::ForegroundColor))->toBe('#666');
});

test('resolver isolates sibling styles', function () {
    $parentStyle = Style::make()->fontSize(14);

    $child1 = (new Text('a'))->style(Style::make()->fontSize(16));
    $child2 = new Text('b');
    $child3 = (new Text('c'))->style(Style::make()->foregroundColor('red'));

    $parent = (new VStack())->style($parentStyle);
    $parent->addChild($child1);
    $parent->addChild($child2);
    $parent->addChild($child3);

    $resolver = new StyleResolver();
    $resolver->resolve($parent);

    expect($child1->getStyle()->get(StyleProperty::FontSize))->toEqual(16);

    expect($child2->getStyle()->get(StyleProperty::FontSize))->toEqual(14);

    expect($child3->getStyle()->get(StyleProperty::FontSize))->toEqual(14);
    expect($child3->getStyle()->get(StyleProperty::ForegroundColor))->toBe('red');
});

test('resolver does not mutate parent style when resolving child', function () {
    $parentStyle = Style::make()->fontSize(14);
    $childStyle = Style::make()->fontSize(16);

    $parent = (new VStack())->style($parentStyle);
    $parent->addChild((new Text('child'))->style($childStyle));

    $resolver = new StyleResolver();
    $resolver->resolve($parent);

    expect($parent->getStyle()->get(StyleProperty::FontSize))->toEqual(14);
});
