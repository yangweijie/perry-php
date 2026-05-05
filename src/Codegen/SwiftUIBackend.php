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

final class SwiftUIBackend extends CodegenBackend
{
    private int $indent = 0;
    private array $currentBindings = [];
    private bool $hasWebView = false;

    public function name(): string
    {
        return 'swiftui';
    }

    public function supports(Target $target): bool
    {
        return $target->isApple();
    }

    public function generate(Widget $root): string
    {
        $this->indent = 0;
        $this->currentBindings = [];
        $this->hasWebView = false;

        if ($root instanceof AppContainer) {
            return $this->generateAppWithState($root);
        }

        return $this->generateSimpleApp($root);
    }

    private function generateAppWithState(AppContainer $app): string
    {
        $bindings = $app->bindings();
        $this->currentBindings = array_map(fn($b) => $b->name, $bindings);
        $stateVars = $this->generateStateVars($bindings);
        $body = $this->generateWidget($app->content());

        $width = $app->windowWidth();
        $height = $app->windowHeight();
        $frameCode = '';
        if ($width !== null && $height !== null) {
            $frameCode = ".frame(width: {$width}, height: {$height})";
        }

        $importWebKit = $this->hasWebView ? "\nimport WebKit\n" : '';
        $webViewStruct = $this->hasWebView ? <<<'SWIFT'

        struct WebViewWrapper: NSViewRepresentable {
            let html: String

            func makeNSView(context: Context) -> WKWebView {
                let webView = WKWebView()
                webView.uiDelegate = context.coordinator
                webView.loadHTMLString(html, baseURL: nil)
                return webView
            }

            func updateNSView(_ nsView: WKWebView, context: Context) {}

            func makeCoordinator() -> Coordinator { Coordinator() }

            class Coordinator: NSObject, WKUIDelegate {
                func webView(_ webView: WKWebView,
                             runJavaScriptTextInputPanelWithPrompt prompt: String,
                             defaultText: String?,
                             initiatedByFrame frame: WKFrameInfo,
                             completionHandler: @escaping (String?) -> Void) {
                    let alert = NSAlert()
                    alert.messageText = prompt
                    alert.addButton(withTitle: "OK")
                    alert.addButton(withTitle: "Cancel")
                    let scrollView = NSScrollView(frame: NSRect(x: 0, y: 0, width: 480, height: 300))
                    scrollView.hasVerticalScroller = true
                    scrollView.borderType = .noBorder
                    let textView = NSTextView(frame: scrollView.bounds)
                    textView.isVerticallyResizable = true
                    textView.isHorizontallyResizable = false
                    textView.autoresizingMask = [.width, .height]
                    textView.textContainer?.containerSize = NSSize(width: scrollView.bounds.width, height: CGFloat.greatestFiniteMagnitude)
                    textView.textContainer?.widthTracksTextView = true
                    textView.font = NSFont.monospacedSystemFont(ofSize: 13, weight: .regular)
                    textView.string = defaultText ?? ""
                    scrollView.documentView = textView
                    alert.accessoryView = scrollView
                    if alert.runModal() == .alertFirstButtonReturn {
                        completionHandler(textView.string)
                    } else {
                        completionHandler(nil)
                    }
                }
            }
        }
        SWIFT : '';

        return <<<SWIFT
        import SwiftUI
        {$importWebKit}
        @main
        struct PerryApp: App {
            var body: some Scene {
                WindowGroup {
                    ContentView()
                        {$frameCode}
                }
                .windowResizability(.contentSize)
            }
        }

        struct ContentView: View {
            {$stateVars}

            var body: some View {
        {$body}
                .frame(maxWidth: .infinity, maxHeight: .infinity)
                .background(Color.black)
            }
        }
        {$webViewStruct}
        SWIFT;
    }

    private function generateSimpleApp(Widget $root): string
    {
        $body = $this->generateWidget($root);

        return <<<SWIFT
        import SwiftUI

        @main
        struct PerryApp: App {
            var body: some Scene {
                WindowGroup {
        {$body}
                }
            }
        }
        SWIFT;
    }

    private function generateStateVars(array $bindings): string
    {
        $vars = [];
        foreach ($bindings as $binding) {
            $initial = $this->formatValue($binding->initialValue);
            $vars[] = "@State private var {$binding->name} = {$initial}";
        }
        return implode("\n    ", $vars);
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
            return str_contains($str, '.') ? $str : $str . '.0';
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
            WidgetKind::Spacer => 'Spacer()',
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
            default => 'EmptyView()',
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

        $modifiers = $this->generateModifiers($widget->getStyle());
        return "Text({$content}){$modifiers}";
    }

    private function generateButton(Button $widget): string
    {
        $label = addslashes($widget->label());
        $action = $this->generateAction($widget->getAction());
        $modifiers = $this->generateModifiers($widget->getStyle());
        return "Button(action: {{$action}}) {\n{$this->indentStr()}    Text(\"{$label}\"){$modifiers}\n{$this->indentStr()}}";
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
        return "VStack(spacing: {$spacing}) {\n{$children}\n{$this->indentStr()}}";
    }

    private function generateHStack(HStack $widget): string
    {
        $this->indent++;
        $children = $this->generateChildren($widget->children());
        $this->indent--;
        $spacing = $this->getSpacing($widget->getStyle());
        return "HStack(spacing: {$spacing}) {\n{$children}\n{$this->indentStr()}}";
    }

    private function generateImage(Image $widget): string
    {
        $source = addslashes($widget->source());
        return "Image(\"{$source}\")";
    }

    private function generateScrollView(ScrollView $widget): string
    {
        $this->indent++;
        $children = $this->generateChildren($widget->children());
        $this->indent--;
        return "ScrollView {\n{$children}\n{$this->indentStr()}}";
    }

    private function generateTextInput(TextInput $widget): string
    {
        $placeholder = addslashes($widget->placeholder());
        $binding = $widget->value();
        $name = $binding->name;
        $modifiers = $this->generateModifiers($widget->getStyle());

        $action = $widget->getOnChange();
        $onChange = '';
        if ($action !== null) {
            $onChange = ' onEditingChanged: {' . $this->generateAction($action) . '}';
        }

        return "TextField(\"{$placeholder}\", text: \${$name}){$modifiers}{$onChange}";
    }

    private function generateTextEditorWidget(TextEditor $widget): string
    {
        $binding = $widget->getBinding();
        $bindingName = $binding->name;
        $modifiers = $this->generateModifiers($widget->getStyle());
        return "TextEditor(text: \${$bindingName}){$modifiers}";
    }

    private function generateWebViewWidget(WebView $widget): string
    {
        $this->hasWebView = true;
        $html = $widget->html();
        // Escape for Swift string literal
        $html = str_replace('\\', '\\\\', $html);
        $html = str_replace('"', '\\"', $html);
        $html = str_replace("\n", "\\n", $html);
        $html = str_replace("\r", "\\r", $html);
        $html = str_replace("\t", "\\t", $html);
        return "WebViewWrapper(html: \"{$html}\")";
    }

    private function generateSlider(Slider $widget): string
    {
        $binding = $widget->value();
        $name = $binding->name;
        $min = $widget->min();
        $max = $widget->max();
        $step = $widget->step();
        $modifiers = $this->generateModifiers($widget->getStyle());

        $action = $widget->getOnChange();
        $onChange = '';
        if ($action !== null) {
            $onChange = ' onEditingChanged: {' . $this->generateAction($action) . '}';
        }

        return "Slider(value: \${$name}, in: {$min}...{$max}, step: {$step}){$modifiers}{$onChange}";
    }

    private function generateListWidget(\Perry\UI\Widget\ListWidget $widget): string
    {
        $this->indent++;
        $children = $this->generateChildren($widget->items());
        $this->indent--;
        return "List {\n{$children}\n{$this->indentStr()}}";
    }

    private function generateNavigationView(NavigationView $widget): string
    {
        $this->indent++;
        $children = $this->generateChildren($widget->screens());
        $this->indent--;
        return "NavigationView {\n{$children}\n{$this->indentStr()}}";
    }

    private function generateTabView(TabView $widget): string
    {
        $this->indent++;
        $children = $this->generateChildren($widget->tabs());
        $this->indent--;
        return "TabView {\n{$children}\n{$this->indentStr()}}";
    }

    private function generateToggle(Toggle $widget): string
    {
        $label = addslashes($widget->label());
        $action = $widget->getOnToggle();
        $onToggle = '';
        if ($action !== null) {
            $onToggle = ' onTapGesture: {' . $this->generateAction($action) . '}';
        }
        return "Toggle(\"{$label}\", isOn: .constant(false)){$onToggle}";
    }

    private function generateChildren(array $children): string
    {
        $parts = [];
        foreach ($children as $child) {
            $parts[] = $this->indentStr() . $this->generateWidget($child);
        }
        return implode("\n", $parts);
    }

    private function generateModifiers(?Style $style): string
    {
        if ($style === null) {
            return '';
        }
        $mods = [];
        $props = \Perry\UI\Styling\StyleProperty::class;

        if ($style->has($props::FontSize)) {
            $mods[] = ".font(.system(size: {$style->get($props::FontSize)}))";
        }
        if ($style->has($props::ForegroundColor)) {
            $mods[] = ".foregroundColor({$this->colorExpr($style->get($props::ForegroundColor))})";
        }
        if ($style->has($props::Width) || $style->has($props::Height) || $style->has($props::MinHeight)) {
            $w = $style->has($props::Width) ? $style->get($props::Width) : 'nil';
            $h = $style->has($props::Height) ? $style->get($props::Height) : 'nil';
            if ($style->has($props::MinHeight)) {
                $mods[] = ".frame(minHeight: {$style->get($props::MinHeight)})";
            } else {
                $mods[] = ".frame(width: {$w}, height: {$h})";
            }
        }
        if ($style->has($props::BackgroundColor)) {
            $mods[] = ".background({$this->colorExpr($style->get($props::BackgroundColor))})";
        }
        if ($style->has($props::CornerRadius)) {
            $mods[] = ".cornerRadius({$style->get($props::CornerRadius)})";
        }
        if ($style->has($props::Padding)) {
            $mods[] = ".padding({$style->get($props::Padding)})";
        }
        if ($style->has($props::Opacity)) {
            $mods[] = ".opacity({$style->get($props::Opacity)})";
        }
        if ($style->has($props::Margin)) {
            $m = $style->get($props::Margin);
            $mods[] = ".padding(.horizontal, {$m})";
        }
        if ($style->has($props::BorderWidth)) {
            $mods[] = ".border(width: {$style->get($props::BorderWidth)})";
        }
        if ($style->has($props::BorderColor)) {
            $mods[] = ".borderColor({$this->colorExpr($style->get($props::BorderColor))})";
        }
        if ($style->has($props::ShadowColor) || $style->has($props::ShadowRadius) || $style->has($props::ShadowOffsetX) || $style->has($props::ShadowOffsetY)) {
            $color = $style->has($props::ShadowColor) ? $this->colorExpr($style->get($props::ShadowColor)) : 'Color.black';
            $radius = $style->has($props::ShadowRadius) ? $style->get($props::ShadowRadius) : 0;
            $x = $style->has($props::ShadowOffsetX) ? $style->get($props::ShadowOffsetX) : 0;
            $y = $style->has($props::ShadowOffsetY) ? $style->get($props::ShadowOffsetY) : 0;
            $mods[] = ".shadow(color: {$color}, radius: {$radius}, x: {$x}, y: {$y})";
        }
        if ($style->has($props::FontWeight)) {
            $mods[] = ".fontWeight({$this->mapFontWeight($style->get($props::FontWeight))})";
        }
        if ($style->has($props::FontFamily)) {
            $mods[] = ".font(.custom(\"{$style->get($props::FontFamily)}\"))";
        }
        if ($style->has($props::TextAlignment)) {
            $mods[] = ".multilineTextAlignment({$this->mapTextAlignment($style->get($props::TextAlignment))})";
        }
        if ($style->has($props::TextDecoration)) {
            $decoration = $style->get($props::TextDecoration);
            $underline = $decoration === 'underline' || $decoration === 'lineThrough' ? 'true' : 'false';
            $mods[] = ".underline({$underline})";
        }
        if ($style->has($props::LineSpacing)) {
            $mods[] = ".lineSpacing({$style->get($props::LineSpacing)})";
        }
        if ($style->has($props::PaddingTop)) {
            $mods[] = ".padding(.top, {$style->get($props::PaddingTop)})";
        }
        if ($style->has($props::PaddingBottom)) {
            $mods[] = ".padding(.bottom, {$style->get($props::PaddingBottom)})";
        }
        if ($style->has($props::PaddingLeading)) {
            $mods[] = ".padding(.leading, {$style->get($props::PaddingLeading)})";
        }
        if ($style->has($props::PaddingTrailing)) {
            $mods[] = ".padding(.trailing, {$style->get($props::PaddingTrailing)})";
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
            return 'Color.white';
        }
        $r = hexdec(substr($hex, 0, 2)) / 255;
        $g = hexdec(substr($hex, 2, 2)) / 255;
        $b = hexdec(substr($hex, 4, 2)) / 255;
        return sprintf('Color(red: %.2f, green: %.2f, blue: %.2f)', $r, $g, $b);
    }

    private function mapFontWeight(int $weight): string
    {
        return match ($weight) {
            700 => 'Font.Weight.bold',
            600 => 'Font.Weight.semibold',
            500 => 'Font.Weight.medium',
            300 => 'Font.Weight.light',
            default => 'Font.Weight.regular',
        };
    }

    private function mapTextAlignment(string $alignment): string
    {
        return match ($alignment) {
            'left' => 'TextAlignment.leading',
            'right' => 'TextAlignment.trailing',
            'center' => 'TextAlignment.center',
            default => 'TextAlignment.leading',
        };
    }

    private function mapTextDecoration(string $decoration): string
    {
        return match ($decoration) {
            'underline' => 'true',
            'lineThrough' => 'true', // SwiftUI uses underline for both
            default => 'false',
        };
    }

    private function getSpacing(?Style $style): string
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
