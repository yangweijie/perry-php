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

final class ArkTsBackend extends CodegenBackend
{
    private int $indent = 0;
    private array $currentBindings = [];

    public function name(): string
    {
        return 'arkts';
    }

    public function supports(Target $target): bool
    {
        return $target === Target::HarmonyOS;
    }

    public function generate(Widget $root): string
    {
        $this->indent = 0;
        $this->currentBindings = [];

        if ($root instanceof AppContainer) {
            return $this->generateAppWithState($root);
        }

        return $this->generateSimpleApp($root);
    }

    private function generateAppWithState(AppContainer $app): string
    {
        $bindings = $app->bindings();
        $this->currentBindings = array_map(fn(Binding $b) => $b->name, $bindings);
        $stateVars = $this->generateStateVars($bindings);
        $body = $this->generateWidget($app->content());

        $modifiers = '';
        $width = $app->windowWidth();
        $height = $app->windowHeight();
        if ($width !== null) {
            $modifiers .= "\n        .width({$width})";
        } else {
            $modifiers .= "\n        .width('100%')";
        }
        if ($height !== null) {
            $modifiers .= "\n        .height({$height})";
        } else {
            $modifiers .= "\n        .height('100%')";
        }

        $containerMods = $this->generateModifiers($app->getStyle());

        return <<<ARKTS
        @Entry
        @Component
        struct PerryApp {
            {$stateVars}

            build() {
                Column() {{$body}
                }{$modifiers}{$containerMods}
            }
        }

        ARKTS;
    }

    private function generateSimpleApp(Widget $root): string
    {
        $body = $this->generateWidget($root);

        return <<<ARKTS
        @Entry
        @Component
        struct PerryApp {
            build() {
                Column() {{$body}
                }
                .width('100%')
                .height('100%')
            }
        }

        ARKTS;
    }

    private function generateStateVars(array $bindings): string
    {
        $vars = [];
        foreach ($bindings as $binding) {
            $initial = $this->formatValue($binding->initialValue);
            $type = $this->arkTsType($binding->initialValue);
            $vars[] = "@State {$binding->name}: {$type} = {$initial};";
        }
        return implode("\n    ", $vars);
    }

    private function arkTsType(mixed $value): string
    {
        if (is_string($value)) {
            return 'string';
        }
        if (is_bool($value)) {
            return 'boolean';
        }
        if (is_int($value)) {
            return 'number';
        }
        if (is_float($value)) {
            return 'number';
        }
        return 'string';
    }

    private function formatValue(mixed $value): string
    {
        if (is_string($value)) {
            return "'" . addslashes($value) . "'";
        }
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }
        if (is_float($value)) {
            $str = (string) $value;
            return str_contains($str, '.') ? $str : $str . '.0';
        }
        if (is_int($value)) {
            return (string) $value;
        }
        return "''";
    }

    private function generateWidget(Widget $widget): string
    {
        return match ($widget->kind()) {
            WidgetKind::Text => $this->generateText($widget),
            WidgetKind::Button => $this->generateButton($widget),
            WidgetKind::VStack => $this->generateVStack($widget),
            WidgetKind::HStack => $this->generateHStack($widget),
            WidgetKind::Spacer => 'Blank()',
            WidgetKind::Image => $this->generateImage($widget),
            WidgetKind::ScrollView => $this->generateScrollView($widget),
            WidgetKind::TextInput => $this->generateTextInput($widget),
            WidgetKind::TextEditor => $this->generateTextEditorWidget($widget),
            WidgetKind::Slider => $this->generateSlider($widget),
            WidgetKind::ListWidget => $this->generateListWidget($widget),
            WidgetKind::NavigationView => $this->generateNavigationView($widget),
            WidgetKind::TabView => $this->generateTabView($widget),
            WidgetKind::Toggle => $this->generateToggle($widget),
            WidgetKind::WebView => $this->generateWebViewWidget($widget),
            default => 'Blank()',
        };
    }

    private function generateText(Text $widget): string
    {
        $binding = $widget->getBinding();
        if ($binding) {
            $content = "this.{$binding->name}.toString()";
        } else {
            $content = "'" . addslashes($widget->content()) . "'";
        }

        $modifiers = $this->generateModifiers($widget->getStyle());
        return "Text({$content}){$modifiers}";
    }

    private function generateButton(Button $widget): string
    {
        $label = addslashes($widget->label());
        $action = $this->generateAction($widget->getAction());
        $modifiers = $this->generateModifiers($widget->getStyle());

        if ($action !== '') {
            return "Button('{$label}', () => {\n{$this->indentStr()}    {$action}\n{$this->indentStr()}}){$modifiers}";
        }
        return "Button('{$label}'){$modifiers}";
    }

    private function generateAction(?Action $action): string
    {
        if ($action === null) {
            return '';
        }

        if ($action->type === ActionType::Closure) {
            $generator = new \Perry\Generator\SwiftGenerator($this->currentBindings);
            return $action->generate($generator);
        }

        return match ($action->type) {
            ActionType::SetValue => "this.{$action->target->name} = {$this->formatValue($action->value)};",
            ActionType::Append => "this.{$action->target->name} += '{$action->value}';",
            ActionType::Clear => "this.{$action->target->name} = {$this->formatValue($action->target->initialValue)};",
            ActionType::Custom => $action->customCode ?? '',
            default => '',
        };
    }

    private function generateVStack(VStack $widget): string
    {
        $this->indent++;
        $children = $this->generateChildren($widget->children());
        $this->indent--;
        $modifiers = $this->generateModifiers($widget->getStyle());
        return "Column() {{$children}\n{$this->indentStr()}}{$modifiers}";
    }

    private function generateHStack(HStack $widget): string
    {
        $this->indent++;
        $children = $this->generateChildren($widget->children());
        $this->indent--;
        $modifiers = $this->generateModifiers($widget->getStyle());
        return "Row() {{$children}\n{$this->indentStr()}}{$modifiers}";
    }

    private function generateImage(Image $widget): string
    {
        $source = addslashes($widget->source());
        return "Image({ src: '{$source}' })";
    }

    private function generateScrollView(ScrollView $widget): string
    {
        $this->indent++;
        $children = $this->generateChildren($widget->children());
        $this->indent--;
        return "Scroll() {{$children}\n{$this->indentStr()}}";
    }

    private function generateTextInput(TextInput $widget): string
    {
        $placeholder = addslashes($widget->placeholder());
        $modifiers = $this->generateModifiers($widget->getStyle());

        return "TextInput({ placeholder: '{$placeholder}' }){$modifiers}";
    }

    private function generateTextEditorWidget(TextEditor $widget): string
    {
        $modifiers = $this->generateModifiers($widget->getStyle());
        return "TextArea({ placeholder: '' }){$modifiers}";
    }

    private function generateWebViewWidget(WebView $widget): string
    {
        $html = $widget->html();
        $html = str_replace('\\', '\\\\', $html);
        $html = str_replace("'", "\\'", $html);
        $html = str_replace("\n", "\\n", $html);
        $html = str_replace("\r", "\\r", $html);
        $html = str_replace("\t", "\\t", $html);
        return "Web({ src: '{$html}' })";
    }

    private function generateSlider(Slider $widget): string
    {
        $binding = $widget->value();
        $name = $binding->name;
        $min = $widget->min();
        $max = $widget->max();
        $step = $widget->step();
        $modifiers = $this->generateModifiers($widget->getStyle());
        return "Slider({ value: this.{$name}, min: {$min}, max: {$max}, step: {$step} }){$modifiers}";
    }

    private function generateListWidget(ListWidget $widget): string
    {
        $this->indent++;
        $children = $this->generateChildren($widget->items());
        $this->indent--;
        return "List() {{$children}\n{$this->indentStr()}}";
    }

    private function generateNavigationView(NavigationView $widget): string
    {
        $this->indent++;
        $children = $this->generateChildren($widget->screens());
        $this->indent--;
        return "NavDestination() {{$children}\n{$this->indentStr()}}";
    }

    private function generateTabView(TabView $widget): string
    {
        $this->indent++;
        $children = $this->generateChildren($widget->tabs());
        $this->indent--;
        return "Tabs() {{$children}\n{$this->indentStr()}}";
    }

    private function generateToggle(Toggle $widget): string
    {
        $label = addslashes($widget->label());
        return "Toggle({ type: ToggleType.Switch })";
    }

    private function generateChildren(array $children): string
    {
        $parts = [];
        foreach ($children as $child) {
            $parts[] = "\n" . $this->indentStr() . $this->generateWidget($child);
        }
        return implode('', $parts);
    }

    private function generateModifiers(?Style $style): string
    {
        if ($style === null) {
            return '';
        }
        $mods = [];
        $props = StyleProperty::class;

        if ($style->has($props::FontSize)) {
            $mods[] = ".fontSize({$style->get($props::FontSize)})";
        }
        if ($style->has($props::ForegroundColor)) {
            $mods[] = ".fontColor({$this->colorExpr($style->get($props::ForegroundColor))})";
        }
        if ($style->has($props::Width) || $style->has($props::Height) || $style->has($props::MinHeight)) {
            $w = $style->has($props::Width) ? "'{$style->get($props::Width)}'" : "'100%'";
            $h = $style->has($props::Height) ? "'{$style->get($props::Height)}'" : "'100%'";
            if ($style->has($props::MinHeight)) {
                $mods[] = ".constraintSize({ minHeight: '{$style->get($props::MinHeight)}px' })";
            } else {
                $mods[] = ".width({$w})";
                $mods[] = ".height({$h})";
            }
        }
        if ($style->has($props::BackgroundColor)) {
            $mods[] = ".backgroundColor({$this->colorExpr($style->get($props::BackgroundColor))})";
        }
        if ($style->has($props::CornerRadius)) {
            $mods[] = ".borderRadius({$style->get($props::CornerRadius)})";
        }
        if ($style->has($props::Padding)) {
            $mods[] = ".padding({$style->get($props::Padding)})";
        }
        if ($style->has($props::Opacity)) {
            $mods[] = ".opacity({$style->get($props::Opacity)})";
        }
        if ($style->has($props::Margin)) {
            $m = $style->get($props::Margin);
            $mods[] = ".margin({ left: {$m} })";
        }
        if ($style->has($props::BorderWidth)) {
            $mods[] = ".borderWidth({$style->get($props::BorderWidth)})";
        }
        if ($style->has($props::BorderColor)) {
            $mods[] = ".borderColor({$this->colorExpr($style->get($props::BorderColor))})";
        }
        if ($style->has($props::ShadowColor) || $style->has($props::ShadowRadius) || $style->has($props::ShadowOffsetX) || $style->has($props::ShadowOffsetY)) {
            $color = $style->has($props::ShadowColor) ? $this->colorExpr($style->get($props::ShadowColor)) : 'Color.Black';
            $radius = $style->has($props::ShadowRadius) ? $style->get($props::ShadowRadius) : 0;
            $x = $style->has($props::ShadowOffsetX) ? $style->get($props::ShadowOffsetX) : 0;
            $y = $style->has($props::ShadowOffsetY) ? $style->get($props::ShadowOffsetY) : 0;
            $mods[] = ".shadow({ radius: {$radius}, color: {$color}, offsetX: {$x}, offsetY: {$y} })";
        }
        if ($style->has($props::FontWeight)) {
            $mods[] = ".fontWeight({$this->mapFontWeight($style->get($props::FontWeight))})";
        }
        if ($style->has($props::FontFamily)) {
            $mods[] = ".fontFamily('{$style->get($props::FontFamily)}')";
        }
        if ($style->has($props::TextAlignment)) {
            $mods[] = ".textAlign({$this->mapTextAlignment($style->get($props::TextAlignment))})";
        }
        if ($style->has($props::TextDecoration)) {
            $decoration = $style->get($props::TextDecoration);
            if ($decoration === 'underline') {
                $mods[] = '.decoration({ type: TextDecorationType.Underline })';
            } elseif ($decoration === 'lineThrough') {
                $mods[] = '.decoration({ type: TextDecorationType.LineThrough })';
            }
        }
        if ($style->has($props::LineSpacing)) {
            $mods[] = ".lineHeight({$style->get($props::LineSpacing)})";
        }
        if ($style->has($props::LetterSpacing)) {
            $mods[] = ".letterSpacing({$style->get($props::LetterSpacing)})";
        }
        $pad = [];
        if ($style->has($props::PaddingTop)) {
            $pad['top'] = (int) $style->get($props::PaddingTop);
        }
        if ($style->has($props::PaddingBottom)) {
            $pad['bottom'] = (int) $style->get($props::PaddingBottom);
        }
        if ($style->has($props::PaddingLeading)) {
            $pad['left'] = (int) $style->get($props::PaddingLeading);
        }
        if ($style->has($props::PaddingTrailing)) {
            $pad['right'] = (int) $style->get($props::PaddingTrailing);
        }
        if ($pad) {
            $parts = implode(', ', array_map(fn($d, $v) => "{$d}: {$v}", array_keys($pad), $pad));
            $mods[] = ".padding({ {$parts} })";
        }

        return $mods ? "\n        " . implode("\n        ", $mods) : '';
    }

    private function colorExpr(string $hex): string
    {
        $hex = ltrim($hex, '#');
        if (strlen($hex) === 3) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }
        if (strlen($hex) !== 6) {
            return 'Color.White';
        }
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));
        return "Color({$r}, {$g}, {$b})";
    }

    private function mapFontWeight(string $weight): string
    {
        $map = [
            'bold' => 'FontWeight.Bold',
            'semibold' => 'FontWeight.SemiBold',
            'medium' => 'FontWeight.Medium',
            'light' => 'FontWeight.Light',
            'regular' => 'FontWeight.Regular',
            700 => 'FontWeight.Bold',
            600 => 'FontWeight.SemiBold',
            500 => 'FontWeight.Medium',
            300 => 'FontWeight.Light',
        ];
        return $map[$weight] ?? 'FontWeight.Regular';
    }

    private function mapTextAlignment(string $alignment): string
    {
        return match ($alignment) {
            'left' => 'TextAlign.Start',
            'right' => 'TextAlign.End',
            'center' => 'TextAlign.Center',
            default => 'TextAlign.Start',
        };
    }

    private function indentStr(): string
    {
        return str_repeat('    ', $this->indent);
    }

    /** @return StyleProperty[] */
    public function supportedStyleProperties(): array
    {
        return [
            StyleProperty::FontSize, StyleProperty::ForegroundColor, StyleProperty::Width,
            StyleProperty::Height, StyleProperty::MinHeight, StyleProperty::BackgroundColor,
            StyleProperty::CornerRadius, StyleProperty::Padding, StyleProperty::Opacity,
            StyleProperty::Margin, StyleProperty::BorderWidth, StyleProperty::BorderColor,
            StyleProperty::ShadowColor, StyleProperty::ShadowRadius, StyleProperty::ShadowOffsetX,
            StyleProperty::ShadowOffsetY, StyleProperty::FontWeight, StyleProperty::FontFamily,
            StyleProperty::TextAlignment, StyleProperty::TextDecoration, StyleProperty::LineSpacing,
            StyleProperty::LetterSpacing,
            StyleProperty::PaddingTop, StyleProperty::PaddingBottom, StyleProperty::PaddingLeading,
            StyleProperty::PaddingTrailing,
        ];
    }
}
