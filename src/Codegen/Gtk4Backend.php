<?php

declare(strict_types=1);

namespace Perry\Codegen;

use Perry\Build\Target;
use Perry\Generator\CGenerator;
use Perry\UI\Action;
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

final class Gtk4Backend extends CodegenBackend
{
    private int $indent = 0;

    /** @var array<array{id: string, method: string, action: Action}> */
    private array $buttonActions = [];
    
    /** @var array<array{id: string, action: Action}> */
    private array $sliderActions = [];
    
    /** @var array<array{id: string, action: Action}> */
    private array $textInputActions = [];
    
    /** @var array<array{id: string, action: Action}> */
    private array $toggleActions = [];

    public function name(): string
    {
        return 'gtk4';
    }

    public function supports(Target $target): bool
    {
        return $target === Target::Gtk4Linux;
    }

    public function generate(Widget $root): string
    {
        $this->indent = 0;
        $this->objectId = 0;
        $this->buttonActions = [];
        $this->sliderActions = [];
        $this->textInputActions = [];
        $this->toggleActions = [];
        $body = $this->generateWidget($root);

        return <<<XML
        <?xml version="1.0" encoding="UTF-8"?>
        <interface>
            <requires lib="gtk" version="4.0" />
            <object class="GtkApplicationWindow" id="main_window">
                <property name="title">Perry App</property>
                <property name="default-width">800</property>
                <property name="default-height">600</property>
                <child>
                    {$body}
                </child>
            </object>
        </interface>
        XML;
    }

    public function generateMainActivity(string $outputName): string
    {
        $handlers = '';
        foreach ($this->buttonActions as $item) {
            $methodName = $item['method'];
            $action = $item['action'];
            $body = $this->generateActionBody($action);
            $handlers .= <<<C

void {$methodName}(GtkButton *button, gpointer user_data) {
{$body}
}
C;
        }

        return <<<C
#include <gtk/gtk.h>

void activate(GtkApplication *app, gpointer user_data) {
    GtkBuilder *builder = gtk_builder_new_from_file("build/{$outputName}.ui");
    GtkWidget *window = GTK_WIDGET(gtk_builder_get_object(builder, "main_window"));
    gtk_window_set_application(GTK_WINDOW(window), app);
    gtk_widget_set_visible(window, TRUE);
    g_object_unref(builder);
}

int main(int argc, char **argv) {
    GtkApplication *app = gtk_application_new("com.perry.{$outputName}", G_APPLICATION_DEFAULT_FLAGS);
    g_signal_connect(app, "activate", G_CALLBACK(activate), NULL);
    int status = g_application_run(G_APPLICATION(app), argc, argv);
    g_object_unref(app);
    return status;
}
{$handlers}
C;
    }

    private function generateActionBody(\Perry\UI\Action $action): string
    {
        if ($action->type === \Perry\UI\ActionType::Custom) {
            // For Gtk4, custom actions are C functions - just output as comment for now
            return '    // Custom action: ' . $action->customCode;
        }

        if ($action->type === \Perry\UI\ActionType::Closure) {
            // Transpile PHP closure to C code using IR + CGenerator
            $ir = $action->getIr();
            $cGenerator = new CGenerator();
            $code = $ir->accept($cGenerator);
            // Replace closure bindings with actual values
            $code = $this->replaceClosureBindings($code, $action->closureBindings);
            return $code;
        }

        if ($action->type === \Perry\UI\ActionType::SetValue) {
            $target = $action->target;
            $value = $action->value;
            if (is_string($value)) {
                return "    g_print(\"Set {$target} to {$value}\\n\");";
            }
            return "    g_print(\"Set {$target} to {$value}\\n\");";
        }

        if ($action->type === \Perry\UI\ActionType::Append) {
            $target = $action->target;
            $value = $action->value;
            return "    g_print(\"Append {$value} to {$target}\\n\");";
        }

        if ($action->type === \Perry\UI\ActionType::Clear) {
            $target = $action->target;
            return "    g_print(\"Clear {$target}\\n\");";
        }

        return '    // Action type not yet fully supported for Gtk4: ' . $action->type->value;
    }

    private function indentC(string $code, int $level): string
    {
        $lines = explode("\n", $code);
        $indent = str_repeat('    ', $level);
        return implode("\n", array_map(fn($line) => $indent . $line, $lines));
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
            WidgetKind::Spacer => $this->generateSpacer($widget),
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
            default => '',
        };
    }

    private function generateText(Text $widget): string
    {
        $id = $this->nextId();
        $text = htmlspecialchars($widget->content());
        $props = $this->generateProperties($widget->getStyle());

        return <<<XML
        {$this->indentStr()}<object class="GtkLabel" id="{$id}">
        {$this->indentStr()}    <property name="label">{$text}</property>
        {$this->indentStr()}    <property name="xalign">0</property>
        {$props}
        {$this->indentStr()}</object>
        XML;
    }

    private function generateButton(Button $widget): string
    {
        $id = $this->nextId();
        $label = htmlspecialchars($widget->label());
        $props = $this->generateProperties($widget->getStyle());

        $action = $widget->getAction();
        if ($action !== null) {
            $methodName = 'on_' . $id . '_clicked';
            $this->buttonActions[] = ['id' => $id, 'method' => $methodName, 'action' => $action];
        }

        $signal = '';
        if ($action !== null) {
            $methodName = 'on_' . $id . '_clicked';
            $signal = "{$this->indentStr()}    <signal name=\"clicked\" handler=\"{$methodName}\" />";
        }

        return <<<XML
        {$this->indentStr()}<object class="GtkButton" id="{$id}">
        {$this->indentStr()}    <property name="label">{$label}</property>
        {$props}
        {$signal}
        {$this->indentStr()}</object>
        XML;
    }

    private function generateVStack(VStack $widget): string
    {
        $id = $this->nextId();
        $this->indent++;
        $children = $this->generateChildren($widget->children());
        $this->indent--;

        $props = $this->generateProperties($widget->getStyle());

        return <<<XML
        {$this->indentStr()}<object class="GtkBox" id="{$id}">
        {$this->indentStr()}    <property name="orientation">vertical</property>
        {$this->indentStr()}    <property name="spacing">8</property>
        {$props}
        {$children}
        {$this->indentStr()}</object>
        XML;
    }

    private function generateHStack(HStack $widget): string
    {
        $id = $this->nextId();
        $this->indent++;
        $children = $this->generateChildren($widget->children());
        $this->indent--;

        $props = $this->generateProperties($widget->getStyle());

        return <<<XML
        {$this->indentStr()}<object class="GtkBox" id="{$id}">
        {$this->indentStr()}    <property name="orientation">horizontal</property>
        {$this->indentStr()}    <property name="spacing">8</property>
        {$props}
        {$children}
        {$this->indentStr()}</object>
        XML;
    }

    private function generateSpacer(Spacer $widget): string
    {
        $id = $this->nextId();

        return <<<XML
        {$this->indentStr()}<object class="GtkSeparator" id="{$id}">
        {$this->indentStr()}    <property name="orientation">horizontal</property>
        {$this->indentStr()}</object>
        XML;
    }

    private function generateImage(Image $widget): string
    {
        $id = $this->nextId();
        $src = htmlspecialchars($widget->source());

        return <<<XML
        {$this->indentStr()}<object class="GtkImage" id="{$id}">
        {$this->indentStr()}    <property name="file">{$src}</property>
        {$this->indentStr()}</object>
        XML;
    }

    private function generateScrollView(ScrollView $widget): string
    {
        $id = $this->nextId();
        $this->indent++;
        $children = $this->generateChildren($widget->children());
        $this->indent--;

        return <<<XML
        {$this->indentStr()}<object class="GtkScrolledWindow" id="{$id}">
        {$children}
        {$this->indentStr()}</object>
        XML;
    }

    private function generateTextEditorWidget(TextEditor $widget): string
    {
        $id = $this->nextId();
        $placeholder = htmlspecialchars($widget->placeholder());
        return <<<XML
        {$this->indentStr()}<object class="GtkTextView" id="{$id}">
        {$this->indentStr()}    <property name="placeholder-text">{$placeholder}</property>
        {$this->indentStr()}</object>
        XML;
    }

    private function generateWebViewWidget(WebView $widget): string
    {
        $id = $this->nextId();
        return <<<XML
        {$this->indentStr()}<object class="GtkWebView" id="{$id}">
        {$this->indentStr()}    <property name="visible">True</property>
        {$this->indentStr()}</object>
        XML;
    }

    private function generateListWidget(\Perry\UI\Widget\ListWidget $widget): string
    {
        $id = $this->nextId();
        $this->indent++;
        $items = $this->generateChildren($widget->items());
        $this->indent--;
        return <<<XML
        {$this->indentStr()}<object class="GtkBox" id="{$id}">
        {$this->indentStr()}    <property name="orientation">vertical</property>
        {$items}
        {$this->indentStr()}</object>
        XML;
    }

    private function generateNavigationView(NavigationView $widget): string
    {
        $id = $this->nextId();
        $this->indent++;
        $screens = $this->generateChildren($widget->screens());
        $this->indent--;
        return <<<XML
        {$this->indentStr()}<object class="GtkStack" id="{$id}">
        {$screens}
        {$this->indentStr()}</object>
        XML;
    }

    private function generateTabView(TabView $widget): string
    {
        $id = $this->nextId();
        $this->indent++;
        $tabs = $this->generateChildren($widget->tabs());
        $this->indent--;
        return <<<XML
        {$this->indentStr()}<object class="GtkNotebook" id="{$id}">
        {$tabs}
        {$this->indentStr()}</object>
        XML;
    }

    private function generateChildren(array $children): string
    {
        $parts = [];
        foreach ($children as $child) {
            $parts[] = $this->indentStr() . '<child>' . "\n"
                . $this->generateWidget($child) . "\n"
                . $this->indentStr() . '</child>';
        }
        return implode("\n", $parts);
    }

    private function generateProperties(?\Perry\UI\Styling\Style $style): string
    {
        if ($style === null) {
            return '';
        }

        $props = [];
        $cssProps = [];

        // Colors
        if ($style->has(StyleProperty::BackgroundColor)) {
            $props[] = "{$this->indentStr()}    <property name=\"background-color\">#{$style->get(StyleProperty::BackgroundColor)}</property>";
        }
        if ($style->has(StyleProperty::ForegroundColor)) {
            $cssProps[] = "color: #{$style->get(StyleProperty::ForegroundColor)}";
        }
        if ($style->has(StyleProperty::BorderColor)) {
            $cssProps[] = "border-color: #{$style->get(StyleProperty::BorderColor)}";
        }

        // Sizing
        if ($style->has(StyleProperty::Width)) {
            $props[] = "{$this->indentStr()}    <property name=\"width-request\">{$style->get(StyleProperty::Width)}</property>";
        }
        if ($style->has(StyleProperty::Height)) {
            $props[] = "{$this->indentStr()}    <property name=\"height-request\">{$style->get(StyleProperty::Height)}</property>";
        }
        if ($style->has(StyleProperty::MinWidth)) {
            $props[] = "{$this->indentStr()}    <property name=\"min-width-request\">{$style->get(StyleProperty::MinWidth)}</property>";
        }
        if ($style->has(StyleProperty::MinHeight)) {
            $props[] = "{$this->indentStr()}    <property name=\"min-height-request\">{$style->get(StyleProperty::MinHeight)}</property>";
        }
        if ($style->has(StyleProperty::MaxWidth)) {
            $cssProps[] = "max-width: {$style->get(StyleProperty::MaxWidth)}px";
        }
        if ($style->has(StyleProperty::MaxHeight)) {
            $cssProps[] = "max-height: {$style->get(StyleProperty::MaxHeight)}px";
        }

        // Border
        if ($style->has(StyleProperty::BorderWidth)) {
            $cssProps[] = "border-width: {$style->get(StyleProperty::BorderWidth)}px";
        }
        if ($style->has(StyleProperty::CornerRadius)) {
            $cssProps[] = "border-radius: {$style->get(StyleProperty::CornerRadius)}px";
        }

        // Margin & Padding
        if ($style->has(StyleProperty::Margin)) {
            $v = $style->get(StyleProperty::Margin);
            $cssProps[] = "margin: {$v}px";
        }
        if ($style->has(StyleProperty::Padding)) {
            $v = $style->get(StyleProperty::Padding);
            $cssProps[] = "padding: {$v}px";
        }
        if ($style->has(StyleProperty::PaddingTop)) {
            $v = $style->get(StyleProperty::PaddingTop);
            $cssProps[] = "padding-top: {$v}px";
        }
        if ($style->has(StyleProperty::PaddingBottom)) {
            $v = $style->get(StyleProperty::PaddingBottom);
            $cssProps[] = "padding-bottom: {$v}px";
        }
        if ($style->has(StyleProperty::PaddingLeading)) {
            $v = $style->get(StyleProperty::PaddingLeading);
            $cssProps[] = "padding-left: {$v}px";
        }
        if ($style->has(StyleProperty::PaddingTrailing)) {
            $v = $style->get(StyleProperty::PaddingTrailing);
            $cssProps[] = "padding-right: {$v}px";
        }

        // Opacity
        if ($style->has(StyleProperty::Opacity)) {
            $props[] = "{$this->indentStr()}    <property name=\"opacity\">{$style->get(StyleProperty::Opacity)}</property>";
        }

        // Shadow (GTK4 CSS box-shadow)
        if ($style->has(StyleProperty::ShadowRadius) || $style->has(StyleProperty::ShadowColor)) {
            $radius = $style->has(StyleProperty::ShadowRadius) ? $style->get(StyleProperty::ShadowRadius) : 4;
            $color = $style->has(StyleProperty::ShadowColor) ? '#' . $style->get(StyleProperty::ShadowColor) : '#000000';
            $offsetX = $style->has(StyleProperty::ShadowOffsetX) ? $style->get(StyleProperty::ShadowOffsetX) : 0;
            $offsetY = $style->has(StyleProperty::ShadowOffsetY) ? $style->get(StyleProperty::ShadowOffsetY) : 2;
            $cssProps[] = "box-shadow: {$offsetX}px {$offsetY}px {$radius}px {$color}";
        }

        // Font
        if ($style->has(StyleProperty::FontSize)) {
            $cssProps[] = "font-size: {$style->get(StyleProperty::FontSize)}px";
        }
        if ($style->has(StyleProperty::FontWeight)) {
            $v = $style->get(StyleProperty::FontWeight);
            $map = ['bold' => '700', 'semibold' => '600', 'medium' => '500', 'normal' => '400', 'light' => '300'];
            $weight = $map[$v] ?? '400';
            $cssProps[] = "font-weight: {$weight}";
        }
        if ($style->has(StyleProperty::FontFamily)) {
            $v = $style->get(StyleProperty::FontFamily);
            $cssProps[] = "font-family: \"{$v}\"";
        }
        if ($style->has(StyleProperty::TextAlignment)) {
            $v = $style->get(StyleProperty::TextAlignment);
            $map = ['left' => 'left', 'center' => 'center', 'right' => 'right'];
            $align = $map[$v] ?? 'left';
            $cssProps[] = "text-align: {$align}";
        }
        if ($style->has(StyleProperty::TextDecoration)) {
            $v = $style->get(StyleProperty::TextDecoration);
            $cssProps[] = "text-decoration: {$v}";
        }
        if ($style->has(StyleProperty::LineSpacing)) {
            $cssProps[] = "line-height: {$style->get(StyleProperty::LineSpacing)}px";
        }
        if ($style->has(StyleProperty::LetterSpacing)) {
            $cssProps[] = "letter-spacing: {$style->get(StyleProperty::LetterSpacing)}px";
        }

        // Flex layout (CSS properties)
        if ($style->has(StyleProperty::FlexDirection)) {
            $cssProps[] = "flex-direction: {$style->get(StyleProperty::FlexDirection)}";
        }
        if ($style->has(StyleProperty::JustifyContent)) {
            $cssProps[] = "justify-content: {$style->get(StyleProperty::JustifyContent)}";
        }
        if ($style->has(StyleProperty::AlignItems)) {
            $cssProps[] = "align-items: {$style->get(StyleProperty::AlignItems)}";
        }
        if ($style->has(StyleProperty::FlexWrap)) {
            $cssProps[] = "flex-wrap: {$style->get(StyleProperty::FlexWrap)}";
        }
        if ($style->has(StyleProperty::Gap)) {
            $cssProps[] = "gap: {$style->get(StyleProperty::Gap)}px";
        }
        if ($style->has(StyleProperty::FlexGrow)) {
            $cssProps[] = "flex-grow: {$style->get(StyleProperty::FlexGrow)}";
        }
        if ($style->has(StyleProperty::FlexShrink)) {
            $cssProps[] = "flex-shrink: {$style->get(StyleProperty::FlexShrink)}";
        }

        // Transform (CSS)
        $transforms = [];
        if ($style->has(StyleProperty::Rotate)) {
            $transforms[] = "rotate({$style->get(StyleProperty::Rotate)}deg)";
        }
        if ($style->has(StyleProperty::Scale)) {
            $transforms[] = "scale({$style->get(StyleProperty::Scale)})";
        }
        if ($style->has(StyleProperty::TranslateX) || $style->has(StyleProperty::TranslateY)) {
            $tx = $style->has(StyleProperty::TranslateX) ? $style->get(StyleProperty::TranslateX) . 'px' : '0';
            $ty = $style->has(StyleProperty::TranslateY) ? $style->get(StyleProperty::TranslateY) . 'px' : '0';
            $transforms[] = "translate({$tx}, {$ty})";
        }
        if (!empty($transforms)) {
            $cssProps[] = 'transform: ' . implode(' ', $transforms);
        }

        // Animation (CSS)
        if ($style->has(StyleProperty::AnimationDuration)) {
            $cssProps[] = "animation-duration: {$style->get(StyleProperty::AnimationDuration)}ms";
        }
        if ($style->has(StyleProperty::AnimationDelay)) {
            $cssProps[] = "animation-delay: {$style->get(StyleProperty::AnimationDelay)}ms";
        }
        if ($style->has(StyleProperty::AnimationEasing)) {
            $easing = $style->get(StyleProperty::AnimationEasing);
            $cssEasing = match ($easing) {
                'ease-in' => 'ease-in',
                'ease-out' => 'ease-out',
                'ease-in-out' => 'ease-in-out',
                'linear' => 'linear',
                default => $easing,
            };
            $cssProps[] = "animation-timing-function: {$cssEasing}";
        }

        if (!empty($cssProps)) {
            $css = "{$this->indentStr()}    <style>css=\"" . implode('; ', $cssProps) . "\"</style>";
        }

        if (empty($props) && empty($css)) {
            return '';
        }

        return "\n" . implode("\n", $props) . $css;
    }

    private function indentStr(): string
    {
        return str_repeat('    ', $this->indent);
    }

    private function generateSlider(\Perry\UI\Widget\Slider $widget): string
    {
        $id = $this->nextId();
        $binding = $widget->value();
        $min = $widget->min();
        $max = $widget->max();
        $step = $widget->step();
        $onChange = $widget->getOnChange();

        $props = $this->generateProperties($widget->getStyle());
        $bindingExpr = $binding ? '$' . $binding->name : '$0';
        
        $result = <<<XML
{$this->indentStr()}<object class="GtkScale" id="{$id}">
{$this->indentStr()}    <property name="orientation">horizontal</property>
{$this->indentStr()}    <property name="adjustment">
{$this->indentStr()}        <object class="GtkAdjustment">
{$this->indentStr()}            <property name="lower">{$min}</property>
{$this->indentStr()}            <property name="upper">{$max}</property>
{$this->indentStr()}            <property name="value">{$bindingExpr}</property>
{$this->indentStr()}            <property name="step_increment">{$step}</property>
{$this->indentStr()}        </object>
{$this->indentStr()}    </property>
{$props}
{$this->indentStr()}</object>
XML;

        if ($onChange !== null) {
            $this->sliderActions[] = ['id' => $id, 'action' => $onChange];
            $result .= "{$this->indentStr()}<signal name=\"value-changed\" handler=\"on_{$id}_value_changed\"/>\n";
        }

        return rtrim($result);
    }

    private function generateTextInput(\Perry\UI\Widget\TextInput $widget): string
    {
        $id = $this->nextId();
        $placeholder = addslashes($widget->placeholder());
        $state = $widget->value();
        $onChange = $widget->getOnChange();

        $props = $this->generateProperties($widget->getStyle());
        $bindingExpr = $state ? '$' . $state->id : '""';
        
        $result = <<<XML
{$this->indentStr()}<object class="GtkEntry" id="{$id}">
{$this->indentStr()}    <property name="placeholder-text">{$placeholder}</property>
{$this->indentStr()}    <property name="text">{$bindingExpr}</property>
{$props}
{$this->indentStr()}</object>
XML;

        if ($onChange !== null) {
            $this->textInputActions[] = ['id' => $id, 'action' => $onChange];
            $result .= "{$this->indentStr()}<signal name=\"changed\" handler=\"on_{$id}_changed\"/>\n";
        }

        return rtrim($result);
    }

    private function generateToggle(\Perry\UI\Widget\Toggle $widget): string
    {
        $id = $this->nextId();
        $label = addslashes($widget->label());
        $isOn = $widget->getIsOn();
        $onToggle = $widget->getOnToggle();

        $props = $this->generateProperties($widget->getStyle());
        $isOnExpr = $isOn ? '$' . $isOn->name : 'false';
        
        $result = <<<XML
{$this->indentStr()}<object class="GtkCheckButton" id="{$id}">
{$this->indentStr()}    <property name="label">{$label}</property>
{$this->indentStr()}    <property name="active">{$isOnExpr}</property>
{$props}
{$this->indentStr()}</object>
XML;

        if ($onToggle !== null) {
            $this->toggleActions[] = ['id' => $id, 'action' => $onToggle];
            $result .= "{$this->indentStr()}<signal name=\"state-set\" handler=\"on_{$id}_state_set\"/>\n";
        }

        return rtrim($result);
    }

    /** @return StyleProperty[] */
    public function supportedStyleProperties(): array
    {
        return [
            StyleProperty::BackgroundColor, StyleProperty::ForegroundColor, StyleProperty::BorderColor,
            StyleProperty::Width, StyleProperty::Height, StyleProperty::MinWidth, StyleProperty::MinHeight,
            StyleProperty::MaxWidth, StyleProperty::MaxHeight, StyleProperty::BorderWidth,
            StyleProperty::CornerRadius, StyleProperty::Margin, StyleProperty::Padding,
            StyleProperty::PaddingTop, StyleProperty::PaddingBottom, StyleProperty::PaddingLeading,
            StyleProperty::PaddingTrailing, StyleProperty::Opacity, StyleProperty::ShadowRadius,
            StyleProperty::ShadowColor, StyleProperty::ShadowOffsetX, StyleProperty::ShadowOffsetY,
            StyleProperty::FontSize, StyleProperty::FontWeight, StyleProperty::FontFamily,
            StyleProperty::TextAlignment, StyleProperty::TextDecoration, StyleProperty::LineSpacing, StyleProperty::LetterSpacing,
            StyleProperty::FlexDirection, StyleProperty::JustifyContent, StyleProperty::AlignItems,
            StyleProperty::FlexWrap, StyleProperty::Gap, StyleProperty::FlexGrow, StyleProperty::FlexShrink,
            // Transform & Animation
            StyleProperty::Rotate, StyleProperty::Scale, StyleProperty::TranslateX, StyleProperty::TranslateY,
            StyleProperty::AnimationDuration, StyleProperty::AnimationDelay, StyleProperty::AnimationEasing,
        ];
    }

    /**
     * Replace closure binding placeholders with actual C values.
     */
    private function replaceClosureBindings(string $code, array $bindings): string
    {
        foreach ($bindings as $name => $value) {
            if (is_string($value)) {
                $replacement = '"' . addslashes($value) . '"';
            } elseif (is_float($value)) {
                $replacement = (string) $value;
                if (!str_contains($replacement, '.')) {
                    $replacement .= '.0';
                }
            } elseif (is_int($value)) {
                $replacement = (string) $value;
            } elseif (is_bool($value)) {
                $replacement = $value ? 'TRUE' : 'FALSE';
            } else {
                $replacement = (string) $value;
            }
            // Use preg_replace_callback to avoid $0 backreference in replacement string
            $code = preg_replace_callback(
                '/\b' . preg_quote($name, '/') . '\b/',
                fn(array $m) => $replacement,
                $code
            );
        }
        return $code;
    }
}
