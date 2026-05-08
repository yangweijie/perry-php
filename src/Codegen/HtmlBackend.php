<?php

declare(strict_types=1);

namespace Perry\Codegen;

use Perry\Build\Target;
use Perry\UI\Action;
use Perry\UI\ActionType;
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
use Perry\UI\Binding;
use Perry\UI\WidgetKind;

final class HtmlBackend extends CodegenBackend
{
    private int $indent = 0;
    private array $stateVars = [];
    private array $actionFunctions = [];
    private int $actionCounter = 0;
    public static ?string $customScript = null;
    public static array $innerHTMLVars = [];

    public function name(): string
    {
        return 'html';
    }

    public function supports(Target $target): bool
    {
        return in_array($target, [Target::Web, Target::Wasm], true);
    }

    public function generate(Widget $root): string
    {
        $this->indent = 0;
        $this->actionFunctions = [];
        $this->actionCounter = 0;

        $body = '';
        $title = 'Perry App';

        if ($root instanceof AppContainer) {
            $this->stateVars = array_map(fn(Binding $b) => $b->name, $root->bindings());
            $body = $this->generateWidget($root->content());
        } else {
            $body = $this->generateWidget($root);
        }

        $script = $this->generateScript();

        return <<<HTML
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>{$title}</title>
            <style>
                * { box-sizing: border-box; margin: 0; padding: 0; }
                body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #000; color: #fff; }
                .vstack { display: flex; flex-direction: column; }
                .hstack { display: flex; flex-direction: row; }
                .spacer { flex: 1; }
                button { display: inline-flex; align-items: center; justify-content: center; padding: 8px 16px; border: 1px solid #ccc; border-radius: 4px; cursor: pointer; background: #333; color: #fff; }
                button:hover { background: #555; }
                input[type="text"] { padding: 8px; border: 1px solid #ccc; border-radius: 4px; background: #222; color: #fff; }
                .toggle { display: flex; align-items: center; gap: 8px; }
                .toggle input { width: 40px; height: 20px; }
                .display { font-size: 24px; text-align: right; padding: 16px; background: #111; color: #fff; word-break: break-all; }
                .calc-btn { padding: 16px; font-size: 18px; border: 1px solid #444; background: #222; color: #fff; cursor: pointer; }
                .calc-btn:hover { background: #444; }
                .calc-btn.op { background: #f59e0b; color: #000; }
                .calc-btn.op:hover { background: #d97706; }
                .calc-btn.eq { background: #10b981; color: #fff; }
                .calc-btn.eq:hover { background: #059669; }
            </style>
        </head>
        <body>
            {$body}
            {$script}
        </body>
        </html>
        HTML;
    }

    private function generateWidget(Widget $widget): string
    {
        return match ($widget->kind()) {
            WidgetKind::Text => $this->generateText($widget),
            WidgetKind::Button => $this->generateButton($widget),
            WidgetKind::VStack => $this->generateVStack($widget),
            WidgetKind::HStack => $this->generateHStack($widget),
            WidgetKind::Spacer => '<div class="spacer"></div>',
            WidgetKind::Image => $this->generateImage($widget),
            WidgetKind::ScrollView => $this->generateScrollView($widget),
            WidgetKind::TextInput => $this->generateTextInput($widget),
            WidgetKind::TextEditor => $this->generateTextEditorHtml($widget),
            WidgetKind::Slider => $this->generateSlider($widget),
            WidgetKind::ListWidget => $this->generateListWidget($widget),
            WidgetKind::NavigationView => $this->generateNavigationView($widget),
            WidgetKind::TabView => $this->generateTabView($widget),
            WidgetKind::Toggle => $this->generateToggle($widget),
            WidgetKind::WebView => $this->generateWebViewHtml($widget),
            default => '',
        };
    }

    private function generateText(Text $widget): string
    {
        $binding = $widget->getBinding();
        if ($binding && in_array($binding->name, $this->stateVars)) {
            $id = $binding->name;
            $style = $this->generateStyle($widget->getStyle());
            return "<span id=\"{$id}\"{$style}></span>";
        }

        $text = htmlspecialchars($widget->content());
        $style = $this->generateStyle($widget->getStyle());
        return "<span{$style}>{$text}</span>";
    }

    private function generateButton(Button $widget): string
    {
        $label = htmlspecialchars($widget->label());
        $action = $widget->getAction();

        if ($action === null) {
            $style = $this->generateStyle($widget->getStyle());
            return "<button{$style}>{$label}</button>";
        }

        $funcName = $this->generateActionFunction($action);
        $onclick = " onclick=\"{$funcName}()\"";
        $style = $this->generateStyle($widget->getStyle());
        return "<button{$onclick}{$style}>{$label}</button>";
    }

    private function generateActionFunction(Action $action): string
    {
        $funcName = "action_{$this->actionCounter}";
        $this->actionCounter++;

        if ($action->type === ActionType::Closure) {
            $generator = new \Perry\Generator\JavaScriptGenerator($this->stateVars);
            $body = $action->generate($generator);
            $body = $this->addSemicolons($body);
            $this->actionFunctions[] = "function {$funcName}() {\n    {$body}\n    render();\n}";
        } elseif ($action->type === ActionType::Custom) {
            $code = $action->customCode ?? '';
            $code = $this->addSemicolons($code);
            $this->actionFunctions[] = "function {$funcName}() {\n    {$code}\n    render();\n}";
        } else {
            $this->generateLegacyActionFunction($funcName, $action);
        }

        return $funcName;
    }

    private function generateLegacyActionFunction(string $funcName, Action $action): void
    {
        $target = $action->target?->name ?? '';

        $code = match ($action->type) {
            ActionType::SetValue => "{$target}.value = {$this->formatJsValue($action->value)}",
            ActionType::Append => "{$target}.value += {$this->formatJsValue($action->value)}",
            ActionType::Clear => "{$target}.value = {$this->formatJsValue($action->target?->initialValue)}",
            default => '',
        };

        $this->actionFunctions[] = "function {$funcName}() {\n    {$code}\n    render();\n}";
    }

    private function addSemicolons(string $code): string
    {
        $lines = explode("\n", $code);
        $result = [];
        foreach ($lines as $line) {
            $trimmed = trim($line);
            if ($trimmed === '' || $trimmed === '{' || $trimmed === '}' || $trimmed === '} else {'
                || str_starts_with($trimmed, 'if ') || str_starts_with($trimmed, 'else')
                || str_ends_with($trimmed, '{') || str_ends_with($trimmed, '}')) {
                $result[] = $line;
            } else {
                $trimmed_line = rtrim($line);
                if (!str_ends_with($trimmed_line, ';')) {
                    $result[] = $trimmed_line . ';';
                } else {
                    $result[] = $line;
                }
            }
        }
        return implode("\n    ", $result);
    }

    private function formatJsValue(mixed $value): string
    {
        if (is_string($value)) {
            return "'" . addslashes($value) . "'";
        }
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }
        if (is_float($value)) {
            return (string) $value;
        }
        if (is_int($value)) {
            return (string) $value;
        }
        return "''";
    }

    private function generateScript(): string
    {
        if (empty($this->stateVars) && empty($this->actionFunctions) && self::$customScript === null) {
            return '';
        }

        $stateInit = $this->generateStateInit();
        $renderFunc = $this->generateRenderFunction();
        $textareaSync = $this->generateTextareaSync();
        $customScript = self::$customScript ?? '';
        $actions = implode("\n    ", $this->actionFunctions);

        return <<<HTML

        <script>
        {$stateInit}

        {$renderFunc}

        {$textareaSync}

        {$customScript}

        {$actions}
        </script>
        HTML;
    }

    private function generateStateInit(): string
    {
        if (empty($this->stateVars)) {
            return '';
        }

        $pairs = [];
        foreach ($this->stateVars as $var) {
            $pairs[] = "    {$var}: " . $this->getDefaultValue($var);
        }

        $props = implode(",\n", $pairs);
        return "const state = {\n{$props}\n};";
    }

    private function getDefaultValue(string $var): string
    {
        return match ($var) {
            'display' => "'0'",
            'result' => "''",
            'operand1', 'operand2' => '0',
            'operation' => "''",
            'isTyping' => 'false',
            default => "''",
        };
    }

    private function generateRenderFunction(): string
    {
        if (empty($this->stateVars)) {
            return '';
        }

        $updates = [];
        foreach ($this->stateVars as $var) {
            $prop = in_array($var, self::$innerHTMLVars) ? 'innerHTML' : 'textContent';
            $updates[] = "    const el_{$var} = document.getElementById('{$var}');\n"
                . "    if (el_{$var}) {\n"
                . "        if (el_{$var}.tagName === 'TEXTAREA') { el_{$var}.value = state.{$var}; }\n"
                . "        else { el_{$var}.{$prop} = state.{$var}; }\n"
                . "    }";
        }

        $body = implode("\n", $updates);
        return "function render() {\n{$body}\n}";
    }

    private function generateTextareaSync(): string
    {
        $lines = [];
        foreach ($this->stateVars as $var) {
            $lines[] = "document.addEventListener('input', function(e) {\n"
                . "    if (e.target.id === '{$var}' && e.target.tagName === 'TEXTAREA') {\n"
                . "        state.{$var} = e.target.value;\n"
                . "    }\n"
                . "});";
        }
        return $lines ? implode("\n", $lines) : '';
    }

    private function generateVStack(VStack $widget): string
    {
        $this->indent++;
        $children = $this->generateChildren($widget->children());
        $this->indent--;
        $style = $this->generateStyle($widget->getStyle());
        return "<div class=\"vstack\"{$style}>{$children}</div>";
    }

    private function generateHStack(HStack $widget): string
    {
        $this->indent++;
        $children = $this->generateChildren($widget->children());
        $this->indent--;
        $style = $this->generateStyle($widget->getStyle());
        return "<div class=\"hstack\"{$style}>{$children}</div>";
    }

    private function generateImage(Image $widget): string
    {
        $src = htmlspecialchars($widget->source());
        return "<img src=\"{$src}\" alt=\"\">";
    }

    private function generateScrollView(ScrollView $widget): string
    {
        $children = $this->generateChildren($widget->children());
        return "<div style=\"overflow:auto;max-height:100vh\">{$children}</div>";
    }

    private function generateTextEditorHtml(\Perry\UI\Widget\TextEditor $widget): string
    {
        $binding = $widget->getBinding();
        $id = $binding->name;
        $placeholder = htmlspecialchars($widget->placeholder());
        $style = $this->generateStyle($widget->getStyle());
        return "<textarea id=\"{$id}\" placeholder=\"{$placeholder}\"{$style}></textarea>";
    }

    private function generateWebViewHtml(\Perry\UI\Widget\WebView $widget): string
    {
        $html = htmlspecialchars($widget->html(), ENT_QUOTES);
        return "<iframe srcdoc=\"{$html}\" style=\"width:100%;height:300px;border:none;\"></iframe>";
    }

    private function generateSlider(Slider $widget): string
    {
        $id = 'slider_' . $this->nextId();
        $binding = $widget->value();
        $name = $binding->name;
        $min = $widget->min();
        $max = $widget->max();
        $step = $widget->step();
        $style = $this->generateStyle($widget->getStyle());

        $oninput = '';
        $action = $widget->getOnChange();
        if ($action !== null) {
            $funcName = $this->generateActionFunction($action);
            $oninput = " oninput=\"{$funcName}()\"";
        }

        return "<input type=\"range\" id=\"{$id}\" min=\"{$min}\" max=\"{$max}\" step=\"{$step}\"$oninput{$style}>";
    }

    private function generateTextInput(TextInput $widget): string
    {
        $id = 'textinput_' . $this->nextId();
        $placeholder = htmlspecialchars($widget->placeholder());
        $binding = $widget->value();
        $name = $binding->name;
        $style = $this->generateStyle($widget->getStyle());

        $oninput = '';
        $action = $widget->getOnChange();
        if ($action !== null) {
            $funcName = $this->generateActionFunction($action);
            $oninput = " oninput=\"{$funcName}()\"";
        }

        return "<input type=\"text\" id=\"{$id}\" placeholder=\"{$placeholder}\"$oninput{$style}>";
    }

    private function generateToggle(Toggle $widget): string
    {
        $id = 'toggle_' . $this->nextId();
        $label = htmlspecialchars($widget->label());

        $onclick = '';
        $action = $widget->getOnToggle();
        if ($action !== null) {
            $funcName = $this->generateActionFunction($action);
            $onclick = " onclick=\"{$funcName}()\"";
        }

        return "<label{$onclick}><input type=\"checkbox\" id=\"{$id}\"> {$label}</label>";
    }

    private function generateListWidget(\Perry\UI\Widget\ListWidget $widget): string
    {
        $this->indent++;
        $children = $this->generateChildren($widget->items());
        $this->indent--;
        return "<ul class=\"list\">\n{$this->indentStr()}{$children}\n{$this->indentStr()}</ul>";
    }

    private function generateNavigationView(NavigationView $widget): string
    {
        $this->indent++;
        $children = $this->generateChildren($widget->screens());
        $this->indent--;
        return "<div class=\"nav-view\">\n{$this->indentStr()}{$children}\n{$this->indentStr()}</div>";
    }

    private function generateTabView(TabView $widget): string
    {
        $this->indent++;
        $children = $this->generateChildren($widget->tabs());
        $this->indent--;
        return "<div class=\"tab-view\">\n{$this->indentStr()}{$children}\n{$this->indentStr()}</div>";
    }

    private function generateChildren(array $children): string
    {
        $parts = [];
        foreach ($children as $child) {
            $parts[] = $this->generateWidget($child);
        }
        return implode("\n", $parts);
    }

    private function generateStyle(?\Perry\UI\Styling\Style $style): string
    {
        if ($style === null) {
            return '';
        }

        $css = [];

        if ($style->has(StyleProperty::BackgroundColor)) {
            $css[] = "background-color: {$style->get(StyleProperty::BackgroundColor)}";
        }
        if ($style->has(StyleProperty::ForegroundColor)) {
            $css[] = "color: {$style->get(StyleProperty::ForegroundColor)}";
        }
        if ($style->has(StyleProperty::FontSize)) {
            $css[] = "font-size: {$style->get(StyleProperty::FontSize)}px";
        }
        if ($style->has(StyleProperty::Padding)) {
            $css[] = "padding: {$style->get(StyleProperty::Padding)}px";
        }
        if ($style->has(StyleProperty::CornerRadius)) {
            $css[] = "border-radius: {$style->get(StyleProperty::CornerRadius)}px";
        }
        if ($style->has(StyleProperty::Opacity)) {
            $css[] = "opacity: {$style->get(StyleProperty::Opacity)}";
        }
        if ($style->has(StyleProperty::Width)) {
            $css[] = "width: {$style->get(StyleProperty::Width)}px";
        }
        if ($style->has(StyleProperty::Height)) {
            $css[] = "height: {$style->get(StyleProperty::Height)}px";
        }
        if ($style->has(StyleProperty::MinWidth)) {
            $css[] = "min-width: {$style->get(StyleProperty::MinWidth)}px";
        }
        if ($style->has(StyleProperty::MinHeight)) {
            $css[] = "min-height: {$style->get(StyleProperty::MinHeight)}px";
        }
        if ($style->has(StyleProperty::MaxWidth)) {
            $css[] = "max-width: {$style->get(StyleProperty::MaxWidth)}px";
        }
        if ($style->has(StyleProperty::MaxHeight)) {
            $css[] = "max-height: {$style->get(StyleProperty::MaxHeight)}px";
        }
        if ($style->has(StyleProperty::Margin)) {
            $css[] = "margin: {$style->get(StyleProperty::Margin)}px";
        }
        if ($style->has(StyleProperty::BorderWidth)) {
            $css[] = "border-width: {$style->get(StyleProperty::BorderWidth)}px";
        }
        if ($style->has(StyleProperty::BorderColor)) {
            $css[] = "border-color: {$style->get(StyleProperty::BorderColor)}";
        }
        if ($style->has(StyleProperty::PaddingTop)) {
            $css[] = "padding-top: {$style->get(StyleProperty::PaddingTop)}px";
        }
        if ($style->has(StyleProperty::PaddingBottom)) {
            $css[] = "padding-bottom: {$style->get(StyleProperty::PaddingBottom)}px";
        }
        if ($style->has(StyleProperty::PaddingLeading)) {
            $css[] = "padding-left: {$style->get(StyleProperty::PaddingLeading)}px";
        }
        if ($style->has(StyleProperty::PaddingTrailing)) {
            $css[] = "padding-right: {$style->get(StyleProperty::PaddingTrailing)}px";
        }
        if ($style->has(StyleProperty::FontWeight)) {
            $css[] = "font-weight: {$this->mapFontWeight($style->get(StyleProperty::FontWeight))}";
        }
        if ($style->has(StyleProperty::FontFamily)) {
            $css[] = "font-family: \"{$style->get(StyleProperty::FontFamily)}\"";
        }
        if ($style->has(StyleProperty::TextAlignment)) {
            $css[] = "text-align: {$this->mapTextAlignment($style->get(StyleProperty::TextAlignment))}";
        }
        if ($style->has(StyleProperty::TextDecoration)) {
            $css[] = "text-decoration: {$this->mapTextDecoration($style->get(StyleProperty::TextDecoration))}";
        }
        if ($style->has(StyleProperty::LineSpacing)) {
            $css[] = "line-height: {$style->get(StyleProperty::LineSpacing)}px";
        }

        // Box-shadow: combine ShadowOffsetX, ShadowOffsetY, ShadowRadius, ShadowColor
        $shadowX = $style->has(StyleProperty::ShadowOffsetX) ? $style->get(StyleProperty::ShadowOffsetX) : 0;
        $shadowY = $style->has(StyleProperty::ShadowOffsetY) ? $style->get(StyleProperty::ShadowOffsetY) : 0;
        $shadowBlur = $style->has(StyleProperty::ShadowRadius) ? $style->get(StyleProperty::ShadowRadius) : 0;
        $shadowColor = $style->has(StyleProperty::ShadowColor) ? $style->get(StyleProperty::ShadowColor) : null;
        if ($style->has(StyleProperty::ShadowColor) || $style->has(StyleProperty::ShadowRadius)
            || $style->has(StyleProperty::ShadowOffsetX) || $style->has(StyleProperty::ShadowOffsetY)) {
            $css[] = "box-shadow: {$shadowX}px {$shadowY}px {$shadowBlur}px {$shadowColor}";
        }

        if (empty($css)) {
            return '';
        }

        $styleStr = implode('; ', $css);
        return " style=\"{$styleStr}\"";
    }

    private function mapFontWeight($weight): string
    {
        $map = [
            'bold' => 'bold',
            'normal' => 'normal',
            'light' => 'lighter',
            'semibold' => '600',
            'medium' => '500',
            'regular' => 'normal',
        ];
        return $map[$weight] ?? $weight;
    }

    private function mapTextAlignment(string $alignment): string
    {
        return match ($alignment) {
            'left' => 'left',
            'right' => 'right',
            'center' => 'center',
            'justify' => 'justify',
            default => 'left',
        };
    }

    private function mapTextDecoration(string $decoration): string
    {
        return match ($decoration) {
            'underline' => 'underline',
            'line-through' => 'line-through',
            'overline' => 'overline',
            default => 'none',
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
            StyleProperty::BackgroundColor, StyleProperty::ForegroundColor, StyleProperty::FontSize,
            StyleProperty::Padding, StyleProperty::CornerRadius, StyleProperty::Opacity,
            StyleProperty::Width, StyleProperty::Height, StyleProperty::MinWidth,
            StyleProperty::MinHeight, StyleProperty::MaxWidth, StyleProperty::MaxHeight,
            StyleProperty::Margin, StyleProperty::BorderWidth, StyleProperty::BorderColor,
            StyleProperty::PaddingTop, StyleProperty::PaddingBottom, StyleProperty::PaddingLeading,
            StyleProperty::PaddingTrailing, StyleProperty::FontWeight, StyleProperty::FontFamily,
            StyleProperty::TextAlignment, StyleProperty::TextDecoration, StyleProperty::LineSpacing,
            StyleProperty::ShadowColor, StyleProperty::ShadowRadius, StyleProperty::ShadowOffsetX,
            StyleProperty::ShadowOffsetY,
        ];
    }
}
