<?php

declare(strict_types=1);

use Perry\UI\Action;
use Perry\UI\ActionRegistry;
use Perry\UI\ActionType;
use Perry\UI\Frontend\AttributeResolver;
use Perry\UI\Frontend\HtmlFrontend;
use Perry\UI\Frontend\TagMapper;
use Perry\UI\Frontend\TemplateRegistry;
use Perry\UI\NamedState;
use Perry\UI\Styling\Style;
use Perry\UI\Widget\Button;
use Perry\UI\Widget\HStack;
use Perry\UI\Widget\Image;
use Perry\UI\Widget\Slider;
use Perry\UI\Widget\Spacer;
use Perry\UI\Widget\Text;
use Perry\UI\Widget\TextInput;
use Perry\UI\Widget\Toggle;
use Perry\UI\Widget\VStack;

beforeEach(function () {
    NamedState::reset();
    ActionRegistry::reset();
    TemplateRegistry::reset();
});

test('TagMapper resolves known tags', function () {
    expect(TagMapper::resolve('vstack'))->toBe(VStack::class);
    expect(TagMapper::resolve('hstack'))->toBe(HStack::class);
    expect(TagMapper::resolve('button'))->toBe(Button::class);
    expect(TagMapper::resolve('text'))->toBe(Text::class);
    expect(TagMapper::resolve('image'))->toBe(Image::class);
    expect(TagMapper::resolve('spacer'))->toBe(Spacer::class);
    expect(TagMapper::resolve('slider'))->toBe(Slider::class);
    expect(TagMapper::resolve('textinput'))->toBe(TextInput::class);
    expect(TagMapper::resolve('toggle'))->toBe(Toggle::class);
});

test('TagMapper resolves case-insensitively', function () {
    expect(TagMapper::resolve('VStack'))->toBe(VStack::class);
    expect(TagMapper::resolve('BUTTON'))->toBe(Button::class);
});

test('TagMapper returns null for unknown tags', function () {
    expect(TagMapper::resolve('div'))->toBeNull();
    expect(TagMapper::resolve('span'))->toBeNull();
    expect(TagMapper::resolve('unknown'))->toBeNull();
});

test('TagMapper::isWidget matches known tags', function () {
    expect(TagMapper::isWidget('vstack'))->toBeTrue();
    expect(TagMapper::isWidget('div'))->toBeFalse();
});

test('AttributeResolver resolves known styles', function () {
    $style = Style::make()->backgroundColor('#000')->cornerRadius(20);
    $r = new AttributeResolver(['bgBlack' => $style]);
    expect($r->resolveStyle('bgBlack'))->toBe($style);
});

test('AttributeResolver returns null for empty style name', function () {
    $r = new AttributeResolver();
    expect($r->resolveStyle(null))->toBeNull();
    expect($r->resolveStyle(''))->toBeNull();
});

test('AttributeResolver throws for unknown style', function () {
    $r = new AttributeResolver();
    $r->resolveStyle('nope');
})->throws(RuntimeException::class, "Style 'nope' is not defined");

test('AttributeResolver resolves bindings via NamedState', function () {
    NamedState::instance()->create('name', 'world');
    $r = new AttributeResolver();
    $binding = $r->resolveBinding('name');
    expect($binding)->not->toBeNull();
    expect($binding->name)->toBe('name');
});

test('AttributeResolver returns null for empty bind', function () {
    $r = new AttributeResolver();
    expect($r->resolveBinding(null))->toBeNull();
    expect($r->resolveBinding(''))->toBeNull();
});

test('AttributeResolver throws for undefined binding', function () {
    $r = new AttributeResolver();
    $r->resolveBinding('missing');
})->throws(RuntimeException::class, "State key 'missing' is not defined");

test('AttributeResolver resolves registered actions', function () {
    $action = Action::fromClosure(function () {});
    ActionRegistry::register('clear', $action);
    $r = new AttributeResolver();
    [$resolved, $name] = $r->resolveAction('clear');
    expect($resolved)->toBe($action);
    expect($name)->toBe('clear');
});

test('AttributeResolver returns null for empty action', function () {
    $r = new AttributeResolver();
    [$action, $name] = $r->resolveAction(null);
    expect($action)->toBeNull();
    expect($name)->toBeNull();

    [$action2, $name2] = $r->resolveAction('');
    expect($action2)->toBeNull();
    expect($name2)->toBeNull();
});

test('AttributeResolver throws for unregistered action', function () {
    $r = new AttributeResolver();
    $r->resolveAction('nonexistent');
})->throws(RuntimeException::class, "Action 'nonexistent' is not registered");

test('AttributeResolver::parseFloat parses correctly', function () {
    expect(AttributeResolver::parseFloat('42', 0.0))->toBe(42.0);
    expect(AttributeResolver::parseFloat('3.14', 0.0))->toBe(3.14);
    expect(AttributeResolver::parseFloat(null, 10.0))->toBe(10.0);
    expect(AttributeResolver::parseFloat('', 10.0))->toBe(10.0);
});

test('AttributeResolver::parseBool parses correctly', function () {
    expect(AttributeResolver::parseBool('true'))->toBeTrue();
    expect(AttributeResolver::parseBool('false'))->toBeFalse();
    expect(AttributeResolver::parseBool(null, true))->toBeTrue();
    expect(AttributeResolver::parseBool('', false))->toBeFalse();
});

test('HtmlFrontend parses single text element', function () {
    $f = new HtmlFrontend();
    $root = $f->parse('<text>Hello</text>');
    expect($root)->toBeInstanceOf(Text::class);
    expect($root->content())->toBe('Hello');
});

test('HtmlFrontend auto-wraps bare fragment in <ui>', function () {
    $f = new HtmlFrontend();
    $root = $f->parse('<text>A</text><text>B</text>');
    expect($root)->toBeInstanceOf(VStack::class);
});

test('HtmlFrontend parses <ui> wrapper', function () {
    $f = new HtmlFrontend();
    $root = $f->parse('<ui><text>Hi</text></ui>');
    expect($root)->toBeInstanceOf(Text::class);
    expect($root->content())->toBe('Hi');
});

test('HtmlFrontend parses <vstack> with children', function () {
    $f = new HtmlFrontend();
    $root = $f->parse(<<<'HTML'
<vstack>
    <text>One</text>
    <text>Two</text>
</vstack>
HTML);
    expect($root)->toBeInstanceOf(VStack::class);
    expect($root->children())->toHaveCount(2);
    expect($root->children()[0]->content())->toBe('One');
    expect($root->children()[1]->content())->toBe('Two');
});

test('HtmlFrontend parses nested containers', function () {
    $f = new HtmlFrontend();
    $root = $f->parse(<<<'HTML'
<vstack>
    <hstack>
        <text>A</text>
        <text>B</text>
    </hstack>
    <text>C</text>
</vstack>
HTML);
    expect($root)->toBeInstanceOf(VStack::class);
    expect($root->children())->toHaveCount(2);
    expect($root->children()[0])->toBeInstanceOf(HStack::class);
    expect($root->children()[0]->children())->toHaveCount(2);
    expect($root->children()[1]->content())->toBe('C');
});

test('HtmlFrontend parses button with label and onclick', function () {
    $action = Action::fromClosure(function () {});
    ActionRegistry::register('clear', $action);

    $f = new HtmlFrontend();
    $root = $f->parse('<button onclick="clear">Clear</button>');
    expect($root)->toBeInstanceOf(Button::class);
    expect($root->getActionName())->toBe('clear');
});

test('HtmlFrontend parses spacer', function () {
    $f = new HtmlFrontend();
    $root = $f->parse('<spacer />');
    expect($root)->toBeInstanceOf(Spacer::class);
});

test('HtmlFrontend parses image with src', function () {
    $f = new HtmlFrontend();
    $root = $f->parse('<image src="icon.png" />');
    expect($root)->toBeInstanceOf(Image::class);
});

test('HtmlFrontend throws for image without src', function () {
    $f = new HtmlFrontend();
    $f->parse('<image />');
})->throws(RuntimeException::class, 'requires a "src" attribute');

test('HtmlFrontend parses text with bind', function () {
    NamedState::instance()->create('display', '0');
    $f = new HtmlFrontend();
    $root = $f->parse('<text bind="display" />');
    expect($root)->toBeInstanceOf(Text::class);
});

test('HtmlFrontend throws for empty text without bind', function () {
    $f = new HtmlFrontend();
    $f->parse('<text></text>');
})->throws(RuntimeException::class, 'requires either bind="key" or literal text');

test('HtmlFrontend parses slider with bind', function () {
    NamedState::instance()->create('volume', 50);
    $f = new HtmlFrontend();
    $root = $f->parse('<slider bind="volume" min="0" max="100" step="1" />');
    expect($root)->toBeInstanceOf(Slider::class);
});

test('HtmlFrontend throws for slider without bind', function () {
    $f = new HtmlFrontend();
    $f->parse('<slider />');
})->throws(RuntimeException::class, 'requires bind="key"');

test('HtmlFrontend parses toggle with label and onchange', function () {
    $action = Action::fromClosure(function () {});
    ActionRegistry::register('toggle', $action);

    NamedState::instance()->create('enabled', false);
    $f = new HtmlFrontend();
    $root = $f->parse('<toggle bind="enabled" ontoggle="toggle">Enable</toggle>');
    expect($root)->toBeInstanceOf(Toggle::class);
    expect($root->getActionName())->toBe('toggle');
});
test('HtmlFrontend <bind> declarations create NamedState entries', function () {
    $f = new HtmlFrontend();
    $f->parse(<<<'HTML'
<bind name="count" default="0" />
<text bind="count" />
HTML);
    expect(NamedState::instance()->has('count'))->toBeTrue();
    expect(NamedState::instance()->get('count'))->toBe(0);
});

test('HtmlFrontend <bind> with string default', function () {
    $f = new HtmlFrontend();
    $f->parse('<bind name="name" default="hello" /><text bind="name" />');
    expect(NamedState::instance()->get('name'))->toBe('hello');
});

test('HtmlFrontend <bind> with boolean defaults', function () {
    $f = new HtmlFrontend();
    $f->parse('<bind name="b1" default="true" /><bind name="b2" default="false" /><toggle bind="b1" /><toggle bind="b2" />');
    expect(NamedState::instance()->get('b1'))->toBeTrue();
    expect(NamedState::instance()->get('b2'))->toBeFalse();
});

test('HtmlFrontend <bind> with null default', function () {
    $f = new HtmlFrontend();
    $f->parse('<bind name="x" default="null" /><text bind="x" />');
    expect(NamedState::instance()->get('x'))->toBeNull();
});
test('HtmlFrontend applies style by name', function () {
    $style = Style::make()->backgroundColor('#000')->cornerRadius(20);
    $f = new HtmlFrontend(['bgBlack' => $style]);
    $root = $f->parse('<vstack style="bgBlack"><text>Hello</text></vstack>');
    expect($root->getStyle())->not->toBeNull();
    expect($root->getStyle()->get(\Perry\UI\Styling\StyleProperty::BackgroundColor))
        ->toBe('#000');
});
test('HtmlFrontend throws on unknown tag', function () {
    $f = new HtmlFrontend();
    $f->parse('<div>content</div>');
})->throws(RuntimeException::class, "Unknown HTML DSL tag: 'div'");

test('HtmlFrontend throws on unregistered action reference', function () {
    $f = new HtmlFrontend();
    $f->parse('<button onclick="nope">X</button>');
})->throws(RuntimeException::class, "Action 'nope' is not registered");

test('HtmlFrontend throws on empty document', function () {
    $f = new HtmlFrontend();
    $f->parse('');
})->throws(RuntimeException::class);

test('HtmlFrontend throws on undefined style reference', function () {
    $f = new HtmlFrontend();
    $f->parse('<text style="missing">Hi</text>');
})->throws(RuntimeException::class, "Style 'missing' is not defined");
test('HtmlFrontend <scrollview> container', function () {
    $f = new HtmlFrontend();
    $root = $f->parse(<<<'HTML'
<scrollview>
    <text>Scrollable</text>
</scrollview>
HTML);
    expect($root)->toBeInstanceOf(\Perry\UI\Widget\ScrollView::class);
});

test('HtmlFrontend parses empty container', function () {
    $f = new HtmlFrontend();
    $root = $f->parse('<vstack></vstack>');
    expect($root)->toBeInstanceOf(VStack::class);
    expect($root->children())->toHaveCount(0);
});
test('NamedState creates, gets, and sets values', function () {
    $ns = NamedState::instance();
    $ns->create('counter', 0);
    expect($ns->get('counter'))->toBe(0);
    $ns->set('counter', 42);
    expect($ns->get('counter'))->toBe(42);
});

test('NamedState batch update', function () {
    $ns = NamedState::instance();
    $ns->create('a', 1);
    $ns->create('b', 2);
    $ns->update(['a' => 10, 'b' => 20]);
    expect($ns->get('a'))->toBe(10);
    expect($ns->get('b'))->toBe(20);
});

test('NamedState watcher fires on change', function () {
    $ns = NamedState::instance();
    $ns->create('x', 0);
    $called = null;
    $ns->watch('x', function ($v) use (&$called) {
        $called = $v;
    });
    $ns->set('x', 99);
    expect($called)->toBe(99);
});

test('NamedState binding returns correct Binding', function () {
    $ns = NamedState::instance();
    $ns->create('name', 'perry');
    $binding = $ns->binding('name');
    expect($binding)->toBeInstanceOf(\Perry\UI\Binding::class);
    expect($binding->name)->toBe('name');
    expect($binding->initialValue)->toBe('perry');
});

test('NamedState throws on duplicate key', function () {
    $ns = NamedState::instance();
    $ns->create('dup', 1);
    $ns->create('dup', 2);
})->throws(RuntimeException::class, "already exists");

test('NamedState throws on missing key get', function () {
    NamedState::instance()->get('missing');
})->throws(RuntimeException::class, "not found");
test('ActionRegistry registers and retrieves actions', function () {
    $action = Action::fromClosure(function () {
        return 42;
    });
    ActionRegistry::register('compute', $action);
    expect(ActionRegistry::has('compute'))->toBeTrue();
    expect(ActionRegistry::get('compute'))->toBe($action);
});

test('ActionRegistry dispatch executes closure', function () {
    ActionRegistry::register('ping', Action::fromClosure(function () {
        return 'pong';
    }));
    expect(ActionRegistry::dispatch('ping'))->toBe('pong');
});

test('ActionRegistry throws on dispatch without closure', function () {
    ActionRegistry::register('noop', new Action(ActionType::Custom));
    ActionRegistry::dispatch('noop');
})->throws(RuntimeException::class, 'has no closure');

test('ActionRegistry::names returns registered keys', function () {
    ActionRegistry::register('a', Action::fromClosure(fn() => 1));
    ActionRegistry::register('b', Action::fromClosure(fn() => 2));
    expect(ActionRegistry::names())->toContain('a');
    expect(ActionRegistry::names())->toContain('b');
});
//
// TemplateRegistry
//
test('TemplateRegistry registers and retrieves templates', function () {
    $tr = TemplateRegistry::instance();
    $tr->register('greeting', '<text>Hello</text>');
    expect($tr->get('greeting'))->toBe('<text>Hello</text>');
});

test('TemplateRegistry throws on duplicate id', function () {
    $tr = TemplateRegistry::instance();
    $tr->register('x', '<text>A</text>');
    $tr->register('x', '<text>B</text>');
})->throws(RuntimeException::class, 'already registered');

test('TemplateRegistry throws on empty id', function () {
    TemplateRegistry::instance()->register('', '<text>A</text>');
})->throws(RuntimeException::class, 'must not be empty');

test('TemplateRegistry::has and names', function () {
    $tr = TemplateRegistry::instance();
    $tr->register('a', '<text>A</text>');
    $tr->register('b', '<text>B</text>');
    expect($tr->has('a'))->toBeTrue();
    expect($tr->has('c'))->toBeFalse();
    expect($tr->names())->toBe(['a', 'b']);
});

test('TemplateRegistry throws on get undefined', function () {
    TemplateRegistry::instance()->get('nope');
})->throws(RuntimeException::class, 'not defined');

test('TemplateRegistry::clear empties registry', function () {
    $tr = TemplateRegistry::instance();
    $tr->register('x', '<text>X</text>');
    $tr->clear();
    expect($tr->names())->toBe([]);
});

//
// HtmlFrontend template support
//
test('HtmlFrontend strips <template> definitions from output', function () {
    $f = new HtmlFrontend();
    $root = $f->parse(<<<'HTML'
<template id="greeting">
    <text>Hello</text>
</template>
<text>World</text>
HTML);
    expect($root)->toBeInstanceOf(Text::class);
    expect($root->content())->toBe('World');
});

test('HtmlFrontend <use> expands simple template', function () {
    TemplateRegistry::instance()->register('greeting', '<text>Hi</text>');
    $f = new HtmlFrontend();
    $root = $f->parse('<use template="greeting" />');
    expect($root)->toBeInstanceOf(Text::class);
    expect($root->content())->toBe('Hi');
});

test('HtmlFrontend <use> with parameter substitution', function () {
    TemplateRegistry::instance()->register('greeting', '<text>{%msg%}</text>');
    $f = new HtmlFrontend();
    $root = $f->parse('<use template="greeting" msg="Hello" />');
    expect($root)->toBeInstanceOf(Text::class);
    expect($root->content())->toBe('Hello');
});

test('HtmlFrontend <use> with multiple params', function () {
    TemplateRegistry::instance()->register('welcome', '<vstack><text>{%title%}</text><text>{%sub%}</text></vstack>');
    $f = new HtmlFrontend();
    $root = $f->parse('<use template="welcome" title="Main" sub="Secondary" />');
    expect($root)->toBeInstanceOf(VStack::class);
    expect($root->children())->toHaveCount(2);
    expect($root->children()[0]->content())->toBe('Main');
    expect($root->children()[1]->content())->toBe('Secondary');
});

test('HtmlFrontend <use> parameterized bind key', function () {
    NamedState::instance()->create('myDisplay', '42');
    TemplateRegistry::instance()->register('display', '<text bind="{%key%}" />');
    $f = new HtmlFrontend();
    $root = $f->parse('<use template="display" key="myDisplay" />');
    expect($root)->toBeInstanceOf(Text::class);
});

test('HtmlFrontend <use> parameterized action', function () {
    ActionRegistry::register('myAction', Action::fromClosure(fn() => null));
    TemplateRegistry::instance()->register('btn', '<button onclick="{%act%}">Go</button>');
    $f = new HtmlFrontend();
    $root = $f->parse('<use template="btn" act="myAction" />');
    expect($root)->toBeInstanceOf(Button::class);
});

test('HtmlFrontend <use> multiple instances of same template', function () {
    TemplateRegistry::instance()->register('item', '<text>{%label%}</text>');
    $f = new HtmlFrontend();
    $root = $f->parse(<<<'HTML'
<vstack>
    <use template="item" label="A" />
    <use template="item" label="B" />
</vstack>
HTML);
    expect($root)->toBeInstanceOf(VStack::class);
    expect($root->children())->toHaveCount(2);
    expect($root->children()[0]->content())->toBe('A');
    expect($root->children()[1]->content())->toBe('B');
});

test('HtmlFrontend throws on undefined template', function () {
    $f = new HtmlFrontend();
    $f->parse('<use template="undefined" />');
})->throws(RuntimeException::class, 'not defined');

test('HtmlFrontend throws on <use> without template attribute', function () {
    $f = new HtmlFrontend();
    $f->parse('<use />');
})->throws(RuntimeException::class, 'requires a "template" attribute');

test('HtmlFrontend throws on unresolved template placeholder', function () {
    TemplateRegistry::instance()->register('bad', '<text>{%missing%}</text>');
    $f = new HtmlFrontend();
    $f->parse('<use template="bad" />');
})->throws(RuntimeException::class, 'unresolved placeholder');

test('HtmlFrontend throws on circular template reference', function () {
    TemplateRegistry::instance()->register('a', '<use template="b" />');
    TemplateRegistry::instance()->register('b', '<use template="a" />');
    $f = new HtmlFrontend();
    $f->parse('<use template="a" />');
})->throws(RuntimeException::class, 'Circular template reference');

test('HtmlFrontend parsed tree can be rendered by HtmlBackend', function () {
    NamedState::instance()->create('display', '0');
    $clearAction = Action::fromClosure(function () {
        $ns = Perry\UI\NamedState::instance();
        $ns->set('display', '0');
    });
    ActionRegistry::register('clear', $clearAction);

    $f = new HtmlFrontend();
    $root = $f->parse(<<<'HTML'
<vstack>
    <text bind="display" />
    <button onclick="clear">Clear</button>
</vstack>
HTML);

    $backend = new \Perry\Codegen\HtmlBackend();
    $out = $backend->generate($root);
    expect($out)->toContain('<button');
    expect($out)->toContain('Clear');
    expect($out)->toContain('function clear');
});
