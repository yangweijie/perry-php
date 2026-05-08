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

final class ComposeBackend extends CodegenBackend
{
    private int $indent = 0;

    public function name(): string
    {
        return 'compose';
    }

    public function supports(Target $target): bool
    {
        return $target === Target::Android;
    }

    public function generate(Widget $root): string
    {
        $this->indent = 0;

        if ($root instanceof AppContainer) {
            return $this->generateAppWithState($root);
        }

        return $this->generateSimpleApp($root);
    }

    private function generateAppWithState(AppContainer $app): string
    {
        $bindings = $app->bindings();
        $stateVars = $this->generateStateVars($bindings);
        $body = $this->generateWidget($app->content());

        $width = $app->windowWidth();
        $height = $app->windowHeight();
        $sizeCode = 'Modifier.fillMaxSize()';
        if ($width !== null && $height !== null) {
            $sizeCode = "Modifier.fillMaxSize().size(width = {$width}.dp, height = {$height}.dp)";
        }

        return <<<KOTLIN
        package com.perry.app

        import android.os.Bundle
        import androidx.activity.ComponentActivity
        import androidx.activity.compose.setContent
        import androidx.compose.foundation.layout.*
        import androidx.compose.material3.*
        import androidx.compose.runtime.*
        import androidx.compose.ui.Modifier
        import androidx.compose.ui.unit.dp
        import androidx.compose.ui.unit.sp

        class MainActivity : ComponentActivity() {
            override fun onCreate(savedInstanceState: Bundle?) {
                super.onCreate(savedInstanceState)
                setContent {
                    PerryApp()
                }
            }
        }

        @Composable
        fun PerryApp() {
            {$stateVars}

            Surface(
                modifier = {$sizeCode},
                color = MaterialTheme.colorScheme.background
            ) {
        {$body}
            }
        }
        KOTLIN;
    }

    private function generateSimpleApp(Widget $root): string
    {
        $body = $this->generateWidget($root);

        return <<<KOTLIN
        package com.perry.app

        import android.os.Bundle
        import androidx.activity.ComponentActivity
        import androidx.activity.compose.setContent
        import androidx.compose.foundation.layout.*
        import androidx.compose.material3.*
        import androidx.compose.runtime.*
        import androidx.compose.ui.Modifier
        import androidx.compose.ui.unit.dp

        class MainActivity : ComponentActivity() {
            override fun onCreate(savedInstanceState: Bundle?) {
                super.onCreate(savedInstanceState)
                setContent {
                    {$body}
                }
            }
        }
        KOTLIN;
    }

    private function generateStateVars(array $bindings): string
    {
        $vars = [];
        foreach ($bindings as $binding) {
            $initial = $this->formatValue($binding->initialValue);
            $vars[] = "var {$binding->name} by remember { mutableStateOf({$initial}) }";
        }
        return implode("\n            ", $vars);
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
            WidgetKind::Spacer => 'Spacer(modifier = Modifier.weight(1f))',
            WidgetKind::Image => $this->generateImage($widget),
            WidgetKind::ScrollView => $this->generateScrollView($widget),
            WidgetKind::TextInput => $this->generateTextInput($widget),
            WidgetKind::Toggle => $this->generateToggle($widget),
            WidgetKind::Slider => $this->generateSlider($widget),
            WidgetKind::TextEditor => $this->generateTextEditorWidget($widget),
            WidgetKind::WebView => $this->generateWebViewWidget($widget),
            WidgetKind::ListWidget => $this->generateListWidget($widget),
            WidgetKind::NavigationView => $this->generateNavigationView($widget),
            WidgetKind::TabView => $this->generateTabView($widget),
            default => 'Box {}',
        };
    }

    private function generateText(Text $widget): string
    {
        $binding = $widget->getBinding();
        if ($binding) {
            $content = $binding->name;
        } else {
            $content = '"' . addslashes($widget->content()) . '"';
        }

        $mods = $this->generateModifiers($widget->getStyle());
        return "Text(text = {$content}{$mods})";
    }

    private function generateButton(Button $widget): string
    {
        $label = addslashes($widget->label());
        $action = $this->generateAction($widget->getAction());
        $mods = $this->generateModifiers($widget->getStyle());
        return "Button(onClick = {{$action}}){$mods} {\n{$this->indentStr()}    Text(\"{$label}\")\n{$this->indentStr()}}";
    }

    private function generateAction(?Action $action): string
    {
        if ($action === null) {
            return '';
        }

        if ($action->type === ActionType::Closure) {
            $stateVars = ['display', 'result', 'operand1', 'operand2', 'operation', 'isTyping', 'typed'];
            $generator = new \Perry\Generator\KotlinGenerator($stateVars);
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
        $spacing = $this->getSpacing($widget->getStyle());
        return "Column(verticalArrangement = Arrangement.spacing({$spacing}.dp), modifier = Modifier.fillMaxWidth()) {\n{$children}\n{$this->indentStr()}}";
    }

    private function generateHStack(HStack $widget): string
    {
        $this->indent++;
        $children = $this->generateChildren($widget->children());
        $this->indent--;
        $spacing = $this->getSpacing($widget->getStyle());
        return "Row(horizontalArrangement = Arrangement.spacing({$spacing}.dp), modifier = Modifier.fillMaxWidth()) {\n{$children}\n{$this->indentStr()}}";
    }

    private function generateImage(Image $widget): string
    {
        $source = addslashes($widget->source());
        return "Image(painter = painterResource(id = R.drawable.{$source}), contentDescription = null)";
    }

    private function generateScrollView(ScrollView $widget): string
    {
        $this->indent++;
        $children = $this->generateChildren($widget->children());
        $this->indent--;
        return "Column(modifier = Modifier.verticalScroll(rememberScrollState())) {\n{$children}\n{$this->indentStr()}}";
    }

    private function generateTextInput(TextInput $widget): string
    {
        $placeholder = addslashes($widget->placeholder());
        return "TextField(value = \"\", onValueChange = {{}}, placeholder = { Text(\"{$placeholder}\") })";
    }

    private function generateToggle(Toggle $widget): string
    {
        $label = addslashes($widget->label());
        return "Row(verticalAlignment = Alignment.CenterVertically) {\n{$this->indentStr()}    Switch(checked = false, onCheckedChange = {{}})\n{$this->indentStr()}    Text(\"{$label}\")\n{$this->indentStr()}}";
    }

    private function generateSlider(Slider $widget): string
    {
        $binding = $widget->value();
        $name = $binding->name;
        $min = $widget->min();
        $max = $widget->max();
        $step = $widget->step();
        $mods = $this->generateModifiers($widget->getStyle());

        $onChange = '';
        $action = $widget->getOnChange();
        if ($action !== null) {
            $onChange = ', onValueChange = {' . $this->generateAction($action) . '}';
        }

        return "Slider(value = {$name}, valueRange = {$min}f..{$max}f, steps = {(int)(({$max} - {$min}) / {$step})}, onValueChange = {{$name} = it{$onChange}}{$mods})";
    }

    private function generateTextEditorWidget(TextEditor $widget): string
    {
        $binding = $widget->getBinding();
        $name = $binding->name;
        $placeholder = addslashes($widget->placeholder());
        return 'OutlinedTextField(value = ' . $name . ', onValueChange = { ' . $name . ' = it}, placeholder = { Text("' . $placeholder . '") }, modifier = Modifier.fillMaxWidth(), minLines = 3)';
    }

    private function generateWebViewWidget(WebView $widget): string
    {
        $html = addslashes($widget->html());
        // Use AndroidView + android.webkit.WebView for embedded HTML in Compose
        return "AndroidView(factory = { context ->\n{$this->indentStr()}    android.webkit.WebView(context).apply {{\n{$this->indentStr()}        loadDataWithBaseURL(null, \"{$html}\", \"text/html\", \"UTF-8\", null)\n{$this->indentStr()}    }}\n{$this->indentStr()}})";
    }

    private function generateListWidget(\Perry\UI\Widget\ListWidget $widget): string
    {
        $this->indent++;
        $children = $this->generateChildren($widget->items());
        $this->indent--;
        return "LazyColumn(modifier = Modifier.fillMaxWidth()) {\n{$children}\n{$this->indentStr()}}";
    }

    private function generateNavigationView(NavigationView $widget): string
    {
        $this->indent++;
        $children = $this->generateChildren($widget->screens());
        $this->indent--;
        return "Box(modifier = Modifier.fillMaxSize()) {\n{$children}\n{$this->indentStr()}}";
    }

    private function generateTabView(TabView $widget): string
    {
        $this->indent++;
        $children = $this->generateChildren($widget->tabs());
        $this->indent--;
        return "TabRow() {\n{$children}\n{$this->indentStr()}}";
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
        $props = \Perry\UI\Styling\StyleProperty::class;

        // Colors
        if ($style->has($props::BackgroundColor)) {
            $mods[] = ".background({$this->colorExpr($style->get($props::BackgroundColor))})";
        }
        if ($style->has($props::ForegroundColor)) {
            $mods[] = ".color({$this->colorExpr($style->get($props::ForegroundColor))})";
        }
        if ($style->has($props::BorderColor)) {
            $mods[] = ".border(color = {$this->colorExpr($style->get($props::BorderColor))})";
        }

        // Sizing
        if ($style->has($props::Width) || $style->has($props::Height)) {
            $w = $style->has($props::Width) ? $style->get($props::Width) . '.dp' : 'Modifier.fillMaxWidth()';
            $h = $style->has($props::Height) ? $style->get($props::Height) . '.dp' : 'Modifier.fillMaxHeight()';
            $mods[] = ".size({$w}, {$h})";
        }
        if ($style->has($props::MinWidth)) {
            $mods[] = ".requiredWidthIn(min = {$style->get($props::MinWidth)}.dp)";
        }
        if ($style->has($props::MinHeight)) {
            $mods[] = ".requiredHeightIn(min = {$style->get($props::MinHeight)}.dp)";
        }
        if ($style->has($props::MaxWidth)) {
            $mods[] = ".requiredWidthIn(max = {$style->get($props::MaxWidth)}.dp)";
        }
        if ($style->has($props::MaxHeight)) {
            $mods[] = ".requiredHeightIn(max = {$style->get($props::MaxHeight)}.dp)";
        }

        // Border
        if ($style->has($props::BorderWidth)) {
            $mods[] = ".border(width = {$style->get($props::BorderWidth)}.dp" .
                ($style->has($props::BorderColor) ? ", color = {$this->colorExpr($style->get($props::BorderColor))}" : '') . ')';
        }

        // Corner radius
        if ($style->has($props::CornerRadius)) {
            $mods[] = ".clip(RoundedCornerShape({$style->get($props::CornerRadius)}.dp))";
        }

        // Margin (Compose uses padding with offset, or just padding on outer container)
        if ($style->has($props::Margin)) {
            $v = $style->get($props::Margin);
            $mods[] = ".padding({$v}.dp)";
        }

        // Padding
        if ($style->has($props::Padding)) {
            $mods[] = ".padding({$style->get($props::Padding)}.dp)";
        }
        if ($style->has($props::PaddingTop) || $style->has($props::PaddingBottom) ||
            $style->has($props::PaddingLeading) || $style->has($props::PaddingTrailing)) {
            $top = $style->has($props::PaddingTop) ? $style->get($props::PaddingTop) : 0;
            $bottom = $style->has($props::PaddingBottom) ? $style->get($props::PaddingBottom) : 0;
            $start = $style->has($props::PaddingLeading) ? $style->get($props::PaddingLeading) : 0;
            $end = $style->has($props::PaddingTrailing) ? $style->get($props::PaddingTrailing) : 0;
            $mods[] = ".padding(start = {$start}.dp, top = {$top}.dp, end = {$end}.dp, bottom = {$bottom}.dp)";
        }

        // Opacity
        if ($style->has($props::Opacity)) {
            $mods[] = ".alpha({$style->get($props::Opacity)})";
        }

        // Shadow
        if ($style->has($props::ShadowRadius) || $style->has($props::ShadowColor)) {
            $radius = $style->has($props::ShadowRadius) ? $style->get($props::ShadowRadius) : 4;
            $color = $style->has($props::ShadowColor) ? $this->colorExpr($style->get($props::ShadowColor)) : 'Color.Black';
            $offsetX = $style->has($props::ShadowOffsetX) ? $style->get($props::ShadowOffsetX) : 0;
            $offsetY = $style->has($props::ShadowOffsetY) ? $style->get($props::ShadowOffsetY) : 2;
            $mods[] = ".shadow(radius = {$radius}.dp, color = {$color}, offset = Offset({$offsetX}.dp, {$offsetY}.dp))";
        }

        // Font
        if ($style->has($props::FontSize)) {
            $mods[] = ".fontSize({$style->get($props::FontSize)}.sp)";
        }
        if ($style->has($props::FontWeight)) {
            $v = $style->get($props::FontWeight);
            $map = ['bold' => 'FontWeight.Bold', 'semibold' => 'FontWeight.SemiBold', 'medium' => 'FontWeight.Medium', 'normal' => 'FontWeight.Normal', 'light' => 'FontWeight.Light'];
            $weight = $map[$v] ?? 'FontWeight.Normal';
            $mods[] = ".fontWeight({$weight})";
        }
        if ($style->has($props::FontFamily)) {
            $v = $style->get($props::FontFamily);
            $mods[] = ".fontFamily(\"{$v}\")";
        }
        if ($style->has($props::TextAlignment)) {
            $v = $style->get($props::TextAlignment);
            $map = ['left' => 'TextAlign.Left', 'center' => 'TextAlign.Center', 'right' => 'TextAlign.Right'];
            $align = $map[$v] ?? 'TextAlign.Left';
            $mods[] = ".textAlign({$align})";
        }
        if ($style->has($props::TextDecoration)) {
            $v = $style->get($props::TextDecoration);
            $map = ['underline' => 'TextDecoration.Underline', 'line-through' => 'TextDecoration.LineThrough'];
            $decoration = $map[$v] ?? 'TextDecoration.None';
            $mods[] = ".textDecorationLine({$decoration})";
        }
        if ($style->has($props::LineSpacing)) {
            $mods[] = ".lineHeight({$style->get($props::LineSpacing)}.sp)";
        }

        return $mods ? ', modifier = Modifier' . implode('', $mods) : '';
    }

    private function colorExpr(string $hex): string
    {
        $hex = ltrim($hex, '#');
        if (strlen($hex) === 3) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }
        return "Color(0xFF{$hex})";
    }

    private function getSpacing(?\Perry\UI\Styling\Style $style): string
    {
        if ($style && $style->has(\Perry\UI\Styling\StyleProperty::Padding)) {
            return (string) $style->get(\Perry\UI\Styling\StyleProperty::Padding);
        }
        return '8';
    }

    private function indentStr(): string
    {
        return str_repeat('    ', $this->indent);
    }
}
