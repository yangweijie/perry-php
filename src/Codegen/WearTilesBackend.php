<?php

declare(strict_types=1);

namespace Perry\Codegen;

use Perry\Build\Target;
use Perry\UI\Action;
use Perry\UI\ActionType;
use Perry\UI\Binding;
use Perry\UI\Styling\Style;
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

/**
 * Wear OS Tiles backend.
 * Generates Kotlin code using androidx.wear.tiles builder API
 * for Wear OS home/assistant tiles.
 */
final class WearTilesBackend extends CodegenBackend
{
    private int $indent = 0;
    private array $currentBindings = [];

    public function name(): string
    {
        return 'wear-tiles';
    }

    public function supports(Target $target): bool
    {
        return $target === Target::WearTiles;
    }

    public function generate(Widget $root): string
    {
        $this->indent = 0;
        $this->currentBindings = [];
        $stateFields = '';
        $widthCode = '';
        $heightCode = '';

        if ($root instanceof AppContainer) {
            $bindings = $root->bindings();
            $this->currentBindings = array_map(fn(Binding $b) => $b->name, $bindings);
            $stateFields = $this->generateStateFields($bindings);

            $w = $root->windowWidth();
            $h = $root->windowHeight();
            if ($w !== null) {
                $widthCode = "\n{$this->indentStr()}.setWidth({$w})";
            }
            if ($h !== null) {
                $heightCode = "\n{$this->indentStr()}.setHeight({$h})";
            }
        }

        $layout = $this->generateLayoutElement($root);

        return <<<KOTLIN
        package com.perry.tile

        import androidx.wear.tiles.RequestBuilders
        import androidx.wear.tiles.TileBuilders
        import androidx.wear.tiles.TileService
        import androidx.wear.tiles.TimelineBuilders
        import androidx.wear.tiles.layoutbuilders.LayoutElementBuilders
        import androidx.wear.tiles.layoutbuilders.LayoutElementBuilders.FontWeight
        import androidx.wear.tiles.layoutbuilders.LayoutElementBuilders.TextAlignment
        import com.google.common.util.concurrent.Futures
        import com.google.common.util.concurrent.ListenableFuture

        class PerryTile : TileService() {
        {$stateFields}
            override fun onTileRequest(request: RequestBuilders.TileRequest): ListenableFuture<TileBuilders.Tile> {
                return Futures.immediateFuture(
                    TileBuilders.Tile.Builder()
                        .setResources(
                            TileBuilders.Resources.Builder()
                                .setVersion("1")
                                .build()
                        ){$widthCode}{$heightCode}
                        .setTimeline(
                            TimelineBuilders.Timeline.fromLayoutElement(
        {$layout}
                            )
                        )
                        .build()
                )
            }
        }
        KOTLIN;
    }

    private function generateStateFields(array $bindings): string
    {
        $fields = [];
        foreach ($bindings as $binding) {
            $type = match (true) {
                is_int($binding->initialValue) => 'Int',
                is_float($binding->initialValue) => 'Float',
                is_bool($binding->initialValue) => 'Boolean',
                default => 'String',
            };
            $val = $this->formatValue($binding->initialValue);
            $fields[] = "    var {$binding->name}: {$type} = {$val}";
        }
        return $fields ? "\n" . implode("\n", $fields) . "\n" : '';
    }

    private function formatValue(mixed $value): string
    {
        if (is_string($value)) {
            return '"' . addslashes($value) . '"';
        }
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }
        return (string) $value;
    }

    private function generateLayoutElement(Widget $widget): string
    {
        if ($widget instanceof AppContainer) {
            $widget = $widget->content();
        }
        return $this->generateWidget($widget);
    }

    private function generateWidget(Widget $widget): string
    {
        return match ($widget->kind()) {
            WidgetKind::Text => $this->generateText($widget),
            WidgetKind::Button => $this->generateButton($widget),
            WidgetKind::VStack => $this->generateVStack($widget),
            WidgetKind::HStack => $this->generateHStack($widget),
            WidgetKind::Spacer => 'LayoutElementBuilders.Spacer.Builder().build()',
            WidgetKind::Image => $this->generateImage($widget),
            WidgetKind::ScrollView => $this->generateScrollView($widget),
            WidgetKind::TextInput => $this->generateTextInput($widget),
            WidgetKind::Toggle => $this->generateToggle($widget),
            WidgetKind::Slider => $this->generateUnsupported('Slider'),
            WidgetKind::TextEditor => $this->generateUnsupported('TextEditor'),
            WidgetKind::ListWidget => $this->generateListWidget($widget),
            WidgetKind::NavigationView => $this->generateUnsupported('Navigation'),
            WidgetKind::TabView => $this->generateUnsupported('TabView'),
            WidgetKind::WebView => $this->generateUnsupported('WebView'),
            default => '',
        };
    }

    private function generateUnsupported(string $name): string
    {
        $this->indent++;
        $result = "LayoutElementBuilders.Text.Builder()\n{$this->indentStr()}.setText(\"[{$name} not supported in Wear Tiles]\")\n{$this->indentStr()}.build()";
        $this->indent--;
        return $result;
    }

    private function generateText(Text $widget): string
    {
        $binding = $widget->getBinding();
        if ($binding) {
            $content = "\${{$binding->name}}";
        } else {
            $content = addslashes($widget->content());
        }

        $this->indent++;
        $txt = "LayoutElementBuilders.Text.Builder()\n{$this->indentStr()}.setText(\"{$content}\")";

        $style = $widget->getStyle();
        if ($style !== null) {
            $props = StyleProperty::class;
            if ($style->has($props::FontSize)) {
                $txt .= "\n{$this->indentStr()}.setFontSize({$style->get($props::FontSize)})";
            }
            if ($style->has($props::FontWeight)) {
                $v = $style->get($props::FontWeight);
                $map = ['bold' => 'FontWeight.BOLD', 'semibold' => 'FontWeight.SEMI_BOLD', 'medium' => 'FontWeight.MEDIUM', 'normal' => 'FontWeight.NORMAL', 'light' => 'FontWeight.LIGHT'];
                $weight = $map[$v] ?? 'FontWeight.NORMAL';
                $txt .= "\n{$this->indentStr()}.setFontWeight({$weight})";
            }
            if ($style->has($props::TextAlignment)) {
                $v = $style->get($props::TextAlignment);
                $map = ['left' => 'TextAlignment.START', 'center' => 'TextAlignment.CENTER', 'right' => 'TextAlignment.END'];
                $align = $map[$v] ?? 'TextAlignment.START';
                $txt .= "\n{$this->indentStr()}.setTextAlign({$align})";
            }
            if ($style->has($props::ForegroundColor)) {
                $txt .= "\n{$this->indentStr()}.setColor({$this->colorExpr($style->get($props::ForegroundColor))})";
            }
            if ($style->has($props::LineSpacing)) {
                $txt .= "\n{$this->indentStr()}.setLineHeight({$style->get($props::LineSpacing)})";
            }
            if ($style->has($props::Width)) {
                $txt .= "\n{$this->indentStr()}.setWidth({$style->get($props::Width)})";
            }
            if ($style->has($props::Height)) {
                $txt .= "\n{$this->indentStr()}.setHeight({$style->get($props::Height)})";
            }
        }

        $txt .= "\n{$this->indentStr()}.build()";
        $this->indent--;
        return $txt;
    }

    private function generateButton(Button $widget): string
    {
        $label = addslashes($widget->label());
        $this->indent++;
        $result = "LayoutElementBuilders.Button.Builder()\n{$this->indentStr()}.setText(\"{$label}\")";

        $style = $widget->getStyle();
        if ($style !== null) {
            $props = StyleProperty::class;
            if ($style->has($props::BackgroundColor)) {
                $result .= "\n{$this->indentStr()}.setBackgroundColor({$this->colorExpr($style->get($props::BackgroundColor))})";
            }
            if ($style->has($props::ForegroundColor)) {
                $result .= "\n{$this->indentStr()}.setTextColor({$this->colorExpr($style->get($props::ForegroundColor))})";
            }
            if ($style->has($props::CornerRadius)) {
                $result .= "\n{$this->indentStr()}.setCornerRadius({$style->get($props::CornerRadius)})";
            }
            if ($style->has($props::Width)) {
                $result .= "\n{$this->indentStr()}.setWidth({$style->get($props::Width)})";
            }
            if ($style->has($props::Height)) {
                $result .= "\n{$this->indentStr()}.setHeight({$style->get($props::Height)})";
            }
        }

        $result .= "\n{$this->indentStr()}.build()";
        $this->indent--;
        return $result;
    }

    private function generateVStack(VStack $widget): string
    {
        $this->indent++;
        $children = $this->generateChildren($widget->children());
        $this->indent--;
        return "LayoutElementBuilders.Column.Builder()\n{$this->indentStr()}.setWidth(LayoutElementBuilders.Expand.INSTANCE)\n{$this->indentStr()}.setHeight(LayoutElementBuilders.Expand.INSTANCE)\n{$children}\n{$this->indentStr()}.build()";
    }

    private function generateHStack(HStack $widget): string
    {
        $this->indent++;
        $children = $this->generateChildren($widget->children());
        $this->indent--;
        return "LayoutElementBuilders.Row.Builder()\n{$this->indentStr()}.setWidth(LayoutElementBuilders.Expand.INSTANCE)\n{$this->indentStr()}.setHeight(LayoutElementBuilders.Expand.INSTANCE)\n{$children}\n{$this->indentStr()}.build()";
    }

    private function generateImage(Image $widget): string
    {
        $source = addslashes($widget->source());
        $this->indent++;
        $result = "LayoutElementBuilders.Image.Builder()\n{$this->indentStr()}.setResourceId(\"{$source}\")\n{$this->indentStr()}.setWidth(LayoutElementBuilders.Expand.INSTANCE)\n{$this->indentStr()}.setHeight(LayoutElementBuilders.Expand.INSTANCE)\n{$this->indentStr()}.build()";
        $this->indent--;
        return $result;
    }

    private function generateScrollView(ScrollView $widget): string
    {
        $this->indent++;
        $children = $this->generateChildren($widget->children());
        $this->indent--;
        return "LayoutElementBuilders.Scroll.Builder()\n{$this->indentStr()}.setVerticalScroll(true)\n{$this->indentStr()}.setWidth(LayoutElementBuilders.Expand.INSTANCE)\n{$this->indentStr()}.setHeight(LayoutElementBuilders.Expand.INSTANCE)\n{$children}\n{$this->indentStr()}.build()";
    }

    private function generateTextInput(TextInput $widget): string
    {
        $placeholder = addslashes($widget->placeholder());
        $this->indent++;
        $result = "LayoutElementBuilders.Text.Builder()\n{$this->indentStr()}.setText(\"{$placeholder}\")\n{$this->indentStr()}.build()";
        $this->indent--;
        return $result;
    }

    private function generateToggle(Toggle $widget): string
    {
        $label = addslashes($widget->label());
        $this->indent++;
        $result = "LayoutElementBuilders.Text.Builder()\n{$this->indentStr()}.setText(\"{$label}\")\n{$this->indentStr()}.build()";
        $this->indent--;
        return $result;
    }

    private function generateListWidget(ListWidget $widget): string
    {
        $this->indent++;
        $children = $this->generateChildren($widget->items());
        $this->indent--;
        return "LayoutElementBuilders.Column.Builder()\n{$this->indentStr()}.setWidth(LayoutElementBuilders.Expand.INSTANCE)\n{$this->indentStr()}.setHeight(LayoutElementBuilders.Expand.INSTANCE)\n{$children}\n{$this->indentStr()}.build()";
    }

    private function generateChildren(array $children): string
    {
        $parts = [];
        foreach ($children as $child) {
            $childCode = $this->generateWidget($child);
            $parts[] = $this->indentStr() . ".addContent(\n{$childCode}\n{$this->indentStr()})";
        }
        return implode("\n", $parts);
    }

    private function colorExpr(string $hex): string
    {
        $hex = ltrim($hex, '#');
        if (strlen($hex) === 3) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));
        return sprintf("LayoutElementBuilders.ColorRep.Builder().setArgb(0xFF%02X%02X%02X).build()", $r, $g, $b);
    }

    private function indentStr(): string
    {
        return str_repeat('    ', $this->indent);
    }

    /** @return StyleProperty[] */
    public function supportedStyleProperties(): array
    {
        return [
            StyleProperty::FontSize, StyleProperty::FontWeight, StyleProperty::TextAlignment,
            StyleProperty::ForegroundColor, StyleProperty::BackgroundColor, StyleProperty::LineSpacing,
            StyleProperty::Width, StyleProperty::Height, StyleProperty::CornerRadius,
        ];
    }
}
