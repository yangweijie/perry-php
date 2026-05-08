<?php

declare(strict_types=1);

namespace Perry\Codegen;

use Perry\Build\Target;
use Perry\UI\Action;
use Perry\UI\ActionType;
use Perry\UI\Binding;
use Perry\UI\Styling\Style;
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
 * Glance (Google App Widgets) backend.
 * Generates Kotlin code using androidx.glance.* Compose-like API
 * for Android home screen widgets.
 */
final class GlanceBackend extends CodegenBackend
{
    private int $indent = 0;
    private array $currentBindings = [];

    public function name(): string
    {
        return 'glance';
    }

    public function supports(Target $target): bool
    {
        return $target === Target::Glance;
    }

    public function generate(Widget $root): string
    {
        $this->indent = 0;
        $this->currentBindings = [];

        $bindingsCode = '';
        $body = '';
        $hasWindow = false;
        $widthDp = '';
        $heightDp = '';
        $modifierCode = '';

        if ($root instanceof AppContainer) {
            $this->currentBindings = array_map(fn(Binding $b) => $b->name, $root->bindings());
            $bindingsCode = $this->generateGlanceState($root->bindings());
            $body = $this->generateWidget($root->content());

            $w = $root->windowWidth();
            $h = $root->windowHeight();
            if ($w !== null || $h !== null) {
                $hasWindow = true;
                $widthDp = $w !== null ? ".width({$w}.dp)" : '';
                $heightDp = $h !== null ? ".height({$h}.dp)" : '';
            }
        } else {
            $body = $this->generateWidget($root);
        }

        $imports = <<<KOTLIN
        import androidx.glance.GlanceModifier
        import androidx.glance.Image
        import androidx.glance.ImageProvider
        import androidx.glance.action.actionStartActivity
        import androidx.glance.appwidget.GlanceAppWidget
        import androidx.glance.appwidget.GlanceAppWidgetReceiver
        import androidx.glance.appwidget.lazy.LazyColumn
        import androidx.glance.appwidget.lazy.items
        import androidx.glance.background
        import androidx.glance.layout.Alignment
        import androidx.glance.layout.Column
        import androidx.glance.layout.Row
        import androidx.glance.layout.Spacer
        import androidx.glance.layout.Text
        import androidx.glance.layout.fillMaxSize
        import androidx.glance.layout.fillMaxWidth
        import androidx.glance.layout.height
        import androidx.glance.layout.padding
        import androidx.glance.layout.width
        import androidx.glance.text.FontWeight
        import androidx.glance.text.TextAlign
        import androidx.glance.text.TextStyle
        import androidx.glance.unit.ColorProvider
        import androidx.glance.unit.dp
        import androidx.glance.unit.sp

        KOTLIN;

        if ($bindingsCode !== '') {
            $imports .= <<<KOTLIN
        import androidx.compose.runtime.getValue
        import androidx.compose.runtime.mutableStateOf
        import androidx.compose.runtime.remember
        import androidx.compose.runtime.setValue

        KOTLIN;
        }

        if ($hasWindow) {
            $wMod = $widthDp ?: '';
            $hMod = $heightDp ?: '';
            $modifierCode = "GlanceModifier.fillMaxSize(){$wMod}{$hMod}";
        }

        if ($modifierCode === '') {
            $bodyBlock = $body;
        } else {
            $bodyBlock = "                Column(modifier: {$modifierCode}) {\n{$body}\n                }";
        }

        return <<<KOTLIN
        package com.perry.app

        {$imports}
        class PerryWidget : GlanceAppWidget() {
            override suspend fun provideGlance(context: android.content.Context, id: androidx.glance.appwidget.AppWidgetId) {
                provideContent {
                    Content()
                }
            }

            @androidx.compose.runtime.Composable
            private fun Content() {
                {$bindingsCode}
        {$bodyBlock}
            }
        }

        class PerryWidgetReceiver : GlanceAppWidgetReceiver() {
            override val glanceAppWidget: GlanceAppWidget = PerryWidget()
        }
        KOTLIN;
    }

    private function generateGlanceState(array $bindings): string
    {
        $vars = [];
        foreach ($bindings as $binding) {
            $initial = $this->formatValue($binding->initialValue);
            $vars[] = "        var {$binding->name} by remember { mutableStateOf({$initial}) }";
        }
        if ($vars === []) {
            return '';
        }
        return "\n" . implode("\n", $vars) . "\n";
    }

    private function formatValue(mixed $value): string
    {
        if (is_string($value)) {
            return '"' . addslashes($value) . '"';
        }
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }
        if (is_float($value)) {
            $str = (string) $value;
            return str_contains($str, '.') ? $str . 'f' : $str . '.0f';
        }
        if (is_int($value)) {
            return (string) $value;
        }
        return '""';
    }

    private function generateWidget(Widget $widget): string
    {
        return match ($widget->kind()) {
            WidgetKind::Text => $this->generateText($widget),
            WidgetKind::Button => $this->generateButton($widget),
            WidgetKind::VStack => $this->generateVStack($widget),
            WidgetKind::HStack => $this->generateHStack($widget),
            WidgetKind::Spacer => 'Spacer(modifier = GlanceModifier.defaultWeight())',
            WidgetKind::Image => $this->generateImage($widget),
            WidgetKind::ScrollView => $this->generateScrollView($widget),
            WidgetKind::TextInput => $this->generateTextInput($widget),
            WidgetKind::Toggle => $this->generateToggle($widget),
            WidgetKind::Slider => $this->generateUnsupported('Slider'),
            WidgetKind::TextEditor => $this->generateUnsupported('TextEditor'),
            WidgetKind::ListWidget => $this->generateListWidget($widget),
            WidgetKind::NavigationView => $this->generateUnsupported('NavigationView'),
            WidgetKind::TabView => $this->generateUnsupported('TabView'),
            WidgetKind::WebView => $this->generateUnsupported('WebView'),
            default => '',
        };
    }

    private function generateUnsupported(string $name): string
    {
        return "Text(text = \"[{$name} not supported in Glance]\", modifier = GlanceModifier.fillMaxWidth())";
    }

    private function generateText(Text $widget): string
    {
        $binding = $widget->getBinding();
        if ($binding) {
            $content = "\${{$binding->name}}";
        } else {
            $content = addslashes($widget->content());
        }

        $mods = $this->generateModifiers($widget->getStyle());
        if ($mods !== '') {
            return "Text(text = \"{$content}\"{$mods})";
        }
        return "Text(text = \"{$content}\")";
    }

    private function generateButton(Button $widget): string
    {
        $label = addslashes($widget->label());
        $mods = $this->generateModifiers($widget->getStyle());
        $action = $this->generateAction($widget->getAction());
        if ($action !== '') {
            $action = ", onClick = {{$action}}";
        }

        return "Text(text = \"{$label}\"{$action}{$mods})";
    }

    private function generateAction(?Action $action): string
    {
        if ($action === null) {
            return '';
        }

        if ($action->type === ActionType::Closure) {
            $generator = new \Perry\Generator\KotlinGenerator($this->currentBindings);
            return $action->generate($generator);
        }

        return match ($action->type) {
            ActionType::SetValue => "{$action->target->name} = {$this->formatValue($action->value)}",
            ActionType::Append => "{$action->target->name} += \"{$action->value}\"",
            ActionType::Clear => "{$action->target->name} = {$this->formatValue($action->target->initialValue)}",
            ActionType::Custom => $action->customCode ?? '',
            default => '',
        };
    }

    private function generateVStack(VStack $widget): string
    {
        $this->indent++;
        $children = $this->generateChildren($widget->children());
        $this->indent--;
        return "Column(modifier = GlanceModifier.fillMaxWidth()) {\n{$children}\n{$this->indentStr()}}";
    }

    private function generateHStack(HStack $widget): string
    {
        $this->indent++;
        $children = $this->generateChildren($widget->children());
        $this->indent--;
        return "Row(modifier = GlanceModifier.fillMaxWidth()) {\n{$children}\n{$this->indentStr()}}";
    }

    private function generateImage(Image $widget): string
    {
        $source = addslashes($widget->source());
        return "Image(provider = ImageProvider(\"{$source}\"), contentDescription = null, modifier = GlanceModifier.fillMaxWidth().width(100.dp).height(100.dp))";
    }

    private function generateScrollView(ScrollView $widget): string
    {
        $this->indent++;
        $children = $this->generateChildren($widget->children());
        $this->indent--;
        return "Column(modifier = GlanceModifier.fillMaxWidth()) {\n{$children}\n{$this->indentStr()}}";
    }

    private function generateTextInput(TextInput $widget): string
    {
        $placeholder = addslashes($widget->placeholder());
        return "Text(text = \"{$placeholder}\", modifier = GlanceModifier.fillMaxWidth())";
    }

    private function generateToggle(Toggle $widget): string
    {
        $label = addslashes($widget->label());
        return "Text(text = \"{$label}\")";
    }

    private function generateListWidget(ListWidget $widget): string
    {
        $this->indent++;
        $children = $this->generateChildren($widget->items());
        $this->indent--;
        return "LazyColumn(modifier = GlanceModifier.fillMaxWidth()) {\n{$children}\n{$this->indentStr()}}";
    }

    private function generateChildren(array $children): string
    {
        $parts = [];
        foreach ($children as $child) {
            $parts[] = $this->indentStr() . $this->generateWidget($child);
        }
        return implode("\n", $parts);
    }

    private function generateModifiers(?\Perry\UI\Styling\Style $style): string
    {
        if ($style === null) {
            return '';
        }
        $mods = [];
        $modifierParts = [];
        $props = \Perry\UI\Styling\StyleProperty::class;

        if ($style->has($props::FontSize)) {
            $mods[] = "style = TextStyle(fontSize = {$style->get($props::FontSize)}.sp)";
        }
        if ($style->has($props::ForegroundColor)) {
            $mods[] = "colorFilter = ColorProvider({$this->colorExpr($style->get($props::ForegroundColor))})";
        }
        if ($style->has($props::FontWeight)) {
            $v = $style->get($props::FontWeight);
            $map = ['bold' => 'FontWeight.Bold', 'semibold' => 'FontWeight.SemiBold', 'medium' => 'FontWeight.Medium', 'normal' => 'FontWeight.Normal', 'light' => 'FontWeight.Light'];
            $weight = $map[$v] ?? 'FontWeight.Normal';
            $mods[] = "style = TextStyle(fontWeight = {$weight})";
        }
        if ($style->has($props::TextAlignment)) {
            $v = $style->get($props::TextAlignment);
            $map = ['left' => 'TextAlign.Start', 'center' => 'TextAlign.Center', 'right' => 'TextAlign.End'];
            $align = $map[$v] ?? 'TextAlign.Start';
            $mods[] = "style = TextStyle(textAlign = {$align})";
        }

        // Padding
        if ($style->has($props::Padding)) {
            $modifierParts[] = "GlanceModifier.padding({$style->get($props::Padding)}.dp)";
        }
        if ($style->has($props::PaddingTop)) {
            $modifierParts[] = "GlanceModifier.padding(top = {$style->get($props::PaddingTop)}.dp)";
        }
        if ($style->has($props::PaddingBottom)) {
            $modifierParts[] = "GlanceModifier.padding(bottom = {$style->get($props::PaddingBottom)}.dp)";
        }
        if ($style->has($props::PaddingLeading)) {
            $modifierParts[] = "GlanceModifier.padding(start = {$style->get($props::PaddingLeading)}.dp)";
        }
        if ($style->has($props::PaddingTrailing)) {
            $modifierParts[] = "GlanceModifier.padding(end = {$style->get($props::PaddingTrailing)}.dp)";
        }

        // Dimensions
        if ($style->has($props::Width)) {
            $modifierParts[] = "GlanceModifier.width({$style->get($props::Width)}.dp)";
        }
        if ($style->has($props::Height)) {
            $modifierParts[] = "GlanceModifier.height({$style->get($props::Height)}.dp)";
        }
        if ($style->has($props::MinWidth)) {
            $modifierParts[] = "GlanceModifier.defaultWeight().width({$style->get($props::MinWidth)}.dp)";
        }
        if ($style->has($props::MinHeight)) {
            $modifierParts[] = "GlanceModifier.defaultWeight().height({$style->get($props::MinHeight)}.dp)";
        }

        // Visual
        if ($style->has($props::BackgroundColor)) {
            $modifierParts[] = "GlanceModifier.background(ColorProvider({$this->colorExpr($style->get($props::BackgroundColor))}))";
        }
        if ($style->has($props::Opacity)) {
            $modifierParts[] = "GlanceModifier.alpha({$style->get($props::Opacity)}f)";
        }
        if ($style->has($props::Margin)) {
            $modifierParts[] = "GlanceModifier.padding({$style->get($props::Margin)}.dp)";
        }

        if ($modifierParts !== []) {
            $combined = implode('.then(', $modifierParts) . str_repeat(')', count($modifierParts) - 1);
            if (count($modifierParts) === 1) {
                $combined = $modifierParts[0];
            }
            $mods[] = "modifier = {$combined}";
        }

        return $mods ? ', ' . implode(', ', $mods) : '';
    }

    private function colorExpr(string $hex): string
    {
        $hex = ltrim($hex, '#');
        if (strlen($hex) === 3) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }
        return "ColorProvider(android.graphics.Color.parseColor(\"#{$hex}\"))";
    }

    private function indentStr(): string
    {
        return str_repeat('    ', $this->indent);
    }
}
