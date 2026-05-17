<?php

declare(strict_types=1);

namespace Perry\Codegen;

use Perry\Build\Target;
use Perry\UI\Action;
use Perry\UI\ActionType;
use Perry\UI\Styling\StyleProperty;
use Perry\UI\Widget;
use Perry\UI\Widget\AppContainer;
use Perry\UI\Widget\AnimatedContainer;
use Perry\UI\Widget\Button;
use Perry\UI\Widget\Checkbox;
use Perry\UI\Widget\ContextMenu;
use Perry\UI\Widget\DatePicker;
use Perry\UI\Widget\Dialog;
use Perry\UI\Widget\Dropdown;
use Perry\UI\Widget\HStack;
use Perry\UI\Widget\Image;
use Perry\UI\Widget\ListWidget;
use Perry\UI\Widget\NavigationView;
use Perry\UI\Widget\Progress;
use Perry\UI\Widget\RadioButton;
use Perry\UI\Widget\ScrollView;
use Perry\UI\Widget\SegmentedControl;
use Perry\UI\Widget\Slider;
use Perry\UI\Widget\Spacer;
use Perry\UI\Widget\TabView;
use Perry\UI\Widget\Text;
use Perry\UI\Widget\TextEditor;
use Perry\UI\Widget\TextInput;
use Perry\UI\Widget\Toast;
use Perry\UI\Widget\Transition;
use Perry\UI\Widget\Toggle;
use Perry\UI\Widget\VStack;
use Perry\UI\Binding;
use Perry\UI\WidgetKind;
use Perry\UI\Styling\Theme;
use Perry\UI\Styling\StyleCache;

final class HtmlBackend extends CodegenBackend
{
    private int $indent = 0;
    private array $stateVars = [];
    private array $actionFunctions = [];
    private int $actionCounter = 0;
    private array $generatedNamedActions = [];
    private int $responsiveCounter = 0;
    private array $responsiveStyles = [];
    private ?Theme $theme = null;
    private ?StyleCache $styleCache = null;
    /** @var array<string, Binding> */
    private array $stateBindings = [];

    public function setTheme(?Theme $theme): self
    {
        $this->theme = $theme;
        return $this;
    }

    public function getTheme(): ?Theme
    {
        return $this->theme;
    }

    public function setCache(?StyleCache $cache): self
    {
        $this->styleCache = $cache;
        return $this;
    }
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
        $this->generatedNamedActions = [];
        $this->responsiveCounter = 0;
        $this->responsiveStyles = [];
        // Reset static properties to avoid cross-call contamination (#5)
        self::$innerHTMLVars = [];
        self::$customScript = null;

        $body = '';
        $title = 'Perry App';
        $containerStyle = '';

        if ($root instanceof AppContainer) {
            $bindings = $root->bindings();
            $this->stateVars = array_map(fn(Binding $b) => $b->name, $bindings);
            foreach ($bindings as $b) {
                $this->stateBindings[$b->name] = $b;
            }
            $innerBody = $this->generateWidget($root->content());

            $w = $root->windowWidth();
            $h = $root->windowHeight();
            if ($w !== null && $h !== null) {
                $body = "<div class=\"app-container\" style=\"width: {$w}px; height: {$h}px;\">\n{$innerBody}\n</div>";
                $containerStyle = ".app-container { margin: 0 auto; overflow: hidden; }\n";
            } else {
                $body = $innerBody;
            }
        } else {
            $this->collectBindings($root);
            $body = $this->generateWidget($root);
        }

        $script = $this->generateScript();
        $responsiveCSS = $this->emitResponsiveStyles();
        $themeCSS = $this->theme !== null ? Theme::toCssCustomProperties() : '';

        return <<<HTML
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>{$title}</title>
            <style>
                * { box-sizing: border-box; margin: 0; padding: 0; }
                body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #000; display: flex; justify-content: center; align-items: center; min-height: 100vh; color: #000; }
                .vstack { display: flex; flex-direction: column; }
                .hstack { display: flex; flex-direction: row; }
                .spacer { flex: 1; }
                button { display: inline-flex; align-items: center; justify-content: center; padding: 8px 16px; border: 1px solid #ccc; border-radius: 4px; cursor: pointer; background: #333; color: #fff; }
                button:hover { background: #555; }
                input[type="text"] { padding: 8px; border: 1px solid #ccc; border-radius: 4px; background: #222; color: #fff; }
                .toggle { display: flex; align-items: center; gap: 8px; }
                .toggle input { width: 40px; height: 20px; }
                .tab-view { width: 100%; }
                .tab-bar { display: flex; border-bottom: 1px solid #ccc; margin-bottom: 8px; }
                .tab-btn { padding: 8px 16px; border: none; background: transparent; color: inherit; cursor: pointer; border-bottom: 2px solid transparent; }
                .tab-btn:hover { background: rgba(128,128,128,0.1); }
                .tab-btn.active { border-bottom-color: #007AFF; color: #007AFF; font-weight: bold; }
                .tab-panel { display: none; }
                .tab-panel:first-child { display: block; }
                .display { font-size: 24px; text-align: right; padding: 16px; background: #111; color: #fff; word-break: break-all; }
                .calc-btn { padding: 16px; font-size: 18px; border: 1px solid #444; background: #222; color: #fff; cursor: pointer; }
                .calc-btn:hover { background: #444; }
                .calc-btn.op { background: #f59e0b; color: #000; }
                .calc-btn.op:hover { background: #d97706; }
                .calc-btn.eq { background: #10b981; color: #fff; }
                .calc-btn.eq:hover { background: #059669; }
                {$containerStyle}
            </style>
            {$responsiveCSS}
            {$themeCSS}
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
        if ($widget instanceof \Perry\UI\Composition) {
            return $this->generateWidget($widget->toWidget());
        }
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
            WidgetKind::Checkbox => $this->generateCheckbox($widget),
            WidgetKind::RadioButton => $this->generateRadioButton($widget),
            WidgetKind::Dialog => $this->generateDialog($widget),
            WidgetKind::Dropdown => $this->generateDropdown($widget),
            WidgetKind::Progress => $this->generateProgress($widget),
            WidgetKind::Toast => $this->generateToast($widget),
            WidgetKind::SegmentedControl => $this->generateSegmentedControl($widget),
            WidgetKind::ContextMenu => $this->generateContextMenu($widget),
            WidgetKind::DatePicker => $this->generateDatePicker($widget),
            WidgetKind::WebView => $this->generateWebViewHtml($widget),
            WidgetKind::AnimatedContainer => $this->generateAnimatedContainer($widget),
            WidgetKind::Transition => $this->generateTransition($widget),
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

        $funcName = $this->generateActionFunction($action, $widget->getActionName());
        $onclick = " onclick=\"{$funcName}()\"";
        $style = $this->generateStyle($widget->getStyle());
        return "<button{$onclick}{$style}>{$label}</button>";
    }

    private function generateActionFunction(Action $action, ?string $actionName = null): string
    {
        if ($actionName !== null) {
            if (isset($this->generatedNamedActions[$actionName])) {
                return $actionName;
            }
            $this->generatedNamedActions[$actionName] = true;
            $funcName = $actionName;
        } else {
            $funcName = "action_{$this->actionCounter}";
            $this->actionCounter++;
        }

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

        $var = in_array($target, $this->stateVars) ? "state.{$target}" : $target;

        $code = match ($action->type) {
            ActionType::SetValue => "{$var} = {$this->formatJsValue($action->value)}",
            ActionType::Append => "{$var} += {$this->formatJsValue($action->value)}",
            ActionType::Clear => "{$var} = {$this->formatJsValue($action->target?->initialValue)}",
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
            $escaped = str_replace(
                ['\\', "'", "\n", "\r", "\t", "\0"],
                ['\\\\', "\\'", '\\n', '\\r', '\\t', '\\0'],
                $value
            );
            return "'" . $escaped . "'";
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
        $initialRender = !empty($this->stateVars) ? "\n\n        render();" : '';

        return <<<HTML

        <script>
        {$stateInit}

        {$renderFunc}

        {$textareaSync}

        {$customScript}

        {$actions}{$initialRender}
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
        if (isset($this->stateBindings[$var])) {
            return $this->formatJsValue($this->stateBindings[$var]->initialValue);
        }

        return match ($var) {
            'display' => "'0'",
            'result' => "''",
            'operand1', 'operand2' => '0',
            'operation' => "''",
            'isTyping' => 'false',
            default => "''",
        };
    }

    private function collectBindings(Widget $widget): void
    {
        $ref = new \ReflectionObject($widget);
        foreach ($ref->getProperties() as $prop) {
            if (!$prop->isInitialized($widget)) {
                continue;
            }
            $val = $prop->getValue($widget);
            if ($val instanceof Binding) {
                $this->stateBindings[$val->name] = $val;
                if (!in_array($val->name, $this->stateVars, true)) {
                    $this->stateVars[] = $val->name;
                }
            }
        }

        foreach ($widget->children() as $child) {
            $this->collectBindings($child);
        }
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
            $funcName = $this->generateActionFunction($action, $widget->getActionName());
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
            $funcName = $this->generateActionFunction($action, $widget->getActionName());
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
            $funcName = $this->generateActionFunction($action, $widget->getActionName());
            $onclick = " onclick=\"{$funcName}()\"";
        }

        $style = $this->generateStyle($widget->getStyle());
        return "<label{$onclick}{$style}><input type=\"checkbox\" id=\"{$id}\"> {$label}</label>";
    }

    private function generateCheckbox(Checkbox $widget): string
    {
        $id = 'checkbox_' . $this->nextId();
        $label = htmlspecialchars($widget->label());
        $isChecked = $widget->getIsChecked();
        $onChange = $widget->getOnChange();

        $checkedAttr = '';
        if ($isChecked !== null) {
            $id = $isChecked->name;
            $checkedAttr = " checked=\"checked\"";
        }

        $onclick = '';
        if ($onChange !== null) {
            $funcName = $this->generateActionFunction($onChange);
            $onclick = " onclick=\"{$funcName}()\"";
        }

        $style = $this->generateStyle($widget->getStyle());
        return "<label{$onclick}{$style}><input type=\"checkbox\" id=\"{$id}\"{$checkedAttr}> {$label}</label>";
    }

    private function generateRadioButton(RadioButton $widget): string
    {
        $id = 'radio_' . $this->nextId();
        $label = htmlspecialchars($widget->label());
        $group = htmlspecialchars($widget->group());
        $value = htmlspecialchars($widget->getValue());
        $selectedValue = $widget->getSelectedValue();
        $onChange = $widget->getOnChange();

        $checkedAttr = '';
        if ($selectedValue !== null) {
            $checkedAttr = " data-selected=\"{$selectedValue->name}\"";
        }

        $onclick = '';
        if ($onChange !== null) {
            $funcName = $this->generateActionFunction($onChange);
            $onclick = " onclick=\"{$funcName}()\"";
        }

        $style = $this->generateStyle($widget->getStyle());
        return "<label{$onclick}{$style}><input type=\"radio\" name=\"{$group}\" value=\"{$value}\" id=\"{$id}\"{$checkedAttr}> {$label}</label>";
    }

    private function generateDialog(Dialog $widget): string
    {
        $isOpen = $widget->getIsOpen();
        $displayStyle = 'display:none';
        if ($isOpen !== null) {
            $displayStyle = 'display:none'; // controlled via JS binding
        }

        $this->indent++;
        $children = $this->generateChildren($widget->children());
        $this->indent--;

        return "<div class=\"dialog\" style=\"{$displayStyle}\">\n{$children}\n{$this->indentStr()}</div>";
    }

    private function generateDropdown(Dropdown $widget): string
    {
        $id = 'dropdown_' . $this->nextId();
        $selectedValue = $widget->getSelectedValue();
        $onChange = $widget->getOnChange();
        $style = $this->generateStyle($widget->getStyle());

        $onchange = '';
        if ($onChange !== null) {
            $funcName = $this->generateActionFunction($onChange);
            $onchange = " onchange=\"{$funcName}()\"";
        }

        $options = '';
        foreach ($widget->options() as $label => $value) {
            $escapedLabel = htmlspecialchars((string) $label);
            $escapedValue = htmlspecialchars((string) $value);
            $selected = '';
            if ($selectedValue !== null) {
                $selected = " data-value=\"{$selectedValue->name}\"";
            }
            $options .= "\n{$this->indentStr()}    <option value=\"{$escapedValue}\"{$selected}>{$escapedLabel}</option>";
        }

        return "<select id=\"{$id}\"{$onchange}{$style}>{$options}\n{$this->indentStr()}</select>";
    }

    private function generateProgress(Progress $widget): string
    {
        $progress = $widget->getProgress();
        $id = $progress ? $progress->name : 'progress_' . $this->nextId();
        $value = $progress ? '0' : '0';
        $style = $this->generateStyle($widget->getStyle());

        return "<progress id=\"{$id}\" value=\"{$value}\" max=\"1\"{$style}></progress>";
    }

    private function generateToast(Toast $widget): string
    {
        $message = htmlspecialchars($widget->message());
        $style = $this->generateStyle($widget->getStyle());

        return "<div class=\"toast\"{$style}>{$message}</div>";
    }

    private function generateSegmentedControl(SegmentedControl $widget): string
    {
        $options = $widget->options();
        $selected = $widget->getSelectedValue();
        $selName = $selected ? $selected->name() : '';
        $style = $this->generateStyle($widget->getStyle());
        $indent = $this->indentStr();

        $buttons = '';
        foreach ($options as $label => $value) {
            $escLabel = htmlspecialchars($label);
            $escValue = htmlspecialchars($value);
            $selAttr = ($selected && $selected->value() === $value) ? ' data-selected="1"' : '';
            $onclick = $selected ? " onclick=\"updateBinding('{$selName}','{$escValue}')\"" : '';
            $buttons .= "{$indent}    <button class=\"segmented-option\"{$selAttr} value=\"{$escValue}\"{$onclick}>{$escLabel}</button>\n";
        }

        return "<div class=\"segmented-control\"{$style}>\n{$buttons}{$indent}</div>";
    }

    private function generateContextMenu(ContextMenu $widget): string
    {
        $items = $widget->items();
        $isOpen = $widget->getIsOpen();
        $onSelect = $widget->getOnSelect();
        $isOpenName = $isOpen ? $isOpen->name() : '';
        $style = $this->generateStyle($widget->getStyle());
        $indent = $this->indentStr();
        $displayStyle = $isOpen && $isOpen->value() ? '' : ' style="display:none"';

        $menuItems = '';
        foreach ($items as $label => $value) {
            $escLabel = htmlspecialchars($label);
            $escValue = htmlspecialchars($value);
            $onclick = '';
            if ($onSelect) {
                $onclick = " onclick=\"executeAction('{$onSelect->name()}','{$escValue}')\"";
            } elseif ($isOpen) {
                $onclick = " onclick=\"updateBinding('{$isOpenName}','false')\"";
            }
            $menuItems .= "{$indent}    <button class=\"context-menu-item\" value=\"{$escValue}\"{$onclick}>{$escLabel}</button>\n";
        }

        return "<div class=\"context-menu\"{$displayStyle}{$style}>\n{$menuItems}{$indent}</div>";
    }

    private function generateDatePicker(DatePicker $widget): string
    {
        $date = $widget->getDate();
        $isOpen = $widget->getIsOpen();
        $onChange = $widget->getOnChange();
        $dateName = $date ? $date->name() : '';
        $isOpenName = $isOpen ? $isOpen->name() : '';
        $style = $this->generateStyle($widget->getStyle());
        $displayStyle = $isOpen && $isOpen->value() ? '' : ' style="display:none"';
        $defaultDate = $date ? $date->value() : date('Y-m-d');
        $onchange = '';
        if ($onChange) {
            $onchange = " onchange=\"executeAction('{$onChange->name()}',this.value)\"";
        } elseif ($date) {
            $onchange = " onchange=\"updateBinding('{$dateName}',this.value)\"";
        }

        return "<input type=\"date\" class=\"datepicker\" value=\"{$defaultDate}\"{$onchange}{$displayStyle}{$style} />";
    }

    private function generateAnimatedContainer(\Perry\UI\Widget\AnimatedContainer $widget): string
    {
        $style = $this->generateStyle($widget->getStyle());
        $child = $widget->getChild();
        return $this->generateWidget($child);
    }

    private function generateTransition(\Perry\UI\Widget\Transition $widget): string
    {
        $child = $widget->getChild();
        return $this->generateWidget($child);
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
        $count = $widget->tabsCount();
        $id = 'tabview_' . $this->nextId();
        $selectedBinding = $widget->getSelected();

        // Determine the state variable name for selected tab index
        if ($selectedBinding !== null) {
            $selectedVar = $selectedBinding->name;
            // Ensure it's in stateVars
            if (!in_array($selectedVar, $this->stateVars, true)) {
                $this->stateVars[] = $selectedVar;
                $this->stateBindings[$selectedVar] = $selectedBinding;
            }
        } else {
            // Local state var managed internally
            $selectedVar = '_tabIdx_' . $id;
            if (!in_array($selectedVar, $this->stateVars, true)) {
                $this->stateVars[] = $selectedVar;
            }
            self::$innerHTMLVars[] = $selectedVar;
        }

        $labels = $widget->getLabels();

        // Generate tab bar
        $bar = '<div class="tab-bar" id="' . $id . '_bar">';
        for ($i = 0; $i < $count; $i++) {
            $label = htmlspecialchars($labels[$i]);
            $bar .= "<button class=\"tab-btn\" onclick=\"switchTab_{$id}({$i})\" data-tab=\"{$i}\">{$label}</button>";
        }
        $bar .= '</div>';

        // Generate tab panels
        $panels = '<div class="tab-panels" id="' . $id . '_panels">';
        for ($i = 0; $i < $count; $i++) {
            $content = $widget->content($i);
            $inner = $content ? $this->generateWidget($content) : '';
            $panels .= '<div class="tab-panel" data-panel="' . $i . '">' . $inner . '</div>';
        }
        $panels .= '</div>';

        // Generate the switchTab function
        $funcCode = "function switchTab_{$id}(index) {\n"
            . "    state.{$selectedVar} = index;\n"
            . "    render();\n"
            . "    // Update tab button active states\n"
            . "    var bar = document.getElementById('{$id}_bar');\n"
            . "    if (bar) {\n"
            . "        var btns = bar.querySelectorAll('.tab-btn');\n"
            . "        btns.forEach(function(btn, i) {\n"
            . "            btn.classList.toggle('active', i === index);\n"
            . "        });\n"
            . "    }\n"
            . "    // Update panel visibility\n"
            . "    var panels = document.getElementById('{$id}_panels');\n"
            . "    if (panels) {\n"
            . "        var items = panels.querySelectorAll('.tab-panel');\n"
            . "        items.forEach(function(panel, i) {\n"
            . "            panel.style.display = i === index ? 'block' : 'none';\n"
            . "        });\n"
            . "    }\n"
            . "}\n";

        // Also need an init call to set initial active tab
        $initCode = "(function() {\n"
            . "    switchTab_{$id}(state.{$selectedVar} || 0);\n"
            . "})();\n";

        // Add the functions to the script generation
        if (!in_array($funcCode, $this->actionFunctions, true)) {
            $this->actionFunctions[] = $funcCode;
            // Need init to run after state is ready but only once
            if (!in_array($initCode, $this->actionFunctions, true)) {
                $this->actionFunctions[] = $initCode;
            }
        }

        $style = $this->generateStyle($widget->getStyle());
        return "<div class=\"tab-view\" id=\"{$id}\"{$style}>\n{$bar}\n{$panels}\n</div>";
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
        $css = $this->styleToCssArray($style);
        if (empty($css)) {
            return '';
        }
        $result = ' style="' . implode('; ', $css) . '"';

        // Collect responsive CSS for later emission
        $variants = $style->allVariants();
        if (!empty($variants)) {
            $id = $this->responsiveCounter++;
            $result .= ' data-r="' . $id . '"';
            foreach ($variants as $bp => $variantStyle) {
                $breakpoint = \Perry\UI\Styling\Breakpoint::from($bp);
                $mediaQuery = $breakpoint->toCssMediaQuery();
                $cssProps = $this->styleToCssArray($variantStyle);
                if (!empty($cssProps)) {
                    $props = implode(' !important; ', $cssProps) . ' !important';
                    $this->responsiveStyles[] = "@media ({$mediaQuery}) {\n  [data-r=\"{$id}\"] { " . $props . " }\n}";
                }
            }
        }

        return $result;
    }

    private function styleToCssArray(?\Perry\UI\Styling\Style $style): array
    {
        if ($style === null) {
            return [];
        }

        if ($this->styleCache !== null) {
            $key = StyleCache::keyForCss($style);
            $cached = $this->styleCache->get($key);
            if ($cached !== null) {
                return $cached;
            }
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
        if ($style->has(StyleProperty::LetterSpacing)) {
            $css[] = "letter-spacing: {$style->get(StyleProperty::LetterSpacing)}px";
        }
        if ($style->has(StyleProperty::LineSpacing)) {
            $css[] = "line-height: {$style->get(StyleProperty::LineSpacing)}px";
        }

        // Flex layout
        if ($style->has(StyleProperty::FlexDirection)) {
            $css[] = "flex-direction: {$style->get(StyleProperty::FlexDirection)}";
        }
        if ($style->has(StyleProperty::JustifyContent)) {
            $css[] = "justify-content: {$style->get(StyleProperty::JustifyContent)}";
        }
        if ($style->has(StyleProperty::AlignItems)) {
            $css[] = "align-items: {$style->get(StyleProperty::AlignItems)}";
        }
        if ($style->has(StyleProperty::FlexWrap)) {
            $css[] = "flex-wrap: {$style->get(StyleProperty::FlexWrap)}";
        }
        if ($style->has(StyleProperty::Gap)) {
            $css[] = "gap: {$style->get(StyleProperty::Gap)}px";
        }
        if ($style->has(StyleProperty::FlexGrow)) {
            $css[] = "flex-grow: {$style->get(StyleProperty::FlexGrow)}";
        }
        if ($style->has(StyleProperty::FlexShrink)) {
            $css[] = "flex-shrink: {$style->get(StyleProperty::FlexShrink)}";
        }

        // Transform
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
            $css[] = 'transform: ' . implode(' ', $transforms);
        }

        // Animation
        if ($style->has(StyleProperty::AnimationDuration)) {
            $css[] = "animation-duration: {$style->get(StyleProperty::AnimationDuration)}ms";
        }
        if ($style->has(StyleProperty::AnimationDelay)) {
            $css[] = "animation-delay: {$style->get(StyleProperty::AnimationDelay)}ms";
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
            $css[] = "animation-timing-function: {$cssEasing}";
        }
        if ($style->has(StyleProperty::AnimationIterationCount)) {
            $count = $style->get(StyleProperty::AnimationIterationCount);
            $css[] = "animation-iteration-count: {$count}";
        }
        if ($style->has(StyleProperty::AnimationDirection)) {
            $css[] = "animation-direction: {$style->get(StyleProperty::AnimationDirection)}";
        }
        if ($style->has(StyleProperty::AnimationFillMode)) {
            $css[] = "animation-fill-mode: {$style->get(StyleProperty::AnimationFillMode)}";
        }
        if ($style->has(StyleProperty::AnimationPlayState)) {
            $css[] = "animation-play-state: {$style->get(StyleProperty::AnimationPlayState)}";
        }

        // Transition
        if ($style->has(StyleProperty::TransitionProperty)) {
            $css[] = "transition-property: {$style->get(StyleProperty::TransitionProperty)}";
        }
        if ($style->has(StyleProperty::TransitionDuration)) {
            $css[] = "transition-duration: {$style->get(StyleProperty::TransitionDuration)}ms";
        }
        if ($style->has(StyleProperty::TransitionDelay)) {
            $css[] = "transition-delay: {$style->get(StyleProperty::TransitionDelay)}ms";
        }
        if ($style->has(StyleProperty::TransitionTimingFunction)) {
            $easing = $style->get(StyleProperty::TransitionTimingFunction);
            $cssEasing = match ($easing) {
                'ease-in' => 'ease-in',
                'ease-out' => 'ease-out',
                'ease-in-out' => 'ease-in-out',
                'linear' => 'linear',
                default => $easing,
            };
            $css[] = "transition-timing-function: {$cssEasing}";
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

        if ($this->styleCache !== null) {
            $this->styleCache->set(StyleCache::keyForCss($style), $css);
        }

        return $css;
    }

    private function emitResponsiveStyles(): string
    {
        if (empty($this->responsiveStyles)) {
            return '';
        }
        return '<style>' . "\n" . implode("\n", $this->responsiveStyles) . "\n" . '</style>';
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
            StyleProperty::FlexDirection, StyleProperty::JustifyContent, StyleProperty::AlignItems,
            StyleProperty::FlexWrap, StyleProperty::Gap, StyleProperty::FlexGrow, StyleProperty::FlexShrink,
            // Transform & Animation
            StyleProperty::Rotate, StyleProperty::Scale, StyleProperty::TranslateX, StyleProperty::TranslateY,
            StyleProperty::AnimationDuration, StyleProperty::AnimationDelay, StyleProperty::AnimationEasing,
            StyleProperty::AnimationIterationCount, StyleProperty::AnimationDirection,
            StyleProperty::AnimationFillMode, StyleProperty::AnimationPlayState,
            // Transition
            StyleProperty::TransitionProperty, StyleProperty::TransitionDuration,
            StyleProperty::TransitionDelay, StyleProperty::TransitionTimingFunction,
        ];
    }
}
