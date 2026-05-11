<?php

declare(strict_types=1);

namespace Perry\Codegen;

use Perry\Build\Target;
use Perry\UI\Styling\StyleProperty;
use Perry\UI\Widget;
use Perry\UI\Widget\AppContainer;
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
use Perry\UI\WidgetKind;

final class AndroidXmlBackend extends CodegenBackend
{
    private int $indent = 0;

    /** @var array<string, string> */
    private array $colors = [];

    /** @var array<array{id: string, method: string, action: \Perry\UI\Action}> */
    private array $buttonActions = [];

    public function name(): string
    {
        return 'android-xml';
    }

    public function supports(Target $target): bool
    {
        return $target === Target::Android;
    }

    private ?int $windowWidth = null;
    private ?int $windowHeight = null;

    public function generate(Widget $root): string
    {
        $this->indent = 1;
        $this->colors = [];
        $this->buttonActions = [];
        $this->windowWidth = null;
        $this->windowHeight = null;

        if ($root instanceof AppContainer) {
            $this->windowWidth = $root->windowWidth();
            $this->windowHeight = $root->windowHeight();
            $body = $this->generateWidget($root->content());
        } else {
            $body = $this->generateWidget($root);
        }

        return <<<XML
        <?xml version="1.0" encoding="utf-8"?>
        <LinearLayout xmlns:android="http://schemas.android.com/apk/res/android"
            xmlns:app="http://schemas.android.com/apk/res-auto"
            android:layout_width="match_parent"
            android:layout_height="match_parent"
            android:orientation="vertical"
            android:gravity="center_horizontal|top">

        {$body}
        </LinearLayout>
        XML;
    }

    /** @return array<string, string> */
    public function getColors(): array
    {
        return $this->colors;
    }

    private function registerColor(string $hex): string
    {
        $hex = strtolower(ltrim($hex, '#'));
        if (strlen($hex) === 3) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }
        $name = 'perry_color_' . preg_replace('/[^a-f0-9]/', '', $hex);
        $this->colors[$name] = '#' . $hex;
        return '@color/' . $name;
    }

    private function generateWidget(Widget $widget): string
    {
        if ($widget instanceof AppContainer) {
            return $this->generateWidget($widget->content());
        }

        return match ($widget->kind()) {
            WidgetKind::Text => $this->generateText($widget),
            WidgetKind::Button => $this->generateButton($widget),
            WidgetKind::VStack => $this->generateVStack($widget),
            WidgetKind::HStack => $this->generateHStack($widget),
            WidgetKind::Spacer => $this->indentStr() . "<Space\n{$this->indentStr()}    android:layout_width=\"0dp\"\n{$this->indentStr()}    android:layout_height=\"0dp\"\n{$this->indentStr()}    android:layout_weight=\"1\" />\n",
            WidgetKind::Image => $this->generateImage($widget),
            WidgetKind::ScrollView => $this->generateScrollView($widget),
            WidgetKind::TextInput => $this->generateTextInput($widget),
            WidgetKind::TextEditor => $this->generateTextEditor($widget),
            WidgetKind::Slider => $this->generateSlider($widget),
            WidgetKind::ListWidget => $this->generateListWidget($widget),
            WidgetKind::NavigationView => $this->generateNavigationView($widget),
            WidgetKind::TabView => $this->generateTabView($widget),
            WidgetKind::Toggle => $this->generateToggle($widget),
            WidgetKind::WebView => $this->generateWebView($widget),
            default => '',
        };
    }

    private function generateText(Text $widget): string
    {
        $text = htmlspecialchars($widget->content());
        $binding = $widget->getBinding();
        $idLine = '';
        if ($binding !== null) {
            $idLine = "{$this->indentStr()}    android:id=\"@+id/tv_{$binding->name}\"\n";
        }

        $style = $widget->getStyle();
        $attrs = '';
        if ($style !== null) {
            $attrs = $this->generateStyleAttributes($style, 'text');
        }

        return "{$this->indentStr()}<TextView\n"
            . "{$this->indentStr()}    android:layout_width=\"wrap_content\"\n"
            . "{$this->indentStr()}    android:layout_height=\"wrap_content\"\n"
            . $idLine
            . $attrs
            . "{$this->indentStr()}    android:text=\"{$text}\" />";
    }

    private function generateButton(Button $widget): string
    {
        $label = htmlspecialchars($widget->label());
        $btnId = $this->buttonLabelToId($widget->label());
        $isNegate = ($widget->label() === '+/-');

        $style = $widget->getStyle();
        $w = 40;
        $h = 40;
        $radius = 20;
        $fontSize = 18;
        $bgColor = null;
        $fgColor = null;

        if ($style !== null) {
            if ($style->has(StyleProperty::Width)) {
                $w = (int) $style->get(StyleProperty::Width);
            }
            if ($style->has(StyleProperty::Height)) {
                $h = (int) $style->get(StyleProperty::Height);
            }
            if ($style->has(StyleProperty::CornerRadius)) {
                $radius = (int) $style->get(StyleProperty::CornerRadius);
            }
            if ($style->has(StyleProperty::FontSize)) {
                $fontSize = (int) $style->get(StyleProperty::FontSize);
            }
            if ($style->has(StyleProperty::BackgroundColor)) {
                $bgColor = $this->registerColor((string) $style->get(StyleProperty::BackgroundColor));
            }
            if ($style->has(StyleProperty::ForegroundColor)) {
                $fgColor = $this->registerColor((string) $style->get(StyleProperty::ForegroundColor));
            }
        }

        if ($isNegate) {
            $fontSize = 14;
        }

        // Generate android:onClick when button has an action
        $onClick = '';
        $action = $widget->getAction();
        if ($action !== null) {
            $methodName = $this->actionToMethodName($btnId);
            $onClick = "{$this->indentStr()}    android:onClick=\"{$methodName}\"\n";
            $this->buttonActions[] = ['id' => $btnId, 'method' => $methodName, 'action' => $action];
        }

        $id = "{$this->indentStr()}    android:id=\"@+id/{$btnId}\"\n";
        $size = "{$this->indentStr()}    android:layout_width=\"{$w}dp\"\n"
            . "{$this->indentStr()}    android:layout_height=\"{$h}dp\"\n";
        $minClear = "{$this->indentStr()}    android:minHeight=\"0dp\"\n"
            . "{$this->indentStr()}    android:minWidth=\"0dp\"\n";
        $padding = "{$this->indentStr()}    android:paddingTop=\"0dp\"\n"
            . "{$this->indentStr()}    android:paddingBottom=\"0dp\"\n";
        $gravity = "{$this->indentStr()}    android:gravity=\"center\"\n";
        $corner = "{$this->indentStr()}    app:cornerRadius=\"{$radius}dp\"\n";
        $textSize = "{$this->indentStr()}    android:textSize=\"{$fontSize}sp\"\n";
        $bg = $bgColor !== null ? "{$this->indentStr()}    android:backgroundTint=\"{$bgColor}\"\n" : '';
        $fg = $fgColor !== null ? "{$this->indentStr()}    android:textColor=\"{$fgColor}\"\n" : '';

        return "{$this->indentStr()}<Button\n"
            . $id . $size . $minClear . $padding . $gravity . $corner . $textSize . $bg . $fg . $onClick
            . "{$this->indentStr()}    android:text=\"{$label}\" />";
    }

    private function buttonLabelToId(string $label): string
    {
        return match ($label) {
            '=' => 'btn_eq',
            '+' => 'btn_plus',
            '-' => 'btn_minus',
            'x' => 'btn_mul',
            '÷' => 'btn_div',
            '.' => 'btn_dot',
            '%' => 'btn_percent',
            '+/-' => 'btn_negate',
            'C' => 'btn_clear',
            '⌫' => 'btn_backspace',
            default => 'btn_' . preg_replace('/[^a-zA-Z0-9]/', '', $label),
        };
    }

    private function generateVStack(VStack $widget): string
    {
        $this->indent++;
        $children = $this->generateChildren($widget->children());
        $this->indent--;

        $style = $widget->getStyle();
        $attrs = '';
        if ($style !== null) {
            $attrs = $this->generateStyleAttributes($style, 'layout');
        }

        return <<<XML
        {$this->indentStr()}<LinearLayout
        {$this->indentStr()}    android:layout_width="match_parent"
        {$this->indentStr()}    android:layout_height="wrap_content"
        {$this->indentStr()}    android:orientation="vertical"
        {$attrs}{$this->indentStr()}>
        {$children}
        {$this->indentStr()}</LinearLayout>
        XML;
    }

    private function generateHStack(HStack $widget): string
    {
        $this->indent++;
        $children = $this->generateChildren($widget->children());
        $this->indent--;

        $style = $widget->getStyle();
        $attrs = '';
        if ($style !== null) {
            $attrs = $this->generateStyleAttributes($style, 'layout');
        }

        return <<<XML
        {$this->indentStr()}<LinearLayout
        {$this->indentStr()}    android:layout_width="match_parent"
        {$this->indentStr()}    android:layout_height="wrap_content"
        {$this->indentStr()}    android:orientation="horizontal"
        {$attrs}{$this->indentStr()}>
        {$children}
        {$this->indentStr()}</LinearLayout>
        XML;
    }

    private function generateImage(Image $widget): string
    {
        $src = htmlspecialchars($widget->source());
        return <<<XML
        {$this->indentStr()}<ImageView
        {$this->indentStr()}    android:layout_width="wrap_content"
        {$this->indentStr()}    android:layout_height="wrap_content"
        {$this->indentStr()}    android:src="{$src}" />
        XML;
    }

    private function generateScrollView(ScrollView $widget): string
    {
        $this->indent++;
        $children = $this->generateChildren($widget->children());
        $this->indent--;

        return "{$this->indentStr()}<ScrollView\n"
            . "{$this->indentStr()}    android:layout_width=\"match_parent\"\n"
            . "{$this->indentStr()}    android:layout_height=\"match_parent\">\n"
            . "{$this->indentStr()}    <LinearLayout\n"
            . "{$this->indentStr()}        android:layout_width=\"match_parent\"\n"
            . "{$this->indentStr()}        android:layout_height=\"wrap_content\"\n"
            . "{$this->indentStr()}        android:orientation=\"vertical\">\n"
            . $children . "\n"
            . "{$this->indentStr()}    </LinearLayout>\n"
            . "{$this->indentStr()}</ScrollView>";
    }

    private function generateTextInput(TextInput $widget): string
    {
        $hint = htmlspecialchars($widget->placeholder());
        $state = $widget->value();
        $idLine = '';
        if ($state !== null) {
            $idLine = "{$this->indentStr()}    android:id=\"@+id/et_{$state->name}\"\n";
        }

        return "{$this->indentStr()}<EditText\n"
            . "{$this->indentStr()}    android:layout_width=\"match_parent\"\n"
            . "{$this->indentStr()}    android:layout_height=\"wrap_content\"\n"
            . $idLine
            . "{$this->indentStr()}    android:hint=\"{$hint}\" />";
    }

    private function generateTextEditor(\Perry\UI\Widget\TextEditor $widget): string
    {
        $hint = htmlspecialchars($widget->placeholder());
        $binding = $widget->getBinding();
        $idLine = '';
        if ($binding !== null) {
            $idLine = "{$this->indentStr()}    android:id=\"@+id/te_{$binding->name}\"\n";
        }

        $style = $widget->getStyle();
        $attrs = '';
        if ($style !== null) {
            $attrs = $this->generateStyleAttributes($style, 'text');
        }

        return "{$this->indentStr()}<EditText\n"
            . "{$this->indentStr()}    android:layout_width=\"match_parent\"\n"
            . "{$this->indentStr()}    android:layout_height=\"wrap_content\"\n"
            . $idLine
            . $attrs
            . "{$this->indentStr()}    android:hint=\"{$hint}\"\n"
            . "{$this->indentStr()}    android:inputType=\"textMultiLine\"\n"
            . "{$this->indentStr()}    android:gravity=\"top|start\" />";
    }

    private function generateWebView(WebView $widget): string
    {
        $html = htmlspecialchars($widget->html());

        return "{$this->indentStr()}<WebView\n"
            . "{$this->indentStr()}    android:id=\"@+id/webview\"\n"
            . "{$this->indentStr()}    android:layout_width=\"match_parent\"\n"
            . "{$this->indentStr()}    android:layout_height=\"match_parent\"\n"
            . "{$this->indentStr()}    android:layout_weight=\"1\" />";
    }

    private function generateToggle(Toggle $widget): string
    {
        $label = htmlspecialchars($widget->label());
        $binding = $widget->getIsOn();
        $idLine = '';
        if ($binding !== null) {
            $idLine = "{$this->indentStr()}    android:id=\"@+id/sw_{$binding->name}\"\n";
        }

        return "{$this->indentStr()}<Switch\n"
            . "{$this->indentStr()}    android:layout_width=\"wrap_content\"\n"
            . "{$this->indentStr()}    android:layout_height=\"wrap_content\"\n"
            . $idLine
            . "{$this->indentStr()}    android:text=\"{$label}\" />";
    }

    private function actionToMethodName(string $btnId): string
    {
        return 'on' . str_replace('_', '', ucwords($btnId, '_'));
    }

    private function generateChildren(array $children): string
    {
        $parts = [];
        foreach ($children as $child) {
            $parts[] = $this->generateWidget($child);
        }
        return implode("\n", $parts);
    }

    private function generateStyleAttributes(\Perry\UI\Styling\Style $style, string $context): string
    {
        $attrs = [];

        if ($style->has(StyleProperty::BackgroundColor)) {
            $hex = $style->get(StyleProperty::BackgroundColor);
            if (is_string($hex)) {
                $colorRef = $this->registerColor($hex);
                if ($context === 'button') {
                    $attrs[] = "{$this->indentStr()}    android:backgroundTint=\"{$colorRef}\"";
                } else {
                    $attrs[] = "{$this->indentStr()}    android:background=\"{$colorRef}\"";
                }
            }
        }

        if ($style->has(StyleProperty::ForegroundColor)) {
            $hex = $style->get(StyleProperty::ForegroundColor);
            if (is_string($hex)) {
                $colorRef = $this->registerColor($hex);
                $attrs[] = "{$this->indentStr()}    android:textColor=\"{$colorRef}\"";
            }
        }

        if ($style->has(StyleProperty::FontSize)) {
            $size = $style->get(StyleProperty::FontSize);
            if (is_numeric($size)) {
                $attrs[] = "{$this->indentStr()}    android:textSize=\"{$size}sp\"";
            }
        }

        if ($style->has(StyleProperty::Width)) {
            $w = $style->get(StyleProperty::Width);
            if (is_numeric($w)) {
                $dp = (int) $w;
                $attrs[] = "{$this->indentStr()}    android:minWidth=\"{$dp}dp\"";
            }
        }

        if ($style->has(StyleProperty::Height)) {
            $h = $style->get(StyleProperty::Height);
            if (is_numeric($h)) {
                $dp = (int) $h;
                $attrs[] = "{$this->indentStr()}    android:minHeight=\"{$dp}dp\"";
            }
        }

        if ($style->has(StyleProperty::CornerRadius)) {
            $radius = $style->get(StyleProperty::CornerRadius);
            if (is_numeric($radius)) {
                $r = (int) $radius;
                if ($context === 'button') {
                } elseif ($context === 'layout') {
                    $attrs[] = "{$this->indentStr()}    android:padding=\"{$r}dp\"";
                }
            }
        }

        if ($style->has(StyleProperty::Padding)) {
            $p = $style->get(StyleProperty::Padding);
            if (is_numeric($p)) {
                $dp = (int) $p;
                $attrs[] = "{$this->indentStr()}    android:padding=\"{$dp}dp\"";
            }
        }

        $padTop = $style->has(StyleProperty::PaddingTop) ? (int) $style->get(StyleProperty::PaddingTop) : null;
        $padBottom = $style->has(StyleProperty::PaddingBottom) ? (int) $style->get(StyleProperty::PaddingBottom) : null;
        $padLeft = $style->has(StyleProperty::PaddingLeading) ? (int) $style->get(StyleProperty::PaddingLeading) : null;
        $padRight = $style->has(StyleProperty::PaddingTrailing) ? (int) $style->get(StyleProperty::PaddingTrailing) : null;
        if ($padTop !== null || $padBottom !== null || $padLeft !== null || $padRight !== null) {
            $t = $padTop ?? 0;
            $b = $padBottom ?? 0;
            $l = $padLeft ?? 0;
            $r = $padRight ?? 0;
            $attrs[] = "{$this->indentStr()}    android:paddingLeft=\"{$l}dp\"";
            $attrs[] = "{$this->indentStr()}    android:paddingRight=\"{$r}dp\"";
            $attrs[] = "{$this->indentStr()}    android:paddingTop=\"{$t}dp\"";
            $attrs[] = "{$this->indentStr()}    android:paddingBottom=\"{$b}dp\"";
        }

        if ($style->has(StyleProperty::Opacity)) {
            $opacity = $style->get(StyleProperty::Opacity);
            if (is_numeric($opacity)) {
                $attrs[] = "{$this->indentStr()}    android:alpha=\"{$opacity}\"";
            }
        }

        // Margin
        if ($style->has(StyleProperty::Margin)) {
            $v = $style->get(StyleProperty::Margin);
            $attrs[] = "{$this->indentStr()}    android:layout_margin=\"{$v}dp\"";
        }

        // Border (uses background with shape drawable - simplified)
        if ($style->has(StyleProperty::BorderWidth) || $style->has(StyleProperty::BorderColor)) {
            $width = $style->has(StyleProperty::BorderWidth) ? (int)$style->get(StyleProperty::BorderWidth) : 1;
            $color = $style->has(StyleProperty::BorderColor) ? $this->registerColor($style->get(StyleProperty::BorderColor)) : '@color/perry_color_000000';
            $attrs[] = "{$this->indentStr()}    android:background=\"{$color}\"";
        }

        // Shadow (use elevation as approximation)
        if ($style->has(StyleProperty::ShadowRadius)) {
            $radius = (int)$style->get(StyleProperty::ShadowRadius);
            $attrs[] = "{$this->indentStr()}    android:elevation=\"{$radius}dp\"";
        }

        // Font
        if ($style->has(StyleProperty::FontWeight)) {
            $v = $style->get(StyleProperty::FontWeight);
            $weight = ($v === 'bold' || $v === '700') ? 'bold' : 'normal';
            $attrs[] = "{$this->indentStr()}    android:textStyle=\"{$weight}\"";
        }
        if ($style->has(StyleProperty::FontFamily)) {
            $v = $style->get(StyleProperty::FontFamily);
            $attrs[] = "{$this->indentStr()}    android:fontFamily=\"{$v}\"";
        }
        if ($style->has(StyleProperty::TextAlignment)) {
            $v = $style->get(StyleProperty::TextAlignment);
            $map = ['left' => 'left', 'center' => 'center', 'right' => 'right'];
            $align = $map[$v] ?? 'left';
            $attrs[] = "{$this->indentStr()}    android:gravity=\"{$align}\"";
        }
        if ($style->has(StyleProperty::TextDecoration)) {
            $v = $style->get(StyleProperty::TextDecoration);
            if ($v === 'underline') {
                $attrs[] = "{$this->indentStr()}    android:textStyle=\"bold\""; // Android lacks underline in XML
            }
        }
        if ($style->has(StyleProperty::LineSpacing)) {
            $v = $style->get(StyleProperty::LineSpacing);
            $attrs[] = "{$this->indentStr()}    android:lineSpacingExtra=\"{$v}sp\"";
        }
        if ($style->has(StyleProperty::LetterSpacing)) {
            $v = $style->get(StyleProperty::LetterSpacing);
            $attrs[] = "{$this->indentStr()}    android:letterSpacing=\"{$v}\"";
        }

        // Flex layout
        if ($style->has(StyleProperty::FlexGrow)) {
            $v = (int)$style->get(StyleProperty::FlexGrow);
            $attrs[] = "{$this->indentStr()}    android:layout_weight=\"{$v}\"";
        }
        if ($style->has(StyleProperty::Gap)) {
            $v = (int)$style->get(StyleProperty::Gap);
            $attrs[] = "{$this->indentStr()}    android:spacing=\"{$v}dp\"";
        }

        // Transform
        if ($style->has(StyleProperty::Rotate)) {
            $v = $style->get(StyleProperty::Rotate);
            $attrs[] = "{$this->indentStr()}    android:rotation=\"{$v}\"";
        }
        if ($style->has(StyleProperty::Scale)) {
            $v = $style->get(StyleProperty::Scale);
            $attrs[] = "{$this->indentStr()}    android:scaleX=\"{$v}\"";
            $attrs[] = "{$this->indentStr()}    android:scaleY=\"{$v}\"";
        }
        if ($style->has(StyleProperty::TranslateX)) {
            $v = (int)$style->get(StyleProperty::TranslateX);
            $attrs[] = "{$this->indentStr()}    android:translationX=\"{$v}dp\"";
        }
        if ($style->has(StyleProperty::TranslateY)) {
            $v = (int)$style->get(StyleProperty::TranslateY);
            $attrs[] = "{$this->indentStr()}    android:translationY=\"{$v}dp\"";
        }

        // Max dimensions
        if ($style->has(StyleProperty::MaxWidth)) {
            $v = (int)$style->get(StyleProperty::MaxWidth);
            $attrs[] = "{$this->indentStr()}    android:maxWidth=\"{$v}dp\"";
        }
        if ($style->has(StyleProperty::MaxHeight)) {
            $v = (int)$style->get(StyleProperty::MaxHeight);
            $attrs[] = "{$this->indentStr()}    android:maxHeight=\"{$v}dp\"";
        }

        // Min dimensions
        if ($style->has(StyleProperty::MinWidth)) {
            $v = (int)$style->get(StyleProperty::MinWidth);
            $attrs[] = "{$this->indentStr()}    android:minWidth=\"{$v}dp\"";
        }
        if ($style->has(StyleProperty::MinHeight)) {
            $v = (int)$style->get(StyleProperty::MinHeight);
            $attrs[] = "{$this->indentStr()}    android:minHeight=\"{$v}dp\"";
        }

        if (empty($attrs)) {
            return '';
        }

        return implode("\n", $attrs) . "\n";
    }

    private function generateSlider(Slider $widget): string
    {
        $id = $this->nextId();
        $binding = $widget->value();
        $name = $binding->name;
        $min = $widget->min();
        $max = $widget->max();
        $step = $widget->step();

        $action = $widget->getOnChange();
        if ($action !== null) {
            $methodName = 'on' . ucfirst($name) . 'Change';
            $this->buttonActions[] = ['id' => $name, 'method' => $methodName, 'action' => $action];
        }

        return <<<XML
        {$this->indentStr()}<SeekBar
            android:id="@+id/{$id}"
            android:layout_width="match_parent"
            android:layout_height="wrap_content"
            android:minHeight="{$min}dp"
            android:maxHeight="{$max}dp"
            android:stepSize="{$step}" />
        XML;
    }

    private function generateListWidget(\Perry\UI\Widget\ListWidget $widget): string
    {
        $this->indent++;
        $children = $this->generateChildren($widget->items());
        $this->indent--;
        return "{$this->indentStr()}<LinearLayout\n"
            . "{$this->indentStr()}    android:layout_width=\"match_parent\"\n"
            . "{$this->indentStr()}    android:layout_height=\"wrap_content\"\n"
            . "{$this->indentStr()}    android:orientation=\"vertical\">\n"
            . $children
            . "{$this->indentStr()}</LinearLayout>";
    }

    private function generateNavigationView(NavigationView $widget): string
    {
        $this->indent++;
        $children = $this->generateChildren($widget->screens());
        $this->indent--;
        return "{$this->indentStr()}<FrameLayout\n"
            . "{$this->indentStr()}    android:layout_width=\"match_parent\"\n"
            . "{$this->indentStr()}    android:layout_height=\"match_parent\">\n"
            . $children
            . "{$this->indentStr()}</FrameLayout>";
    }

    private function generateTabView(TabView $widget): string
    {
        $this->indent++;
        $children = $this->generateChildren($widget->tabs());
        $this->indent--;
        return "{$this->indentStr()}<LinearLayout\n"
            . "{$this->indentStr()}    android:layout_width=\"match_parent\"\n"
            . "{$this->indentStr()}    android:layout_height=\"match_parent\"\n"
            . "{$this->indentStr()}    android:orientation=\"vertical\">\n"
            . $children
            . "{$this->indentStr()}</LinearLayout>";
    }

    private function indentStr(): string
    {
        return str_repeat('    ', $this->indent);
    }

    /** @return StyleProperty[] */
    public function supportedStyleProperties(): array
    {
        return [
            StyleProperty::Width, StyleProperty::Height, StyleProperty::CornerRadius,
            StyleProperty::FontSize, StyleProperty::BackgroundColor, StyleProperty::ForegroundColor,
            StyleProperty::Padding, StyleProperty::PaddingTop, StyleProperty::PaddingBottom,
            StyleProperty::PaddingLeading, StyleProperty::PaddingTrailing, StyleProperty::Opacity,
            StyleProperty::Margin, StyleProperty::BorderWidth, StyleProperty::BorderColor,
            StyleProperty::ShadowRadius, StyleProperty::FontWeight, StyleProperty::FontFamily,
            StyleProperty::TextAlignment, StyleProperty::TextDecoration, StyleProperty::LineSpacing,
            StyleProperty::MaxWidth, StyleProperty::MaxHeight,
            StyleProperty::MinWidth, StyleProperty::MinHeight,
            StyleProperty::LetterSpacing,
            StyleProperty::FlexDirection, StyleProperty::JustifyContent, StyleProperty::AlignItems,
            StyleProperty::FlexWrap, StyleProperty::Gap, StyleProperty::FlexGrow, StyleProperty::FlexShrink,
            // Transform
            StyleProperty::Rotate, StyleProperty::Scale, StyleProperty::TranslateX, StyleProperty::TranslateY,
        ];
    }

    public function generateMainActivity(string $outputName): string
    {
        $methods = '';
        foreach ($this->buttonActions as $item) {
            $action = $item['action'];
            $methodName = $item['method'];

            $methodBody = $this->generateActionMethodBody($action);

            $methods .= <<<KOTLIN

    fun {$methodName}(view: View) {
{$methodBody}
    }

KOTLIN;
        }

        $package = 'com.perry.' . strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $outputName ?: 'app'));

        return <<<KOTLIN
package {$package}

import android.os.Bundle
import android.view.View
import androidx.appcompat.app.AppCompatActivity

class MainActivity : AppCompatActivity() {

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        setContentView(R.layout.activity_main)
    }
{$methods}}

KOTLIN;
    }

    private function generateActionMethodBody(\Perry\UI\Action $action): string
    {
        if ($action->type === \Perry\UI\ActionType::Custom) {
            // Custom code is expected to be valid Kotlin
            return '        ' . str_replace("\n", "\n        ", $action->customCode);
        }

        if ($action->type === \Perry\UI\ActionType::Closure) {
            // Bypass broken KotlinGenerator - use IR and generate correct Kotlin
            return $this->generateFromIr($action->getIr());
        }

        if ($action->type === \Perry\UI\ActionType::SetValue) {
            $targetName = $action->target->name;
            $value = $action->value;
            $kotlinValue = $this->formatValueForKotlin($value);
            return "        {$targetName} = {$kotlinValue}";
        }

        if ($action->type === \Perry\UI\ActionType::Append) {
            $targetName = $action->target->name;
            $value = $action->value;
            return '        ' . $targetName . ' += "' . addslashes($value) . '"';
        }

        if ($action->type === \Perry\UI\ActionType::Clear) {
            $targetName = $action->target->name;
            return "        {$targetName} = \"\"";
        }

        return '        // Action type not yet supported for Android: ' . $action->type->value;
    }

    private function generateFromIr($ir): string
    {
        // Generate Kotlin code from IR (Program object)
        // This bypasses the broken KotlinGenerator
        $lines = [];
        
        // Get statements from IR
        // Program has a $statements property (Perry IR)
        if (property_exists($ir, 'statements')) {
            $stmts = $ir->statements;
        } elseif (method_exists($ir, 'getStmts')) {
            $stmts = $ir->getStmts();
        } elseif (property_exists($ir, 'stmts')) {
            $stmts = $ir->stmts;
        } else {
            return '        // Could not extract statements from IR';
        }
        
        foreach ($stmts as $stmt) {
            $line = $this->generateStmtKotlin($stmt, 2);
            if ($line !== '') {
                $lines[] = $line;
            }
        }
        
        return implode("\n", $lines);
    }

    private function generateStmtKotlin($stmt, int $indentLevel): string
    {
        $indent = str_repeat('        ', $indentLevel);
        
        // Handle Expression statement
        if ($stmt instanceof \PhpParser\Node\Stmt\Expression) {
            return $indent . $this->generateExprKotlin($stmt->expr);
        }
        
        // Handle If statement
        if ($stmt instanceof \PhpParser\Node\Stmt\If_) {
            $cond = $this->generateExprKotlin($stmt->cond);
            $thenLines = [];
            foreach ($stmt->stmts as $s) {
                $thenLines[] = $this->generateStmtKotlin($s, $indentLevel + 1);
            }
            $result = $indent . "if ({$cond}) {\n";
            $result .= implode("\n", array_filter($thenLines)) . "\n";
            $result .= $indent . "}";
            
            // Handle else
            if ($stmt->else !== null) {
                $elseLines = [];
                foreach ($stmt->else->stmts as $s) {
                    $elseLines[] = $this->generateStmtKotlin($s, $indentLevel + 1);
                }
                $result .= " else {\n";
                $result .= implode("\n", array_filter($elseLines)) . "\n";
                $result .= $indent . "}";
            }
            
            return $result;
        }
        
        return $indent . '// Unsupported statement: ' . get_class($stmt);
    }

    private function generateExprKotlin($expr): string
    {
        // Assignment
        if ($expr instanceof \PhpParser\Node\Expr\Assign) {
            $var = $this->generateExprKotlin($expr->var);
            $value = $this->generateExprKotlin($expr->expr);
            return "{$var} = {$value}";
        }
        
        // Variable
        if ($expr instanceof \PhpParser\Node\Expr\Variable) {
            return $expr->name;
        }
        
        // String literal
        if ($expr instanceof \PhpParser\Node\Scalar\String_) {
            return '"' . addslashes($expr->value) . '"';
        }
        
        // Int literal
        if ($expr instanceof \PhpParser\Node\Scalar\LNumber) {
            return (string) $expr->value;
        }
        
        // Float literal
        if ($expr instanceof \PhpParser\Node\Scalar\DNumber) {
            $str = (string) $expr->value;
            if (!str_contains($str, '.')) {
                $str .= '.0';
            }
            return $str;
        }
        
        // Method call
        if ($expr instanceof \PhpParser\Node\Expr\MethodCall) {
            $var = $this->generateExprKotlin($expr->var);
            $method = $expr->name->name;
            $args = [];
            foreach ($expr->args as $arg) {
                $args[] = $this->generateExprKotlin($arg->value);
            }
            $argsStr = implode(', ', $args);
            
            // Map PHP methods to Kotlin
            $methodMap = [
                'toDoubleOrNull' => 'toDoubleOrNull',
                'isEmpty' => 'isEmpty()',
                'dropLast' => 'dropLast',
                'length' => 'length',
            ];
            
            $kotlinMethod = $methodMap[$method] ?? $method;
            return "{$var}.{$kotlinMethod}({$argsStr})";
        }
        
        // Ternary
        if ($expr instanceof \PhpParser\Node\Expr\Ternary) {
            $cond = $this->generateExprKotlin($expr->cond);
            $if = $this->generateExprKotlin($expr->if);
            $else = $this->generateExprKotlin($expr->else);
            return "if ({$cond}) {$if} else {$else}";
        }
        
        // Binary op
        if ($expr instanceof \PhpParser\Node\Expr\BinaryOp) {
            $left = $this->generateExprKotlin($expr->left);
            $right = $this->generateExprKotlin($expr->right);
            $op = $this->getBinaryOpKotlin($expr);
            return "{$left} {$op} {$right}";
        }
        
        return '/* Unsupported expression: ' . get_class($expr) . ' */';
    }

    private function getBinaryOpKotlin($expr): string
    {
        if ($expr instanceof \PhpParser\Node\Expr\BinaryOp\Concat) return '+';
        if ($expr instanceof \PhpParser\Node\Expr\BinaryOp\Plus) return '+';
        if ($expr instanceof \PhpParser\Node\Expr\BinaryOp\Minus) return '-';
        if ($expr instanceof \PhpParser\Node\Expr\BinaryOp\Muliply) return '*';
        if ($expr instanceof \PhpParser\Node\Expr\BinaryOp\Divide) return '/';
        if ($expr instanceof \PhpParser\Node\Expr\BinaryOp\Modulo) return '%';
        if ($expr instanceof \PhpParser\Node\Expr\BinaryOp\Equal) return '==';
        if ($expr instanceof \PhpParser\Node\Expr\BinaryOp\NotEqual) return '!=';
        if ($expr instanceof \PhpParser\Node\Expr\BinaryOp\Greater) return '>';
        if ($expr instanceof \PhpParser\Node\Expr\BinaryOp\GreaterOrEqual) return '>=';
        if ($expr instanceof \PhpParser\Node\Expr\BinaryOp\Smaller) return '<';
        if ($expr instanceof \PhpParser\Node\Expr\BinaryOp\SmallerOrEqual) return '<=';
        return '/* unknown op */';
    }

    private function formatValueForKotlin(mixed $value): string
    {
        if (is_string($value)) {
            return '"' . addslashes($value) . '"';
        }
        if (is_int($value) || is_float($value)) {
            $str = (string) $value;
            if (is_float($value) && !str_contains($str, '.')) {
                $str .= '.0';
            }
            return $str;
        }
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }
        return (string) $value;
    }

    private function indentLines(string $code, int $level): string
    {
        $lines = explode("\n", $code);
        $indent = str_repeat('    ', $level);
        return implode("\n", array_map(fn($line) => $indent . $line, $lines));
    }
}
