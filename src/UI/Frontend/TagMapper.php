<?php

declare(strict_types=1);

namespace Perry\UI\Frontend;

use Perry\UI\Widget;
use Perry\UI\Widget\Button;
use Perry\UI\Widget\HStack;
use Perry\UI\Widget\Image;
use Perry\UI\Widget\ListWidget;
use Perry\UI\Widget\NavigationView;
use Perry\UI\Widget\ScrollView;
use Perry\UI\Widget\Slider;
use Perry\UI\Widget\Spacer;
use Perry\UI\Widget\TabView;
use Perry\UI\Widget\Text;
use Perry\UI\Widget\TextEditor;
use Perry\UI\Widget\TextInput;
use Perry\UI\Widget\Toggle;
use Perry\UI\Widget\VStack;
use Perry\UI\Widget\WebView;

/**
 * TagMapper maps HTML tag names to Perry Widget class names.
 *
 * Used by HtmlFrontend to instantiate the correct Widget subclass
 * when parsing HTML DSL elements.
 */
final class TagMapper
{
    /** @var array<string, class-string<Widget>> */
    private static array $map = [
        'vstack' => VStack::class,
        'hstack' => HStack::class,
        'button' => Button::class,
        'text' => Text::class,
        'image' => Image::class,
        'spacer' => Spacer::class,
        'scrollview' => ScrollView::class,
        'textinput' => TextInput::class,
        'toggle' => Toggle::class,
        'slider' => Slider::class,
        'list' => ListWidget::class,
        'navigationview' => NavigationView::class,
        'tabview' => TabView::class,
        'texteditor' => TextEditor::class,
        'webview' => WebView::class,
    ];

    /**
     * Get the Widget class for a given HTML tag name.
     *
     * @return class-string<Widget>|null
     */
    public static function resolve(string $tagName): ?string
    {
        return self::$map[strtolower($tagName)] ?? null;
    }

    /**
     * Check if a tag name maps to a known widget.
     */
    public static function isWidget(string $tagName): bool
    {
        return isset(self::$map[strtolower($tagName)]);
    }
}
