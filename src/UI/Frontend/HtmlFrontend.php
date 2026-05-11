<?php

declare(strict_types=1);

namespace Perry\UI\Frontend;

use DOMDocument;
use DOMElement;
use DOMNode;
use DOMText;
use Perry\UI\NamedState;
use Perry\UI\Widget;
use Perry\UI\Widget\Button;
use Perry\UI\Widget\HStack;
use Perry\UI\Widget\Image;
use Perry\UI\Widget\ScrollView;
use Perry\UI\Widget\Slider;
use Perry\UI\Widget\Spacer;
use Perry\UI\Widget\Text;
use Perry\UI\Widget\TextInput;
use Perry\UI\Widget\Toggle;
use Perry\UI\Widget\VStack;

/**
 * HtmlFrontend — HTML DSL Parser for Perry Widget Trees.
 *
 * Parses Perry's HTML DSL into a Widget tree consumable by any codegen backend.
 *
 * Usage:
 *   $frontend = new HtmlFrontend();
 *   $frontend->addStyles(['bgBlack' => Style::make()->backgroundColor('#000')]);
 *   $root = $frontend->parse(file_get_contents('calc.ui'));
 *
 * Supported elements with attributes:
 *   <ui>, <bind name="x" default="0" />,
 *   <vstack style="x">, <hstack>, <scrollview>,
 *   <text bind="key" />, <text>literal</text>,
 *   <button onclick="name">Label</button>,
 *   <image src="path" />, <spacer />,
 *   <textinput bind="key" placeholder="..." onchange="name" />,
 *   <toggle bind="key" ontoggle="name">Label</toggle>,
 *   <slider bind="key" min="0" max="100" step="1" onchange="name" />
 */
final class HtmlFrontend
{
    private AttributeResolver $resolver;
    private TemplateRegistry $templateRegistry;

    /** @var array<string, true> recursion guard for template expansion */
    private array $expandingTemplates = [];

    public function __construct(array $styles = [], ?TemplateRegistry $templateRegistry = null)
    {
        $this->resolver = new AttributeResolver($styles);
        $this->templateRegistry = $templateRegistry ?? TemplateRegistry::instance();
    }

    public function addStyle(string $name, Style $style): void
    {
        $this->resolver->addStyle($name, $style);
    }

    public function addStyles(array $styles): void
    {
        $this->resolver->addStyles($styles);
    }

    public function resolver(): AttributeResolver
    {
        return $this->resolver;
    }

    public function parse(string $html): Widget
    {
        if (trim($html) === '') {
            throw new \RuntimeException('Empty HTML DSL document');
        }

        // Extract <template> definitions from raw HTML before DOM parse
        $html = $this->collectTemplates($html);

        $dom = new DOMDocument();

        $useErrors = libxml_use_internal_errors(true);
        $dom->loadHTML($this->wrapHtml($html), LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_use_internal_errors($useErrors);

        $root = $dom->documentElement;
        if (!$root instanceof DOMElement) {
            throw new \RuntimeException('Failed to parse HTML DSL: empty document');
        }

        $this->collectBinds($root);

        return $this->buildTree($root);
    }

    private function wrapHtml(string $html): string
    {
        $trimmed = trim($html);
        if (str_starts_with($trimmed, '<ui') || str_starts_with($trimmed, '<vstack')
            || str_starts_with($trimmed, '<hstack') || str_starts_with($trimmed, '<scrollview')
        ) {
            return $trimmed;
        }
        return "<ui>{$trimmed}</ui>";
    }

    private function collectBinds(DOMElement $root): void
    {
        $this->walkElements($root, function (DOMElement $el) {
            if ($el->tagName !== 'bind') {
                return;
            }
            $name = $el->getAttribute('name');
            if ($name === '') {
                throw new \RuntimeException('<bind> element requires a "name" attribute');
            }
            $defaultAttr = $el->getAttribute('default');
            $default = $defaultAttr !== '' ? $this->coerceValue($defaultAttr) : null;
            $ns = NamedState::instance();
            if (!$ns->has($name)) {
                $ns->create($name, $default);
            }
        });
    }

    private function walkElements(DOMElement $root, callable $callback): void
    {
        $callback($root);
        for ($node = $root->firstChild; $node !== null; $node = $node->nextSibling) {
            if ($node instanceof DOMElement) {
                $this->walkElements($node, $callback);
            }
        }
    }

    private function collectTemplates(string $html): string
    {
        return preg_replace_callback(
            '/<template\s+id=["\']([^"\']+)["\'][^>]*>\s*(.*?)\s*<\/template>/is',
            function (array $matches): string {
                $this->templateRegistry->register($matches[1], $matches[2]);
                return ''; // remove template definition from HTML
            },
            $html
        );
    }

    private function buildUseTag(DOMElement $node): Widget
    {
        $templateId = $node->getAttribute('template');
        if ($templateId === '') {
            throw new \RuntimeException('<use> tag requires a "template" attribute');
        }

        if (isset($this->expandingTemplates[$templateId])) {
            throw new \RuntimeException("Circular template reference: '{$templateId}'");
        }

        $this->expandingTemplates[$templateId] = true;
        try {
            $params = [];
            foreach ($node->attributes as $attr) {
                if ($attr->name !== 'template') {
                    $params[$attr->name] = $attr->value;
                }
            }

            $body = $this->templateRegistry->get($templateId);
            $expanded = $this->expandTemplate($body, $params);

            return $this->parseFragment($expanded);
        } finally {
            unset($this->expandingTemplates[$templateId]);
        }
    }

    private function expandTemplate(string $body, array $params): string
    {
        $result = $body;
        foreach ($params as $key => $value) {
            $result = str_replace("{%{$key}%}", $value, $result);
        }

        // Check for unresolved placeholders (typo or missing param)
        if (preg_match('/\{%.+?%\}/', $result)) {
            throw new \RuntimeException(
                'Template has unresolved placeholder(s) — missing parameter(s)'
            );
        }

        return $result;
    }

    private function parseFragment(string $html): Widget
    {
        $trimmed = trim($html);
        if ($trimmed === '') {
            return new VStack();
        }

        $dom = new DOMDocument();
        $useErrors = libxml_use_internal_errors(true);
        $dom->loadHTML($this->wrapHtml($html), LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_use_internal_errors($useErrors);

        $root = $dom->documentElement;
        if (!$root instanceof DOMElement) {
            throw new \RuntimeException('Failed to parse template fragment');
        }

        return $this->buildTree($root);
    }

    private function coerceValue(string $value): float|int|string|bool|null
    {
        if ($value === 'null' || $value === '') {
            return null;
        }
        if ($value === 'true') {
            return true;
        }
        if ($value === 'false') {
            return false;
        }
        if (ctype_digit($value)) {
            return (int) $value;
        }
        if (is_numeric($value)) {
            return (float) $value;
        }
        return $value;
    }

    private function buildTree(DOMNode $node): Widget
    {
        if ($node instanceof DOMText) {
            return $this->buildTextFromTextNode($node);
        }
        if (!$node instanceof DOMElement) {
            throw new \RuntimeException('Unexpected DOM node type');
        }

        $tagName = strtolower($node->tagName);

        if ($tagName === 'use') {
            return $this->buildUseTag($node);
        }

        if (in_array($tagName, ['ui', 'bind', 'template'], true)) {
            return $this->buildContainer($node);
        }

        $widgetClass = TagMapper::resolve($tagName);
        if ($widgetClass === null) {
            throw new \RuntimeException("Unknown HTML DSL tag: '{$tagName}'");
        }

        return $this->instantiateWidget($widgetClass, $node);
    }

    private function buildTextFromTextNode(DOMText $node): Text
    {
        $content = trim($node->wholeText);
        if ($content === '') {
            throw new \RuntimeException('Empty text node — should be filtered before calling');
        }
        return new Text($content);
    }

    private function buildContainer(DOMElement $element): Widget
    {
        $children = $this->buildChildren($element);
        return match (count($children)) {
            0 => new VStack(),
            1 => $children[0],
            default => new VStack(...$children),
        };
    }

    private function buildChildren(DOMElement $parent): array
    {
        $children = [];
        for ($node = $parent->firstChild; $node !== null; $node = $node->nextSibling) {
            if ($node instanceof DOMText && trim($node->wholeText) === '') {
                continue;
            }
            if ($node instanceof DOMElement
                && in_array($node->tagName, ['bind', 'template'], true)
            ) {
                continue;
            }
            try {
                $children[] = $this->buildTree($node);
            } catch (\RuntimeException $e) {
                $context = $node instanceof DOMElement
                    ? "tag <{$node->tagName}>"
                    : 'text node';
                throw new \RuntimeException(
                    "Error building {$context}: {$e->getMessage()}",
                    0,
                    $e
                );
            }
        }
        return $children;
    }

    private function instantiateWidget(string $widgetClass, DOMElement $element): Widget
    {
        $widget = match ($widgetClass) {
            VStack::class, HStack::class, ScrollView::class
                => $this->makeContainerWidget($widgetClass, $element),
            Text::class => $this->makeText($element),
            Button::class => $this->makeButton($element),
            Image::class => $this->makeImage($element),
            Spacer::class => new Spacer(),
            TextInput::class => $this->makeTextInput($element),
            Toggle::class => $this->makeToggle($element),
            Slider::class => $this->makeSlider($element),
            default => throw new \RuntimeException(
                "Widget '{$widgetClass}' not yet supported by HtmlFrontend"
            ),
        };

        $styleName = $element->getAttribute('style');
        if ($styleName !== '') {
            $style = $this->resolver->resolveStyle($styleName);
            if ($style !== null) {
                $widget->style($style);
            }
        }

        return $widget;
    }

    private function makeContainerWidget(string $widgetClass, DOMElement $element): Widget
    {
        return new $widgetClass(...$this->buildChildren($element));
    }

    private function makeText(DOMElement $element): Text
    {
        $binding = $this->resolver->resolveBinding($element->getAttribute('bind'));
        if ($binding !== null) {
            return new Text($binding);
        }
        $content = trim($element->textContent);
        if ($content === '') {
            throw new \RuntimeException(
                '<text> requires either bind="key" or literal text content'
            );
        }
        return new Text($content);
    }

    private function makeButton(DOMElement $element): Button
    {
        $label = trim($element->textContent);
        if ($label === '') {
            throw new \RuntimeException(
                '<button> requires text content as the label'
            );
        }
        [$action, $actionName] = $this->resolver->resolveAction(
            $element->getAttribute('onclick')
        );
        $button = new Button($label, $action);
        if ($actionName !== null) {
            $button->actionName($actionName);
        }
        return $button;
    }

    private function makeImage(DOMElement $element): Image
    {
        $src = $element->getAttribute('src');
        if ($src === '') {
            throw new \RuntimeException('<image> requires a "src" attribute');
        }
        return new Image($src);
    }

    private function makeTextInput(DOMElement $element): TextInput
    {
        $binding = $this->resolver->resolveBinding($element->getAttribute('bind'));
        if ($binding === null) {
            throw new \RuntimeException('<textinput> requires bind="key"');
        }
        $ns = NamedState::instance();
        $stateId = $ns->stateId($binding->name);
        if ($stateId === null) {
            throw new \RuntimeException("StateId not found for binding '{$binding->name}'");
        }
        $placeholder = $element->getAttribute('placeholder');
        [$action, $actionName] = $this->resolver->resolveAction(
            $element->getAttribute('onchange')
        );
        $input = new TextInput($stateId, $placeholder, $action);
        if ($actionName !== null) {
            $input->actionName($actionName);
        }
        return $input;
    }

    private function makeToggle(DOMElement $element): Toggle
    {
        $label = trim($element->textContent) ?: '';
        $binding = $this->resolver->resolveBinding($element->getAttribute('bind'));
        [$action, $actionName] = $this->resolver->resolveAction(
            $element->getAttribute('ontoggle')
        );
        $toggle = new Toggle($label, $binding, $action);
        if ($actionName !== null) {
            $toggle->actionName($actionName);
        }
        return $toggle;
    }

    private function makeSlider(DOMElement $element): Slider
    {
        $binding = $this->resolver->resolveBinding($element->getAttribute('bind'));
        if ($binding === null) {
            throw new \RuntimeException('<slider> requires bind="key"');
        }
        $min = AttributeResolver::parseFloat($element->getAttribute('min'), 0.0);
        $max = AttributeResolver::parseFloat($element->getAttribute('max'), 100.0);
        $step = AttributeResolver::parseFloat($element->getAttribute('step'), 1.0);
        [$action, $actionName] = $this->resolver->resolveAction(
            $element->getAttribute('onchange')
        );
        $slider = new Slider($binding, $min, $max, $step, $action);
        if ($actionName !== null) {
            $slider->actionName($actionName);
        }
        return $slider;
    }
}
