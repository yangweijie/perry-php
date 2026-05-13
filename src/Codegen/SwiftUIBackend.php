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
use Perry\UI\Widget\Checkbox;
use Perry\UI\Widget\ContextMenu;
use Perry\UI\Widget\DatePicker;
use Perry\UI\Widget\Dialog;
use Perry\UI\Widget\Dropdown;
use Perry\UI\Widget\SegmentedControl;
use Perry\UI\Widget\HStack;
use Perry\UI\Widget\Image;
use Perry\UI\Widget\ListWidget;
use Perry\UI\Widget\NavigationView;
use Perry\UI\Widget\Progress;
use Perry\UI\Widget\RadioButton;
use Perry\UI\Widget\ScrollView;
use Perry\UI\Widget\Slider;
use Perry\UI\Widget\Spacer;
use Perry\UI\Widget\TabView;
use Perry\UI\Widget\Text;
use Perry\UI\Widget\TextEditor;
use Perry\UI\Widget\TextInput;
use Perry\UI\Widget\Toast;
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

        // Apply AppContainer style modifiers
        $containerMods = $this->generateModifiers($app->getStyle());

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
        {$body}{$containerMods}
                .frame(maxWidth: .infinity, maxHeight: .infinity)
            }
        }
        {$webViewStruct}
        SWIFT;
    }

    private function generateSimpleApp(Widget $root): string
    {
        $body = $this->generateWidget($root);

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
        {$body}
                }
            }
        }
        {$webViewStruct}
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
            WidgetKind::Checkbox => $this->generateCheckbox($widget),
            WidgetKind::RadioButton => $this->generateRadioButton($widget),
            WidgetKind::Dialog => $this->generateDialog($widget),
            WidgetKind::Dropdown => $this->generateDropdown($widget),
            WidgetKind::Progress => $this->generateProgress($widget),
            WidgetKind::Toast => $this->generateToast($widget),
            WidgetKind::SegmentedControl => $this->generateSegmentedControl($widget),
            WidgetKind::ContextMenu => $this->generateContextMenuWidget($widget),
            WidgetKind::DatePicker => $this->generateDatePickerWidget($widget),
            default => 'EmptyView()',
        };
    }

    private function generateText(Text $widget): string
    {
        $binding = $widget->getBinding();
        if ($binding) {
            // SwiftUI Text needs string interpolation for state variables
            $content = '"\\(' . $binding->name . ')"';
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
        $modifiers = $this->generateModifiers($widget->getStyle());
        return "VStack(spacing: {$spacing}) {\n{$children}\n{$this->indentStr()}}{$modifiers}";
    }

    private function generateHStack(HStack $widget): string
    {
        $this->indent++;
        $children = $this->generateChildren($widget->children());
        $this->indent--;
        $spacing = $this->getSpacing($widget->getStyle());
        $modifiers = $this->generateModifiers($widget->getStyle());
        return "HStack(spacing: {$spacing}) {\n{$children}\n{$this->indentStr()}}{$modifiers}";
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
        $onEditingChanged = '';
        if ($action !== null) {
            // onEditingChanged expects (Bool) -> Void closure
            $actionBody = $this->generateAction($action);
            $onEditingChanged = ", onEditingChanged: { _ in\n" . $this->indentStr() . "    " . $actionBody . "\n" . $this->indentStr() . "}";
        }

        return "Slider(value: \${$name}, in: {$min}...{$max}, step: {$step}{$onEditingChanged}){$modifiers}";
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
        $isOn = $widget->getIsOn();
        $onToggle = $widget->getOnToggle();

        $isOnExpr = '.constant(false)';
        if ($isOn !== null) {
            $isOnExpr = '$' . $isOn->name;
        }

        $result = "Toggle(\"{$label}\", isOn: {$isOnExpr})";
        
        if ($onToggle !== null) {
            // onChange needs wrapped value (without $), not Binding
            $onChangeExpr = $isOnExpr;
            if (str_starts_with($isOnExpr, '$')) {
                $onChangeExpr = substr($isOnExpr, 1);
            }
            $result .= ".onChange(of: " . $onChangeExpr . ") {" . $this->generateAction($onToggle) . "}";
        }

        return $result;
    }

    private function generateCheckbox(Checkbox $widget): string
    {
        $label = addslashes($widget->label());
        $isChecked = $widget->getIsChecked();
        $bindingExpr = $isChecked ? '$' . $isChecked->name : '.constant(false)';

        $result = "Toggle(\"{$label}\", isOn: {$bindingExpr}).toggleStyle(.checkbox)";

        $onChange = $widget->getOnChange();
        if ($onChange !== null) {
            $varName = $isChecked ? $isChecked->name : 'false';
            $result .= ".onChange(of: {$varName}) {" . $this->generateAction($onChange) . "}";
        }

        return $result;
    }

    private function generateRadioButton(RadioButton $widget): string
    {
        $label = addslashes($widget->label());
        $value = addslashes($widget->getValue());
        $selected = $widget->getSelectedValue();

        return "Button(action: { {$selected->name} = \"{$value}\" }) {\n{$this->indentStr()}    Text(\"{$label}\")\n{$this->indentStr()}}";
    }

    private function generateDialog(Dialog $widget): string
    {
        $isOpen = $widget->getIsOpen();
        $this->indent++;
        $children = $this->generateChildren($widget->children());
        $this->indent--;

        if ($isOpen) {
            return "VStack {\n{$children}\n{$this->indentStr()}}\n{$this->indentStr()}.opacity({$isOpen->name} ? 1.0 : 0.0)";
        }
        return "VStack {\n{$children}\n{$this->indentStr()}}";
    }

    private function generateDropdown(Dropdown $widget): string
    {
        $selected = $widget->getSelectedValue();
        $selectedName = $selected ? $selected->name : 'selection';
        $options = $widget->options();

        $parts = [];
        foreach ($options as $label => $val) {
            $escapedLabel = addslashes((string) $label);
            $escapedVal = addslashes((string) $val);
            $parts[] = "Text(\"{$escapedLabel}\").tag(\"{$escapedVal}\")";
        }
        $optionsCode = implode("\n{$this->indentStr()}", $parts);

        $result = "Picker(\"{$selectedName}\", selection: \${$selectedName}) {\n{$this->indentStr()}{$optionsCode}\n{$this->indentStr()}}";

        $onChange = $widget->getOnChange();
        if ($onChange !== null) {
            $result .= ".onChange(of: {$selectedName}) {" . $this->generateAction($onChange) . "}";
        }

        return $result;
    }

    private function generateProgress(Progress $widget): string
    {
        $progress = $widget->getProgress();
        if ($progress) {
            return "ProgressView(value: \${$progress->name})";
        }
        return "ProgressView()";
    }

    private function generateToast(Toast $widget): string
    {
        $message = addslashes($widget->message());
        return "Text(\"{$message}\")";
    }

    private function generateSegmentedControl(SegmentedControl $widget): string
    {
        $selected = $widget->getSelectedValue();
        $selectedName = $selected ? $selected->name : 'selection';
        $parts = [];
        foreach ($widget->options() as $label => $value) {
            $escLabel = addslashes((string) $label);
            $parts[] = "Text(\"{$escLabel}\").tag(\"{$value}\")";
        }
        $optionsCode = implode("\n{$this->indentStr()}", $parts);

        $result = "Picker(\"{$selectedName}\", selection: \${$selectedName}) {\n{$this->indentStr()}{$optionsCode}\n{$this->indentStr()}}\n{$this->indentStr()}.pickerStyle(.segmented)";

        $onChange = $widget->getOnChange();
        if ($onChange !== null && $selected) {
            $result .= "\n{$this->indentStr()}.onChange(of: {$selected->name}) {" . $this->generateAction($onChange) . "}";
        }

        $modifiers = $this->generateModifiers($widget->getStyle());
        $result .= $modifiers;
        return $result;
    }

    private function generateContextMenuWidget(ContextMenu $widget): string
    {
        $isOpen = $widget->getIsOpen();
        $openExpr = $isOpen ? $isOpen->name : 'false';
        $items = [];
        foreach ($widget->items() as $label => $value) {
            $escLabel = addslashes((string) $label);
            $items[] = "Button(\"{$escLabel}\") { {$openExpr} = false }";
        }
        $itemsCode = implode("\n{$this->indentStr()}    ", $items);
        $modifiers = $this->generateModifiers($widget->getStyle());
        return "Text(\"Menu\").contextMenu {\n{$this->indentStr()}    {$itemsCode}\n{$this->indentStr()}}{$modifiers}";
    }

    private function generateDatePickerWidget(DatePicker $widget): string
    {
        $date = $widget->getDate();
        $dateVar = $date ? '$' . $date->name : '.constant(Date())';
        $modifiers = $this->generateModifiers($widget->getStyle());
        return "DatePicker(\"Date\", selection: {$dateVar}, displayedComponents: .date){$modifiers}";
    }

    private function generateModifiers(?\Perry\UI\Styling\Style $style): string
    {
        if ($style === null) {
            return '';
        }
        $mods = [];
        $props = \Perry\UI\Styling\StyleProperty::class;

        if ($style->has($props::FontSize)) {
            $mods[] = '.font(.system(size: ' . $style->get($props::FontSize) . '))';
        }
        if ($style->has($props::FontWeight)) {
            $v = $style->get($props::FontWeight);
            $map = ['bold' => '.bold', 'semibold' => '.semibold', 'medium' => '.medium', 'normal' => '.regular', 'light' => '.light'];
            $mods[] = '.fontWeight(' . ($map[$v] ?? '.regular') . ')';
        }
        if ($style->has($props::TextDecoration)) {
             $v = $style->get($props::TextDecoration);
             $map = ['underline' => '.underline', 'line-through' => '.strikethrough', 'overline' => '.overline'];
             $mods[] = $map[$v] ?? '';
         }
         if ($style->has($props::ForegroundColor)) {
            $hex = ltrim($style->get($props::ForegroundColor), '#');
            if (strlen($hex) === 3) {
                $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
            }
            $r = round(hexdec(substr($hex, 0, 2)) / 255, 2);
            $g = round(hexdec(substr($hex, 2, 2)) / 255, 2);
            $b = round(hexdec(substr($hex, 4, 2)) / 255, 2);
            $mods[] = sprintf('.foregroundColor(Color(red: %.2f, green: %.2f, blue: %.2f))', $r, $g, $b);
        }
        if ($style->has($props::FontFamily)) {
            $size = $style->has($props::FontSize) ? $style->get($props::FontSize) : 17;
            $mods[] = '.font(.custom("' . $style->get($props::FontFamily) . '", size: ' . $size . '))';
        }
        if ($style->has($props::TextAlignment)) {
            $v = $style->get($props::TextAlignment);
            $map = ['left' => '.leading', 'center' => '.center', 'right' => '.trailing'];
            $mods[] = '.multilineTextAlignment(TextAlignment' . ($map[$v] ?? '.leading') . ')';
        }
        if ($style->has($props::LineSpacing)) {
            $mods[] = '.lineSpacing(' . $style->get($props::LineSpacing) . ')';
        }
        if ($style->has($props::Padding)) {
            $mods[] = '.padding(' . $style->get($props::Padding) . ')';
        }
        if ($style->has($props::CornerRadius)) {
            $mods[] = '.cornerRadius(' . $style->get($props::CornerRadius) . ')';
        }
        if ($style->has($props::Opacity)) {
            $mods[] = '.opacity(' . $style->get($props::Opacity) . ')';
        }
        if ($style->has($props::BackgroundColor)) {
            $hex = ltrim($style->get($props::BackgroundColor), '#');
            if (strlen($hex) === 3) {
                $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
            }
            $r = round(hexdec(substr($hex, 0, 2)) / 255, 2);
            $g = round(hexdec(substr($hex, 2, 2)) / 255, 2);
            $b = round(hexdec(substr($hex, 4, 2)) / 255, 2);
            $mods[] = sprintf('.background(Color(red: %.2f, green: %.2f, blue: %.2f))', $r, $g, $b);
        }
        if ($style->has($props::Width) || $style->has($props::Height)) {
            $w = $style->has($props::Width) ? $style->get($props::Width) : 'nil';
            $h = $style->has($props::Height) ? $style->get($props::Height) : 'nil';
            $mods[] = ".frame(width: {$w}, height: {$h})";
        }
        if ($style->has($props::BorderWidth)) {
            $w = $style->get($props::BorderWidth);
            $color = $style->has($props::BorderColor) ? $style->get($props::BorderColor) : '#000000';
            $hex = ltrim($color, '#');
            if (strlen($hex) === 3) {
                $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
            }
            $r = round(hexdec(substr($hex, 0, 2)) / 255, 2);
            $g = round(hexdec(substr($hex, 2, 2)) / 255, 2);
            $b = round(hexdec(substr($hex, 4, 2)) / 255, 2);
            $mods[] = ".overlay(RoundedRectangle(cornerRadius: 0).stroke(Color(red: {$r}, green: {$g}, blue: {$b}), lineWidth: {$w}))";
        }
        if ($style->has($props::ShadowColor)) {
            $hex = ltrim($style->get($props::ShadowColor), '#');
            $radius = $style->has($props::ShadowRadius) ? $style->get($props::ShadowRadius) : 4;
            $ox = $style->has($props::ShadowOffsetX) ? $style->get($props::ShadowOffsetX) : 0;
            $oy = $style->has($props::ShadowOffsetY) ? $style->get($props::ShadowOffsetY) : 2;
            if (strlen($hex) === 3) {
                $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
            }
            $r = round(hexdec(substr($hex, 0, 2)) / 255, 2);
            $g = round(hexdec(substr($hex, 2, 2)) / 255, 2);
            $b = round(hexdec(substr($hex, 4, 2)) / 255, 2);
            $mods[] = ".shadow(color: Color(red: {$r}, green: {$g}, blue: {$b}), radius: {$radius}, x: {$ox}, y: {$oy})";
        }
        if ($style->has($props::PaddingLeading)) {
            $mods[] = '.padding(.leading, ' . $style->get($props::PaddingLeading) . ')';
        }
        if ($style->has($props::PaddingTrailing)) {
            $mods[] = '.padding(.trailing, ' . $style->get($props::PaddingTrailing) . ')';
        }
        if ($style->has($props::PaddingTop)) {
            $mods[] = '.padding(.top, ' . $style->get($props::PaddingTop) . ')';
        }
        if ($style->has($props::PaddingBottom)) {
            $mods[] = '.padding(.bottom, ' . $style->get($props::PaddingBottom) . ')';
        }
        if ($style->has($props::FlexGrow)) {
            $mods[] = '.frame(maxWidth: .infinity)';
        }
        if ($style->has($props::JustifyContent)) {
            $v = $style->get($props::JustifyContent);
            $map = ['center' => '.frame(maxWidth: .infinity)', 'end' => '.frame(maxWidth: .infinity)', 'space-between' => '.frame(maxWidth: .infinity)'];
            $mods[] = $map[$v] ?? '.frame(maxWidth: .infinity)';
        }
        if ($style->has($props::AlignItems)) {
             $v = $style->get($props::AlignItems);
             $map = ['center' => '.center', 'start' => '.leading', 'end' => '.trailing'];
             $mods[] = '.frame(alignment: ' . ($map[$v] ?? '.center') . ')';
         }
         if ($style->has($props::Rotate)) {
            $mods[] = '.rotationEffect(.degrees(' . $style->get($props::Rotate) . '))';
        }
        if ($style->has($props::Scale)) {
            $mods[] = '.scaleEffect(' . $style->get($props::Scale) . ')';
        }
        if ($style->has($props::TranslateX) || $style->has($props::TranslateY)) {
            $x = $style->has($props::TranslateX) ? $style->get($props::TranslateX) : 0;
            $y = $style->has($props::TranslateY) ? $style->get($props::TranslateY) : 0;
            $mods[] = ".offset(x: {$x}, y: {$y})";
        }

        return implode('', $mods);
    }

    private function indentStr(): string
    {
        return str_repeat('    ', $this->indent);
    }

    private function generateChildren(array $children): string
    {
        $parts = [];
        foreach ($children as $child) {
            $parts[] = $this->indentStr() . $this->generateWidget($child);
        }
        return implode("\n", $parts);
    }

    private function getSpacing(?\Perry\UI\Styling\Style $style): string
    {
        return '0';
    }

    /** @return StyleProperty[] */
    public function supportedStyleProperties(): array
    {
        return [
            StyleProperty::ForegroundColor, StyleProperty::BackgroundColor, StyleProperty::FontSize,
            StyleProperty::FontWeight, StyleProperty::FontFamily, StyleProperty::TextAlignment,
            StyleProperty::TextDecoration, StyleProperty::Opacity, StyleProperty::CornerRadius,
            StyleProperty::Padding, StyleProperty::PaddingTop, StyleProperty::PaddingBottom,
            StyleProperty::PaddingLeading, StyleProperty::PaddingTrailing, StyleProperty::Width,
            StyleProperty::Height, StyleProperty::MinHeight, StyleProperty::Margin,
            StyleProperty::BorderWidth, StyleProperty::BorderColor, StyleProperty::ShadowColor,
            StyleProperty::ShadowRadius, StyleProperty::ShadowOffsetX, StyleProperty::ShadowOffsetY,
            StyleProperty::LineSpacing, StyleProperty::FlexDirection, StyleProperty::JustifyContent,
            StyleProperty::AlignItems, StyleProperty::FlexWrap, StyleProperty::Gap,
            StyleProperty::FlexGrow, StyleProperty::FlexShrink,
            // Transform & Animation
            StyleProperty::Rotate, StyleProperty::Scale, StyleProperty::TranslateX, StyleProperty::TranslateY,
            StyleProperty::AnimationDuration, StyleProperty::AnimationEasing,
        ];
    }
}
