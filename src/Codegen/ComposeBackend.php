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
use Perry\UI\Widget\AnimatedContainer;
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
use Perry\UI\Widget\Transition;
use Perry\UI\Widget\VStack;
use Perry\UI\Widget\WebView;
use Perry\UI\WidgetKind;

final class ComposeBackend extends CodegenBackend
{
    private int $indent = 0;
    private array $stateVars = [];
    /** @var array<string, Binding> */
    private array $stateBindings = [];
    private string $packageName = 'com.perry.app';

    public function setPackageName(string $packageName): void
    {
        $this->packageName = $packageName;
    }

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
        $this->stateVars = [];
        $this->stateBindings = [];

        if ($root instanceof AppContainer) {
            $bindings = $root->bindings();
            $this->stateVars = array_map(fn(Binding $b) => $b->name, $bindings);
            foreach ($bindings as $b) {
                $this->stateBindings[$b->name] = $b;
            }
            return $this->generateAppWithState($root);
        }

        $this->collectBindings($root);
        return $this->generateSimpleApp($root);
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

        // Handle TextInput StateId binding
        if ($widget instanceof TextInput) {
            $id = $widget->value();
            $varName = !empty($id->name) ? $id->name : $id->id;
            if (!in_array($varName, $this->stateVars, true)) {
                $this->stateVars[] = $varName;
            }
        }

        foreach ($widget->children() as $child) {
            $this->collectBindings($child);
        }
    }

    private function generateAppWithState(AppContainer $app): string
    {
        $bindings = $app->bindings();
        $stateVars = $this->generateStateVars($bindings);
        $body = $this->generateWidget($app->content());

        $width = $app->windowWidth();
        $height = $app->windowHeight();

        if ($width !== null && $height !== null) {
            return <<<KOTLIN
        package {$this->packageName}

        import android.os.Bundle
        import androidx.activity.ComponentActivity
        import androidx.activity.compose.setContent
        import androidx.compose.foundation.layout.*
        import androidx.compose.material3.*
        import androidx.compose.runtime.*
        import androidx.compose.ui.Alignment
        import androidx.compose.ui.Modifier
        import androidx.compose.ui.draw.clip
        import androidx.compose.foundation.background
        import androidx.compose.foundation.shape.RoundedCornerShape
        import androidx.compose.ui.graphics.graphicsLayer
        import androidx.compose.ui.graphics.TransformOrigin
        import androidx.compose.ui.graphics.Color
        import androidx.compose.ui.text.font.FontFamily
        import androidx.compose.ui.text.font.FontWeight
        import androidx.compose.ui.text.style.TextAlign
        import androidx.compose.ui.text.style.TextDecoration
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

            BoxWithConstraints(
                modifier = Modifier.fillMaxSize(),
                contentAlignment = Alignment.Center
            ) {
                val scale = minOf(
                    maxWidth / {$width}.dp,
                    maxHeight / {$height}.dp
                )

                Surface(
                    modifier = Modifier
                        .size({$width}.dp, {$height}.dp)
                        .graphicsLayer(
                            scaleX = scale,
                            scaleY = scale,
                            transformOrigin = TransformOrigin(0.5f, 0.5f)
                        ),
                    color = MaterialTheme.colorScheme.background
                ) {
        {$body}
                }
            }
        }
        KOTLIN;
        }

        return <<<KOTLIN
        package {$this->packageName}

        import android.os.Bundle
        import androidx.activity.ComponentActivity
        import androidx.activity.compose.setContent
        import androidx.compose.foundation.layout.*
        import androidx.compose.material3.*
        import androidx.compose.runtime.*
        import androidx.compose.ui.Modifier
        import androidx.compose.ui.draw.clip
        import androidx.compose.foundation.background
        import androidx.compose.foundation.shape.RoundedCornerShape
        import androidx.compose.ui.graphics.Color
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
                modifier = Modifier.fillMaxSize(),
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
        package {$this->packageName}

        import android.os.Bundle
        import androidx.activity.ComponentActivity
        import androidx.activity.compose.setContent
        import androidx.compose.foundation.layout.*
        import androidx.compose.material3.*
        import androidx.compose.runtime.*
        import androidx.compose.ui.Modifier
        import androidx.compose.ui.draw.clip
        import androidx.compose.foundation.background
        import androidx.compose.foundation.shape.RoundedCornerShape
        import androidx.compose.ui.graphics.Color
        import androidx.compose.ui.text.font.FontFamily
        import androidx.compose.ui.text.font.FontWeight
        import androidx.compose.ui.text.style.TextAlign
        import androidx.compose.ui.text.style.TextDecoration
        import androidx.compose.ui.unit.dp
        import androidx.compose.ui.unit.sp

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
        $boundNames = [];
        foreach ($bindings as $binding) {
            $initial = $this->formatValue($binding->initialValue);
            $vars[] = "var {$binding->name} by remember { mutableStateOf({$initial}) }";
            $boundNames[] = $binding->name;
        }
        // Add state vars from TextInput StateId (not in bindings)
        foreach ($this->stateVars as $name) {
            if (!in_array($name, $boundNames, true)) {
                $vars[] = "var {$name} by remember { mutableStateOf(\"\") }";
            }
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
            WidgetKind::Checkbox => $this->generateCheckbox($widget),
            WidgetKind::RadioButton => $this->generateRadioButton($widget),
            WidgetKind::Dialog => $this->generateDialogWidget($widget),
            WidgetKind::Dropdown => $this->generateDropdownWidget($widget),
            WidgetKind::Progress => $this->generateProgressWidget($widget),
            WidgetKind::Toast => $this->generateToastWidget($widget),
            WidgetKind::SegmentedControl => $this->generateSegmentedControl($widget),
            WidgetKind::ContextMenu => $this->generateContextMenuWidget($widget),
            WidgetKind::DatePicker => $this->generateDatePickerWidget($widget),
            WidgetKind::AnimatedContainer => $this->generateAnimatedContainer($widget),
            WidgetKind::Transition => $this->generateTransition($widget),
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

        $textArgs = $this->generateTextStyleArgs($widget->getStyle());
        $mods = $this->generateModifiers($widget->getStyle());
        return "Text(text = {$content}{$textArgs}{$mods})";
    }

    private function generateButton(Button $widget): string
    {
        $label = addslashes($widget->label());
        $action = $this->generateAction($widget->getAction());
        $style = $widget->getStyle();
        $textArgs = $this->generateTextStyleArgs($style);
        $props = \Perry\UI\Styling\StyleProperty::class;

        // Button parameters: colors + shape + contentPadding instead of Modifier.background + .clip
        // (Material3 Button has internal Surface with containerColor from theme primary,
        //  and default contentPadding ~16-24dp horizontal — clips small buttons)
        $buttonColors = '';
        $colorsArgs = [];
        if ($style && $style->has($props::BackgroundColor)) {
            $colorsArgs[] = "containerColor = {$this->colorExpr($style->get($props::BackgroundColor))}";
        }
        if ($style && $style->has($props::ForegroundColor)) {
            $colorsArgs[] = "contentColor = {$this->colorExpr($style->get($props::ForegroundColor))}";
        }
        if ($colorsArgs) {
            $buttonColors = ", colors = ButtonDefaults.buttonColors(" . implode(', ', $colorsArgs) . ")";
        }
        $buttonShape = '';
        if ($style && $style->has($props::CornerRadius)) {
            $buttonShape = ", shape = RoundedCornerShape({$style->get($props::CornerRadius)}.dp)";
        }

        // Modifier chain — exclude .background() and .clip() (handled via colors/shape params)
        $parts = $this->getModifierParts($style);
        $filtered = array_values(array_filter($parts, fn(string $m) =>
            !str_starts_with($m, '.background(') && !str_starts_with($m, '.clip(')
        ));
        $modifier = $filtered ? ', modifier = Modifier' . implode('', $filtered) : '';

        return "Button(onClick = {{$action}}{$modifier}{$buttonColors}{$buttonShape}, contentPadding = PaddingValues(0.dp)) {\n{$this->indentStr()}    Text(\"{$label}\"{$textArgs})\n{$this->indentStr()}}";
    }

    /**
     * Generate text-style arguments for a Text widget (not Modifier).
     * These are passed as direct parameters: Text(text = ..., color = ..., fontSize = ..., ...)
     */
    private function generateTextStyleArgs(?\Perry\UI\Styling\Style $style): string
    {
        if ($style === null) {
            return '';
        }
        $args = [];
        $props = \Perry\UI\Styling\StyleProperty::class;

        if ($style->has($props::ForegroundColor)) {
            $args[] = 'color = ' . $this->colorExpr($style->get($props::ForegroundColor));
        }
        if ($style->has($props::FontSize)) {
            $args[] = 'fontSize = ' . $style->get($props::FontSize) . '.sp';
        }
        if ($style->has($props::FontWeight)) {
            $v = $style->get($props::FontWeight);
            $map = ['bold' => 'FontWeight.Bold', 'semibold' => 'FontWeight.SemiBold', 'medium' => 'FontWeight.Medium', 'normal' => 'FontWeight.Normal', 'light' => 'FontWeight.Light'];
            $args[] = 'fontWeight = ' . ($map[$v] ?? 'FontWeight.Normal');
        }
        if ($style->has($props::FontFamily)) {
            $args[] = 'fontFamily = FontFamily.' . ucfirst($style->get($props::FontFamily));
        }
        if ($style->has($props::TextAlignment)) {
            $v = $style->get($props::TextAlignment);
            $map = ['left' => 'TextAlign.Left', 'center' => 'TextAlign.Center', 'right' => 'TextAlign.Right'];
            $args[] = 'textAlign = ' . ($map[$v] ?? 'TextAlign.Left');
        }
        if ($style->has($props::TextDecoration)) {
            $v = $style->get($props::TextDecoration);
            $map = ['underline' => 'TextDecoration.Underline', 'line-through' => 'TextDecoration.LineThrough'];
            $args[] = 'textDecoration = ' . ($map[$v] ?? 'TextDecoration.None');
        }
        if ($style->has($props::LetterSpacing)) {
            $args[] = 'letterSpacing = ' . $style->get($props::LetterSpacing) . '.sp';
        }
        if ($style->has($props::LineSpacing)) {
            $args[] = 'lineHeight = ' . $style->get($props::LineSpacing) . '.sp';
        }

        return $args ? ', ' . implode(', ', $args) : '';
    }

    private function generateAction(?Action $action): string
    {
        if ($action === null) {
            return '';
        }

        if ($action->type === ActionType::Closure) {
            $generator = new \Perry\Generator\KotlinGenerator($this->stateVars);
            $code = $action->generate($generator);
            // Wrap in run {} so bare 'return' works (Kotlin lambda forbids unlabeled return)
            return " run {\n{$code}\n} ";
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
        $mods = $this->generateModifiers($widget->getStyle(), 'Modifier.fillMaxWidth()');
        return "Column(verticalArrangement = Arrangement.spacedBy({$spacing}.dp){$mods}) {\n{$children}\n{$this->indentStr()}}";
    }

    private function generateHStack(HStack $widget): string
    {
        $this->indent++;
        $children = $this->generateChildren($widget->children());
        $this->indent--;
        $spacing = $this->getSpacing($widget->getStyle());
        $mods = $this->generateModifiers($widget->getStyle(), 'Modifier.fillMaxWidth()');
        return "Row(horizontalArrangement = Arrangement.spacedBy({$spacing}.dp){$mods}) {\n{$children}\n{$this->indentStr()}}";
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
        $binding = $widget->value();
        $varName = $binding->name;
        return "TextField(value = {$varName}, onValueChange = {{$varName} = it}, placeholder = { Text(\"{$placeholder}\") })";
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

    private function generateCheckbox(Checkbox $widget): string
    {
        $label = addslashes($widget->label());
        $binding = $widget->getIsChecked();
        $checked = $binding ? $binding->name : 'false';
        $action = $widget->getOnChange();
        $onChange = $action ? $this->generateAction($action) : '';
        $mods = $this->generateModifiers($widget->getStyle());
        return "Row(verticalAlignment = Alignment.CenterVertically{$mods}) {\n{$this->indentStr()}    Checkbox(checked = {$checked}, onCheckedChange = {{$onChange}})\n{$this->indentStr()}    Text(\"{$label}\")\n{$this->indentStr()}}";
    }

    private function generateRadioButton(RadioButton $widget): string
    {
        $label = addslashes($widget->label());
        $value = addslashes($widget->getValue());
        $binding = $widget->getSelectedValue();
        $selected = $binding ? $binding->name : '""';
        $action = $widget->getOnChange();
        $onChange = $action ? $this->generateAction($action) : "{$selected} = \"{$value}\"";
        $mods = $this->generateModifiers($widget->getStyle());
        return "Row(verticalAlignment = Alignment.CenterVertically{$mods}) {\n{$this->indentStr()}    RadioButton(selected = {$selected} == \"{$value}\", onClick = {{$onChange}})\n{$this->indentStr()}    Text(\"{$label}\")\n{$this->indentStr()}}";
    }

    private function generateDialogWidget(Dialog $widget): string
    {
        $binding = $widget->getIsOpen();
        $isOpen = $binding ? $binding->name : 'false';
        $this->indent++;
        $children = $this->generateChildren($widget->children());
        $this->indent--;
        $mods = $this->generateModifiers($widget->getStyle());
        return "if ({$isOpen}) {\n{$this->indentStr()}    Dialog(onDismissRequest = { {$isOpen} = false }) {\n{$this->indentStr()}        Column{$mods} {\n{$children}\n{$this->indentStr()}        }\n{$this->indentStr()}    }\n{$this->indentStr()}}";
    }

    private function generateDropdownWidget(Dropdown $widget): string
    {
        $binding = $widget->getSelectedValue();
        $selected = $binding ? $binding->name : '""';
        $action = $widget->getOnChange();
        $expandedVar = 'expanded_' . $selected;
        $items = [];
        foreach ($widget->options() as $label => $value) {
            $escLabel = addslashes((string) $label);
            $escValue = addslashes((string) $value);
            $onClick = $action ? str_replace('{$value}', "\"{$escValue}\"", $this->generateAction($action)) : "{$selected} = \"{$escValue}\"";
            $items[] = "{$this->indentStr()}        DropdownMenuItem(text = { Text(\"{$escLabel}\") }, onClick = { {$onClick}; {$expandedVar} = false })";
        }
        $itemsStr = implode(",\n", $items);
        $mods = $this->generateModifiers($widget->getStyle());
        return "var {$expandedVar} by remember { mutableStateOf(false) }\n{$this->indentStr()}ExposedDropdownMenuBox(expanded = {$expandedVar}, onExpandedChange = {{ {$expandedVar} = it }}{$mods}) {\n{$this->indentStr()}    TextField(value = {$selected}, onValueChange = {{}}, readOnly = true, modifier = Modifier.menuAnchor())\n{$this->indentStr()}    ExposedDropdownMenu(expanded = {$expandedVar}, onDismissRequest = {{ {$expandedVar} = false }}) {\n{$itemsStr}\n{$this->indentStr()}    }\n{$this->indentStr()}}";
    }

    private function generateProgressWidget(Progress $widget): string
    {
        $binding = $widget->getProgress();
        $progress = $binding ? $binding->name : '0.5';
        $mods = $this->generateModifiers($widget->getStyle());
        return "LinearProgressIndicator(progress = { {$progress}.toFloat() }{$mods})";
    }

    private function generateToastWidget(Toast $widget): string
    {
        $message = addslashes($widget->message());
        $mods = $this->generateModifiers($widget->getStyle());
        return "Text(text = \"{$message}\"{$mods})";
    }

    private function generateSegmentedControl(SegmentedControl $widget): string
    {
        $binding = $widget->getSelectedValue();
        $selected = $binding ? $binding->name : '0';
        $action = $widget->getOnChange();
        $mods = $this->generateModifiers($widget->getStyle());
        $items = [];
        foreach ($widget->options() as $label => $value) {
            $escLabel = addslashes((string) $label);
            $items[] = "{$this->indentStr()}            DropdownMenuItem(text = { Text(\"{$escLabel}\") }, onClick = {{ {$selected} = \"{$value}\" }})";
        }
        $itemsStr = implode(",\n", $items);

        return "var expanded_{$selected} by remember { mutableStateOf(false) }\n{$this->indentStr()}ExposedDropdownMenuBox(expanded = expanded_{$selected}, onExpandedChange = {{ expanded_{$selected} = it }}{$mods}) {\n{$this->indentStr()}    OutlinedTextField(value = {$selected}, onValueChange = {{}}, readOnly = true, modifier = Modifier.menuAnchor())\n{$this->indentStr()}    ExposedDropdownMenu(expanded = expanded_{$selected}, onDismissRequest = {{ expanded_{$selected} = false }}) {\n{$itemsStr}\n{$this->indentStr()}    }\n{$this->indentStr()}}";
    }

    private function generateContextMenuWidget(ContextMenu $widget): string
    {
        $isOpen = $widget->getIsOpen();
        $openVar = $isOpen ? $isOpen->name : 'false';
        $mods = $this->generateModifiers($widget->getStyle());
        $items = [];
        foreach ($widget->items() as $label => $value) {
            $escLabel = addslashes((string) $label);
            $items[] = "{$this->indentStr()}        DropdownMenuItem(text = { Text(\"{$escLabel}\") }, onClick = {{ {$openVar} = false }})";
        }
        $itemsStr = implode(",\n", $items);

        return "DropdownMenu(expanded = {$openVar}, onDismissRequest = {{ {$openVar} = false }}{$mods}) {\n{$itemsStr}\n{$this->indentStr()}}";
    }

    private function generateDatePickerWidget(DatePicker $widget): string
    {
        $date = $widget->getDate();
        $isOpen = $widget->getIsOpen();
        $dateVar = $date ? $date->name : 'dateState';
        $openVar = $isOpen ? $isOpen->name : 'true';
        $mods = $this->generateModifiers($widget->getStyle());

        return "var {$dateVar} by remember { mutableStateOf(\"\") }\n{$this->indentStr()}if ({$openVar}) {\n{$this->indentStr()}    DatePicker(state = rememberDatePickerState()){$mods}\n{$this->indentStr()}}";
    }

    private function generateAnimatedContainer(AnimatedContainer $widget): string
    {
        $child = $widget->getChild();
        return $this->generateWidget($child);
    }

    private function generateTransition(Transition $widget): string
    {
        $child = $widget->getChild();
        return $this->generateWidget($child);
    }

    private function generateModifiers(?\Perry\UI\Styling\Style $style): string
    {
        $parts = $this->getModifierParts($style);
        if (empty($parts)) {
            return '';
        }
        return ', modifier = Modifier' . implode('', $parts);
    }

    private function getModifierParts(?\Perry\UI\Styling\Style $style): array
    {
        if ($style === null) {
            return [];
        }
        $parts = [];
        $props = \Perry\UI\Styling\StyleProperty::class;

        if ($style->has($props::Padding)) {
            $parts[] = '.padding(' . $style->get($props::Padding) . '.dp)';
        }
        if ($style->has($props::Width) && $style->has($props::Height)) {
            $parts[] = '.size(' . $style->get($props::Width) . '.dp, ' . $style->get($props::Height) . '.dp)';
        } elseif ($style->has($props::Width)) {
            $parts[] = '.width(' . $style->get($props::Width) . '.dp)';
        } elseif ($style->has($props::Height)) {
            $parts[] = '.height(' . $style->get($props::Height) . '.dp)';
        }
        if ($style->has($props::BackgroundColor)) {
            $hex = $style->get($props::BackgroundColor);
            $parts[] = '.background(' . $this->colorExpr($hex) . ')';
        }
        if ($style->has($props::CornerRadius)) {
            $parts[] = '.clip(RoundedCornerShape(' . $style->get($props::CornerRadius) . '.dp))';
        }
        if ($style->has($props::Opacity)) {
            $parts[] = '.alpha(' . $style->get($props::Opacity) . 'f)';
        }
        if ($style->has($props::Margin)) {
            $parts[] = '.padding(' . $style->get($props::Margin) . '.dp)';
        }
        if ($style->has($props::FlexGrow)) {
            $parts[] = '.weight(' . $style->get($props::FlexGrow) . 'f)';
        }
        if ($style->has($props::Rotate)) {
            $parts[] = '.rotate(' . $style->get($props::Rotate) . 'f)';
        }
if ($style->has($props::Scale)) {
             $s = $style->get($props::Scale);
             $parts[] = ".graphicsLayer { scaleX: {$s}f, scaleY: {$s}f }";
         }
        if ($style->has($props::TranslateX) || $style->has($props::TranslateY)) {
            $x = $style->has($props::TranslateX) ? $style->get($props::TranslateX) : 0;
            $y = $style->has($props::TranslateY) ? $style->get($props::TranslateY) : 0;
            $parts[] = ".offset(x: {$x}.dp, y: {$y}.dp)";
        }

        // Transition
        if ($style->has($props::TransitionDuration)) {
            $duration = $style->get($props::TransitionDuration);
            $delay = $style->has($props::TransitionDelay) ? $style->get($props::TransitionDelay) : 0;
            $easing = $style->has($props::TransitionTimingFunction) ? $style->get($props::TransitionTimingFunction) : 'ease';
            $curve = match ($easing) {
                'ease-in' => 'EaseIn',
                'ease-out' => 'EaseOut',
                'ease-in-out' => 'EaseInOutCubic',
                'linear' => 'Linear',
                default => 'EaseInOut',
            };
            $parts[] = ".animateContentSize(animation = {$curve}Easing.duration({$duration}))";
        }

        return $parts;
    }

    private function indentStr(): string
    {
        return str_repeat('    ', $this->indent ?? 0);
    }

    private function colorExpr(string $hex): string
    {
        $hex = ltrim($hex, '#');
        if (strlen($hex) === 3) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }
        if (strlen($hex) === 6) {
            $hex = 'FF' . $hex;
        }
        return 'Color(0x' . $hex . ')';
    }

    private function generateChildren(array $children): string
    {
        $parts = [];
        foreach ($children as $child) {
            $code = $this->generateWidget($child);
            $parts[] = $this->indentStr() . $code;
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
            StyleProperty::BackgroundColor, StyleProperty::ForegroundColor, StyleProperty::BorderColor,
            StyleProperty::Width, StyleProperty::Height, StyleProperty::MinWidth, StyleProperty::MinHeight,
            StyleProperty::MaxWidth, StyleProperty::MaxHeight, StyleProperty::BorderWidth,
            StyleProperty::CornerRadius, StyleProperty::Margin, StyleProperty::Padding,
            StyleProperty::PaddingTop, StyleProperty::PaddingBottom, StyleProperty::PaddingLeading,
            StyleProperty::PaddingTrailing, StyleProperty::Opacity, StyleProperty::ShadowRadius,
            StyleProperty::ShadowColor, StyleProperty::ShadowOffsetX, StyleProperty::ShadowOffsetY,
            StyleProperty::FontSize, StyleProperty::FontWeight, StyleProperty::FontFamily,
            StyleProperty::TextAlignment, StyleProperty::TextDecoration, StyleProperty::LineSpacing,
            StyleProperty::FlexDirection, StyleProperty::JustifyContent, StyleProperty::AlignItems,
            StyleProperty::FlexWrap, StyleProperty::Gap, StyleProperty::FlexGrow, StyleProperty::FlexShrink,
            // Transform & Animation
            StyleProperty::Rotate, StyleProperty::Scale, StyleProperty::TranslateX, StyleProperty::TranslateY,
            StyleProperty::AnimationDuration,
            // Transition
            StyleProperty::TransitionProperty, StyleProperty::TransitionDuration, StyleProperty::TransitionDelay,
            StyleProperty::TransitionTimingFunction,
        ];
    }
}
