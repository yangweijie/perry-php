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

/**
 * WasmBackend — generates self-contained HTML with perry_ui_* bridge API.
 *
 * Differs from HtmlBackend: generates programmatic JS using the perry_ui_*
 * widget handle API (compatible with perry-ts WASM architecture) instead of
 * declarative HTML. Includes a JS runtime that implements the bridge via DOM
 * manipulation, and hooks for loading actual WASM binaries via bootPerryWasm().
 *
 * Generated output structure:
 *   - <style> base CSS
 *   - <script> wasm_runtime.js (embedded perry_ui_* bridge)
 *   - <script> app code using perry_ui_* calls to build widget tree
 */
final class WasmBackend extends CodegenBackend
{
    private array $lines = [];
    private array $stateVars = [];
    private array $actionFunctions = [];
    private int $actionCounter = 0;

    public function name(): string
    {
        return 'wasm';
    }

    public function supports(Target $target): bool
    {
        return in_array($target, [Target::Wasm, Target::Web], true);
    }

    public function generate(Widget $root): string
    {
        $this->lines = [];
        $this->actionFunctions = [];
        $this->actionCounter = 0;

        $handle = 0;
        if ($root instanceof AppContainer) {
            $this->stateVars = array_map(fn(Binding $b) => $b->name, $root->bindings());
            $this->emitWidget($root->content(), $handle);
        } else {
            $this->emitWidget($root, $handle);
        }

        $this->lines[] = "perry_ui_mount({$handle});";

        $appCode = implode("\n", $this->lines);
        $runtimeJs = file_get_contents(__DIR__ . '/wasm_runtime.js');
        $actionsJs = implode("\n", $this->actionFunctions);

        $title = 'Perry App';

        return <<<HTML
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>{$title}</title>
            <style>
                * { box-sizing: border-box; margin: 0; padding: 0; }
                html, body { width: 100vw; min-height: 100vh; overflow-x: hidden; }
                body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #000; color: #fff; }
                #perry-root { width: 100%; min-height: 100vh; display: flex; flex-direction: column; }
                .perry-flex-row { display: flex; flex-direction: row; }
                .perry-flex-col { display: flex; flex-direction: column; }
                .perry-spacer { flex: 1; }
                .perry-scroll { overflow: auto; }
                .perry-list { list-style: none; }
                .perry-nav-view { display: flex; flex-direction: column; }
                .perry-tab-view { display: flex; flex-direction: column; }
                button { display: inline-flex; align-items: center; justify-content: center; padding: 8px 16px; border: 1px solid #ccc; border-radius: 4px; cursor: pointer; background: #333; color: #fff; }
                button:hover { background: #555; }
                input[type="text"], input[type="range"] { padding: 8px; border: 1px solid #ccc; border-radius: 4px; background: #222; color: #fff; }
                textarea { padding: 8px; border: 1px solid #ccc; border-radius: 4px; background: #222; color: #fff; font-family: monospace; resize: vertical; }
                .perry-toggle { display: flex; align-items: center; gap: 8px; }
                iframe { width: 100%; height: 300px; border: none; }
            </style>
        </head>
        <body>
            <div id="perry-root"></div>
            <script>
        {$runtimeJs}
            </script>
            <script>
        (function() {
        'use strict';
        {$appCode}
        })();
            </script>
            <script>
        {$actionsJs}
            </script>
        </body>
        </html>
        HTML;
    }

    private function emitWidget(Widget $widget, int &$handle): void
    {
        match ($widget->kind()) {
            WidgetKind::Text => $this->emitText($widget, $handle),
            WidgetKind::Button => $this->emitButton($widget, $handle),
            WidgetKind::VStack => $this->emitVStack($widget, $handle),
            WidgetKind::HStack => $this->emitHStack($widget, $handle),
            WidgetKind::Spacer => $this->emitSpacer($handle),
            WidgetKind::Image => $this->emitImage($widget, $handle),
            WidgetKind::ScrollView => $this->emitScrollView($widget, $handle),
            WidgetKind::TextInput => $this->emitTextInput($widget, $handle),
            WidgetKind::TextEditor => $this->emitTextEditor($widget, $handle),
            WidgetKind::Slider => $this->emitSlider($widget, $handle),
            WidgetKind::Toggle => $this->emitToggle($widget, $handle),
            WidgetKind::ListWidget => $this->emitListWidget($widget, $handle),
            WidgetKind::NavigationView => $this->emitNavigationView($widget, $handle),
            WidgetKind::TabView => $this->emitTabView($widget, $handle),
            WidgetKind::WebView => $this->emitWebView($widget, $handle),
            default => $this->emitDiv($handle),
        };
    }

    private function emitText(Text $widget, int &$handle): void
    {
        $handle = $this->nextHandle();
        $binding = $widget->getBinding();
        if ($binding && in_array($binding->name, $this->stateVars)) {
            $this->addLine("var w{$handle} = perry_ui_createWidget('span');");
            $this->addLine("perry_ui_setId(w{$handle}, '{$binding->name}');");
        } else {
            $text = $this->escapeJs($widget->content());
            $this->addLine("var w{$handle} = perry_ui_createWidget('span');");
            $this->addLine("perry_ui_setTextContent(w{$handle}, '{$text}');");
        }
        $this->emitStyle($widget->getStyle(), $handle);
    }

    private function emitButton(Button $widget, int &$handle): void
    {
        $handle = $this->nextHandle();
        $label = $this->escapeJs($widget->label());
        $this->addLine("var w{$handle} = perry_ui_createWidget('button');");
        $this->addLine("perry_ui_setTextContent(w{$handle}, '{$label}');");

        $action = $widget->getAction();
        if ($action !== null) {
            $funcName = $this->emitAction($action);
            $this->addLine("perry_ui_onClick(w{$handle}, function() {{$funcName}(); render?.();});");
        }

        $this->emitStyle($widget->getStyle(), $handle);
    }

    private function emitVStack(VStack $widget, int &$handle): void
    {
        $handle = $this->nextHandle();
        $this->addLine("var w{$handle} = perry_ui_createWidget('div');");
        $this->addLine("perry_ui_addClass(w{$handle}, 'perry-flex-col');");
        $this->emitStyle($widget->getStyle(), $handle);

        foreach ($widget->children() as $child) {
            $childHandle = 0;
            $this->emitWidget($child, $childHandle);
            $this->addLine("perry_ui_addChild(w{$handle}, w{$childHandle});");
        }
    }

    private function emitHStack(HStack $widget, int &$handle): void
    {
        $handle = $this->nextHandle();
        $this->addLine("var w{$handle} = perry_ui_createWidget('div');");
        $this->addLine("perry_ui_addClass(w{$handle}, 'perry-flex-row');");
        $this->emitStyle($widget->getStyle(), $handle);

        foreach ($widget->children() as $child) {
            $childHandle = 0;
            $this->emitWidget($child, $childHandle);
            $this->addLine("perry_ui_addChild(w{$handle}, w{$childHandle});");
        }
    }

    private function emitSpacer(int &$handle): void
    {
        $handle = $this->nextHandle();
        $this->addLine("var w{$handle} = perry_ui_createWidget('div');");
        $this->addLine("perry_ui_addClass(w{$handle}, 'perry-spacer');");
    }

    private function emitImage(Image $widget, int &$handle): void
    {
        $handle = $this->nextHandle();
        $src = $this->escapeJs($widget->source());
        $this->addLine("var w{$handle} = perry_ui_createWidget('img');");
        $this->addLine("perry_ui_setAttribute(w{$handle}, 'src', '{$src}');");
    }

    private function emitScrollView(ScrollView $widget, int &$handle): void
    {
        $handle = $this->nextHandle();
        $this->addLine("var w{$handle} = perry_ui_createWidget('div');");
        $this->addLine("perry_ui_addClass(w{$handle}, 'perry-scroll');");
        $this->addLine("perry_ui_setStyle(w{$handle}, 'max-height', '100vh');");

        foreach ($widget->children() as $child) {
            $childHandle = 0;
            $this->emitWidget($child, $childHandle);
            $this->addLine("perry_ui_addChild(w{$handle}, w{$childHandle});");
        }
    }

    private function emitTextInput(TextInput $widget, int &$handle): void
    {
        $handle = $this->nextHandle();
        $placeholder = $this->escapeJs($widget->placeholder());
        $this->addLine("var w{$handle} = perry_ui_createWidget('input');");
        $this->addLine("perry_ui_setAttribute(w{$handle}, 'type', 'text');");
        $this->addLine("perry_ui_setAttribute(w{$handle}, 'placeholder', '{$placeholder}');");

        $binding = $widget->value();
        $name = $binding->name;
        if (in_array($name, $this->stateVars, true)) {
            $this->addLine("perry_ui_setId(w{$handle}, '{$name}');");
        }

        $action = $widget->getOnChange();
        if ($action !== null) {
            $funcName = $this->emitAction($action);
            $this->addLine("perry_ui_onInput(w{$handle}, function() {{$funcName}(); render?.();});");
        }

        $this->emitStyle($widget->getStyle(), $handle);
    }

    private function emitTextEditor(TextEditor $widget, int &$handle): void
    {
        $handle = $this->nextHandle();
        $placeholder = $this->escapeJs($widget->placeholder());
        $this->addLine("var w{$handle} = perry_ui_createWidget('textarea');");
        $this->addLine("perry_ui_setAttribute(w{$handle}, 'placeholder', '{$placeholder}');");

        $binding = $widget->getBinding();
        if ($binding && in_array($binding->name, $this->stateVars, true)) {
            $this->addLine("perry_ui_setId(w{$handle}, '{$binding->name}');");
        }

        $this->emitStyle($widget->getStyle(), $handle);
    }

    private function emitSlider(Slider $widget, int &$handle): void
    {
        $handle = $this->nextHandle();
        $min = $widget->min();
        $max = $widget->max();
        $step = $widget->step();
        $this->addLine("var w{$handle} = perry_ui_createWidget('input');");
        $this->addLine("perry_ui_setAttribute(w{$handle}, 'type', 'range');");
        $this->addLine("perry_ui_setAttribute(w{$handle}, 'min', '{$min}');");
        $this->addLine("perry_ui_setAttribute(w{$handle}, 'max', '{$max}');");
        $this->addLine("perry_ui_setAttribute(w{$handle}, 'step', '{$step}');");

        $binding = $widget->value();
        $name = $binding->name;
        if (in_array($name, $this->stateVars, true)) {
            $this->addLine("perry_ui_setId(w{$handle}, '{$name}');");
        }

        $action = $widget->getOnChange();
        if ($action !== null) {
            $funcName = $this->emitAction($action);
            $this->addLine("perry_ui_onInput(w{$handle}, function() {{$funcName}(); render?.();});");
        }

        $this->emitStyle($widget->getStyle(), $handle);
    }

    private function emitToggle(Toggle $widget, int &$handle): void
    {
        $handle = $this->nextHandle();
        $label = $this->escapeJs($widget->label());

        // Generate container row with checkbox + label
        $this->addLine("var w{$handle} = perry_ui_createWidget('label');");
        $this->addLine("perry_ui_addClass(w{$handle}, 'perry-toggle');");

        $cb = $this->nextHandle();
        $this->addLine("var w{$cb} = perry_ui_createWidget('input');");
        $this->addLine("perry_ui_setAttribute(w{$cb}, 'type', 'checkbox');");
        $this->addLine("perry_ui_addChild(w{$handle}, w{$cb});");

        $action = $widget->getOnToggle();
        if ($action !== null) {
            $funcName = $this->emitAction($action);
            $this->addLine("perry_ui_onClick(w{$cb}, function() {{$funcName}(); render?.();});");
        }

        $textHandle = $this->nextHandle();
        $this->addLine("var w{$textHandle} = perry_ui_createWidget('span');");
        $this->addLine("perry_ui_setTextContent(w{$textHandle}, '{$label}');");
        $this->addLine("perry_ui_addChild(w{$handle}, w{$textHandle});");
    }

    private function emitListWidget(ListWidget $widget, int &$handle): void
    {
        $handle = $this->nextHandle();
        $this->addLine("var w{$handle} = perry_ui_createWidget('ul');");
        $this->addLine("perry_ui_addClass(w{$handle}, 'perry-list');");

        foreach ($widget->items() as $item) {
            $itemHandle = $this->nextHandle();
            $this->addLine("var w{$itemHandle} = perry_ui_createWidget('li');");
            $this->addLine("perry_ui_addChild(w{$handle}, w{$itemHandle});");

            if ($item instanceof Widget) {
                $innerHandle = 0;
                $this->emitWidget($item, $innerHandle);
                $this->addLine("perry_ui_addChild(w{$itemHandle}, w{$innerHandle});");
            }
        }
    }

    private function emitNavigationView(NavigationView $widget, int &$handle): void
    {
        $handle = $this->nextHandle();
        $this->addLine("var w{$handle} = perry_ui_createWidget('div');");
        $this->addLine("perry_ui_addClass(w{$handle}, 'perry-nav-view');");

        foreach ($widget->screens() as $screen) {
            $childHandle = 0;
            $this->emitWidget($screen, $childHandle);
            $this->addLine("perry_ui_addChild(w{$handle}, w{$childHandle});");
        }
    }

    private function emitTabView(TabView $widget, int &$handle): void
    {
        $handle = $this->nextHandle();
        $this->addLine("var w{$handle} = perry_ui_createWidget('div');");
        $this->addLine("perry_ui_addClass(w{$handle}, 'perry-tab-view');");

        foreach ($widget->tabs() as $tab) {
            $childHandle = 0;
            $this->emitWidget($tab, $childHandle);
            $this->addLine("perry_ui_addChild(w{$handle}, w{$childHandle});");
        }
    }

    private function emitWebView(\Perry\UI\Widget\WebView $widget, int &$handle): void
    {
        $handle = $this->nextHandle();
        $html = $this->escapeJs($widget->html());
        $this->addLine("var w{$handle} = perry_ui_createWidget('iframe');");
        $this->addLine("perry_ui_setAttribute(w{$handle}, 'srcdoc', '{$html}');");
        $this->addLine("perry_ui_setStyle(w{$handle}, 'width', '100%');");
        $this->addLine("perry_ui_setStyle(w{$handle}, 'height', '300px');");
        $this->addLine("perry_ui_setStyle(w{$handle}, 'border', 'none');");
    }

    private function emitDiv(int &$handle): void
    {
        $handle = $this->nextHandle();
        $this->addLine("var w{$handle} = perry_ui_createWidget('div');");
    }

    private function emitStyle(?\Perry\UI\Styling\Style $style, int $handle): void
    {
        if ($style === null) {
            return;
        }

        if ($style->has(StyleProperty::BackgroundColor)) {
            $v = $this->escapeJs($style->get(StyleProperty::BackgroundColor));
            $this->addLine("perry_ui_setStyle(w{$handle}, 'background-color', '{$v}');");
        }
        if ($style->has(StyleProperty::ForegroundColor)) {
            $v = $this->escapeJs($style->get(StyleProperty::ForegroundColor));
            $this->addLine("perry_ui_setStyle(w{$handle}, 'color', '{$v}');");
        }
        if ($style->has(StyleProperty::FontSize)) {
            $this->addLine("perry_ui_setStyle(w{$handle}, 'font-size', '{$style->get(StyleProperty::FontSize)}px');");
        }
        if ($style->has(StyleProperty::Padding)) {
            $this->addLine("perry_ui_setStyle(w{$handle}, 'padding', '{$style->get(StyleProperty::Padding)}px');");
        }
        if ($style->has(StyleProperty::CornerRadius)) {
            $this->addLine("perry_ui_setStyle(w{$handle}, 'border-radius', '{$style->get(StyleProperty::CornerRadius)}px');");
        }
        if ($style->has(StyleProperty::Opacity)) {
            $this->addLine("perry_ui_setStyle(w{$handle}, 'opacity', '{$style->get(StyleProperty::Opacity)}');");
        }
        if ($style->has(StyleProperty::Width)) {
            $this->addLine("perry_ui_setStyle(w{$handle}, 'width', '{$style->get(StyleProperty::Width)}px');");
        }
        if ($style->has(StyleProperty::Height)) {
            $this->addLine("perry_ui_setStyle(w{$handle}, 'height', '{$style->get(StyleProperty::Height)}px');");
        }
        if ($style->has(StyleProperty::MinWidth)) {
            $this->addLine("perry_ui_setStyle(w{$handle}, 'min-width', '{$style->get(StyleProperty::MinWidth)}px');");
        }
        if ($style->has(StyleProperty::MinHeight)) {
            $this->addLine("perry_ui_setStyle(w{$handle}, 'min-height', '{$style->get(StyleProperty::MinHeight)}px');");
        }
        if ($style->has(StyleProperty::MaxWidth)) {
            $this->addLine("perry_ui_setStyle(w{$handle}, 'max-width', '{$style->get(StyleProperty::MaxWidth)}px');");
        }
        if ($style->has(StyleProperty::MaxHeight)) {
            $this->addLine("perry_ui_setStyle(w{$handle}, 'max-height', '{$style->get(StyleProperty::MaxHeight)}px');");
        }
        if ($style->has(StyleProperty::Margin)) {
            $this->addLine("perry_ui_setStyle(w{$handle}, 'margin', '{$style->get(StyleProperty::Margin)}px');");
        }
        if ($style->has(StyleProperty::BorderWidth)) {
            $this->addLine("perry_ui_setStyle(w{$handle}, 'border-width', '{$style->get(StyleProperty::BorderWidth)}px');");
        }
        if ($style->has(StyleProperty::BorderColor)) {
            $v = $this->escapeJs($style->get(StyleProperty::BorderColor));
            $this->addLine("perry_ui_setStyle(w{$handle}, 'border-color', '{$v}');");
        }
        if ($style->has(StyleProperty::PaddingTop)) {
            $this->addLine("perry_ui_setStyle(w{$handle}, 'padding-top', '{$style->get(StyleProperty::PaddingTop)}px');");
        }
        if ($style->has(StyleProperty::PaddingBottom)) {
            $this->addLine("perry_ui_setStyle(w{$handle}, 'padding-bottom', '{$style->get(StyleProperty::PaddingBottom)}px');");
        }
        if ($style->has(StyleProperty::PaddingLeading)) {
            $this->addLine("perry_ui_setStyle(w{$handle}, 'padding-left', '{$style->get(StyleProperty::PaddingLeading)}px');");
        }
        if ($style->has(StyleProperty::PaddingTrailing)) {
            $this->addLine("perry_ui_setStyle(w{$handle}, 'padding-right', '{$style->get(StyleProperty::PaddingTrailing)}px');");
        }
        if ($style->has(StyleProperty::FontWeight)) {
            $this->addLine("perry_ui_setStyle(w{$handle}, 'font-weight', '{$this->mapFontWeight($style->get(StyleProperty::FontWeight))}');");
        }
        if ($style->has(StyleProperty::TextAlignment)) {
            $this->addLine("perry_ui_setStyle(w{$handle}, 'text-align', '{$this->mapTextAlignment($style->get(StyleProperty::TextAlignment))}');");
        }
        if ($style->has(StyleProperty::TextDecoration)) {
            $this->addLine("perry_ui_setStyle(w{$handle}, 'text-decoration', '{$this->mapTextDecoration($style->get(StyleProperty::TextDecoration))}');");
        }
        if ($style->has(StyleProperty::LineSpacing)) {
            $this->addLine("perry_ui_setStyle(w{$handle}, 'line-height', '{$style->get(StyleProperty::LineSpacing)}px');");
        }

        // Shadow
        $sx = $style->has(StyleProperty::ShadowOffsetX) ? $style->get(StyleProperty::ShadowOffsetX) : 0;
        $sy = $style->has(StyleProperty::ShadowOffsetY) ? $style->get(StyleProperty::ShadowOffsetY) : 0;
        $sb = $style->has(StyleProperty::ShadowRadius) ? $style->get(StyleProperty::ShadowRadius) : 0;
        $sc = $style->has(StyleProperty::ShadowColor) ? $style->get(StyleProperty::ShadowColor) : null;
        if ($style->has(StyleProperty::ShadowColor) || $style->has(StyleProperty::ShadowRadius)
            || $style->has(StyleProperty::ShadowOffsetX) || $style->has(StyleProperty::ShadowOffsetY)) {
            $scSafe = $this->escapeJs($sc ?? '#000');
            $this->addLine("perry_ui_setStyle(w{$handle}, 'box-shadow', '{$sx}px {$sy}px {$sb}px {$scSafe}');");
        }
    }

    private function emitAction(Action $action): string
    {
        $funcName = "action_{$this->actionCounter}";
        $this->actionCounter++;

        if ($action->type === ActionType::Closure) {
            $generator = new \Perry\Generator\JavaScriptGenerator($this->stateVars);
            $body = $action->generate($generator);
            $body = $this->addSemicolons($body);
            $this->addActionFunction($funcName, $body);
        } elseif ($action->type === ActionType::Custom) {
            $code = $action->customCode ?? '';
            $code = $this->addSemicolons($code);
            $this->addActionFunction($funcName, $code);
        } elseif ($action->type === ActionType::SetValue) {
            $target = $action->target?->name ?? '';
            $code = "{$target}.value = {$this->formatJsValue($action->value)}";
            $this->addActionFunction($funcName, $code);
        } elseif ($action->type === ActionType::Append) {
            $target = $action->target?->name ?? '';
            $code = "{$target}.value += {$this->formatJsValue($action->value)}";
            $this->addActionFunction($funcName, $code);
        } elseif ($action->type === ActionType::Clear) {
            $target = $action->target?->name ?? '';
            $init = $this->formatJsValue($action->target?->initialValue ?? '');
            $code = "{$target}.value = {$init}";
            $this->addActionFunction($funcName, $code);
        }

        return $funcName;
    }

    private function addActionFunction(string $funcName, string $body): void
    {
        $this->actionFunctions[] = "function {$funcName}() {\n    {$body}\n}";
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
        if (is_float($value) || is_int($value)) {
            return (string) $value;
        }
        return "''";
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

    private function addLine(string $line): void
    {
        $this->lines[] = $line;
    }

    private function nextHandle(): int
    {
        return ++$this->objectId;
    }

    private function escapeJs(string $value): string
    {
        return str_replace(["\\", "'", "\n", "\r"], ["\\\\", "\\'", "\\n", ""], $value);
    }
}
