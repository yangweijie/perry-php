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
use Perry\UI\Widget\AnimatedContainer;
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

/**
 * Flutter (Dart) backend.
 * Generates Dart code using Flutter's Material Design widget library
 * for cross-platform mobile and desktop apps.
 */
final class FlutterBackend extends CodegenBackend
{
    private int $indent = 0;
    private array $currentBindings = [];

    public function name(): string
    {
        return 'flutter';
    }

    public function supports(Target $target): bool
    {
        return $target === Target::Flutter;
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

    private function generateSimpleApp(Widget $root): string
    {
        $body = $this->generateWidget($root);

        return <<<DART
        import 'package:flutter/material.dart';

        void main() => runApp(const PerryApp());

        class PerryApp extends StatelessWidget {
          const PerryApp({super.key});

          @override
          Widget build(BuildContext context) {
            return MaterialApp(
              debugShowCheckedModeBanner: false,
              home: Scaffold(
                body: Center(
                  child: {$body},
                ),
              ),
            );
          }
        }

        DART;
    }

    private function generateAppWithState(AppContainer $app): string
    {
        $bindings = $app->bindings();
        $this->currentBindings = array_map(fn(Binding $b) => $b->name, $bindings);
        $stateVars = $this->generateStateVars($bindings);
        $body = $this->generateWidget($app->content());

        $width = $app->windowWidth();
        $height = $app->windowHeight();
        $sizeCode = '';
        if ($width !== null && $height !== null) {
            $sizeCode = ", width: {$width}, height: {$height}";
        }

        return <<<DART
        import 'package:flutter/material.dart';

        void main() => runApp(PerryApp());

        class PerryApp extends StatefulWidget {
          @override
          _PerryAppState createState() => _PerryAppState();
        }

        class _PerryAppState extends State<PerryApp> {
        {$stateVars}

          @override
          Widget build(BuildContext context) {
            return MaterialApp(
              debugShowCheckedModeBanner: false,
              home: Scaffold(
                body: Center(
                  child: SizedBox({$sizeCode}
                    child: {$body},
                  ),
                ),
              ),
            );
          }
        }

        DART;
    }

    private function generateStateVars(array $bindings): string
    {
        $vars = [];
        foreach ($bindings as $binding) {
            $initial = $this->formatValue($binding->initialValue);
            $type = $this->dartType($binding->initialValue);
            $vars[] = "  {$type} {$binding->name} = {$initial};";
        }
        return implode("\n", $vars);
    }

    private function dartType(mixed $value): string
    {
        if (is_int($value)) return 'int';
        if (is_float($value)) return 'double';
        if (is_bool($value)) return 'bool';
        return 'String';
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
            return (string) $value;
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
        WidgetKind::AnimatedContainer => $this->generateAnimatedContainer($widget),
        WidgetKind::Transition => $this->generateTransition($widget),
            default => 'const SizedBox.shrink()',
        };
    }

    private function generateText(Text $widget): string
    {
        $binding = $widget->getBinding();
        if ($binding) {
            $content = "\${$binding->name}";
        } else {
            $content = addslashes($widget->content());
        }

        $style = $this->generateTextStyle($widget->getStyle());
        $textAlign = $this->getTextAlign($widget->getStyle());
        $overflow = $this->getTextOverflow($widget->getStyle());

        $parts = ["'{$content}'"];
        if ($style !== '') {
            $parts[] = "style: {$style}";
        }
        if ($textAlign !== '') {
            $parts[] = "textAlign: {$textAlign}";
        }
        if ($overflow !== '') {
            $parts[] = "overflow: {$overflow}";
        }

        $args = implode(",\n{$this->indentStr()}    ", $parts);
        $wrappers = $this->generateStyleWrappers($widget->getStyle());
        return $wrappers . "Text(\n{$this->indentStr()}    {$args},\n{$this->indentStr()  })";
    }

    private function generateButton(Button $widget): string
    {
        $label = addslashes($widget->label());
        $action = $this->generateAction($widget->getAction());
        $wrappers = $this->generateStyleWrappers($widget->getStyle());
        return $wrappers . "ElevatedButton(\n{$this->indentStr()}    onPressed: () {\n{$this->indentStr()}      {$action}\n{$this->indentStr()}    },\n{$this->indentStr()}    child: const Text('{$label}'),\n{$this->indentStr()  })";
    }

    private function generateVStack(VStack $widget): string
    {
        $children = $this->generateChildren($widget->children());
        $crossAlign = $this->getCrossAxisAlignment($widget->getStyle());
        $wrappers = $this->generateStyleWrappers($widget->getStyle());
        return $wrappers . "Column(\n{$this->indentStr()}    {$crossAlign}children: [\n{$children}\n{$this->indentStr()}    ],\n{$this->indentStr()  })";
    }

    private function generateHStack(HStack $widget): string
    {
        $children = $this->generateChildren($widget->children());
        $wrappers = $this->generateStyleWrappers($widget->getStyle());
        return $wrappers . "Row(\n{$this->indentStr()}    children: [\n{$children}\n{$this->indentStr()}    ],\n{$this->indentStr()  })";
    }

    private function generateImage(Image $widget): string
    {
        $source = addslashes($widget->source());
        $wrappers = $this->generateStyleWrappers($widget->getStyle());
        return $wrappers . "Image.asset(\n{$this->indentStr()}    '{$source}',\n{$this->indentStr()  })";
    }

    private function generateScrollView(ScrollView $widget): string
    {
        $this->indent++;
        $children = $this->generateChildren($widget->children());
        $this->indent--;
        $wrappers = $this->generateStyleWrappers($widget->getStyle());
        return $wrappers . "SingleChildScrollView(\n{$this->indentStr()}    child: Column(\n{$this->indentStr()}      children: [\n{$children}\n{$this->indentStr()}      ],\n{$this->indentStr()}    ),\n{$this->indentStr()  })";
    }

    private function generateTextInput(TextInput $widget): string
    {
        $placeholder = addslashes($widget->placeholder());
        $binding = $widget->value();
        $name = $binding->name;
        $style = $this->generateTextStyle($widget->getStyle());
        $styleArg = $style !== '' ? ",\n{$this->indentStr()}    style: {$style}" : '';

        $action = $widget->getOnChange();
        $onChange = '';
        if ($action !== null) {
            $actionBody = $this->generateAction($action);
            $onChange = ",\n{$this->indentStr()}    onChanged: (v) {\n{$this->indentStr()}      {$actionBody}\n{$this->indentStr()}    }";
        }

        $wrappers = $this->generateStyleWrappers($widget->getStyle());
        return $wrappers . "TextField(\n{$this->indentStr()}    decoration: InputDecoration(\n{$this->indentStr()}      labelText: '{$placeholder}',\n{$this->indentStr()}    ),{$styleArg}{$onChange}\n{$this->indentStr()  })";
    }

    private function generateTextEditorWidget(TextEditor $widget): string
    {
        $binding = $widget->getBinding();
        $name = $binding->name;
        $wrappers = $this->generateStyleWrappers($widget->getStyle());
        return $wrappers . "TextField(\n{$this->indentStr()}    maxLines: null,\n{$this->indentStr()}    decoration: const InputDecoration(\n{$this->indentStr()}      isDense: true,\n{$this->indentStr()}    ),\n{$this->indentStr()}    onChanged: (v) {\n{$this->indentStr()}      setState(() => {$name} = v);\n{$this->indentStr()}    },\n{$this->indentStr()}    value: {$name},\n{$this->indentStr()  })";
    }

    private function generateToggle(Toggle $widget): string
    {
        $label = addslashes($widget->label());
        $isOn = $widget->getIsOn()?->name ?? 'false';
        $onToggle = $widget->getOnToggle();
        $actionBody = '';
        if ($onToggle !== null) {
            $actionBody = "\n{$this->indentStr()}    {$this->generateAction($onToggle)}";
        }

        $wrappers = $this->generateStyleWrappers($widget->getStyle());
        $switchPart = $wrappers . "Row(\n{$this->indentStr()}    children: [\n{$this->indentStr()}      Text('{$label}'),\n{$this->indentStr()}      Switch(\n{$this->indentStr()}        value: {$isOn},\n{$this->indentStr()}        onChanged: (v) {\n{$this->indentStr()}          setState(() => {$isOn} = v);{$actionBody}\n{$this->indentStr()}        },\n{$this->indentStr()}      ),\n{$this->indentStr()}    ],\n{$this->indentStr()}  )";

        return $switchPart;
    }

    private function generateSlider(Slider $widget): string
    {
        $binding = $widget->value();
        $name = $binding->name;
        $min = $widget->min();
        $max = $widget->max();
        $wrappers = $this->generateStyleWrappers($widget->getStyle());
        return $wrappers . "Slider(\n{$this->indentStr()}    value: {$name},\n{$this->indentStr()}    min: {$min},\n{$this->indentStr()}    max: {$max},\n{$this->indentStr()}    onChanged: (v) {\n{$this->indentStr()}      setState(() => {$name} = v);\n{$this->indentStr()}    },\n{$this->indentStr()  })";
    }

    private function generateListWidget(ListWidget $widget): string
    {
        $this->indent++;
        $children = $this->generateChildren($widget->items());
        $this->indent--;
        $wrappers = $this->generateStyleWrappers($widget->getStyle());
        return $wrappers . "ListView(\n{$this->indentStr()}    children: [\n{$children}\n{$this->indentStr()}    ],\n{$this->indentStr()  })";
    }

    private function generateNavigationView(NavigationView $widget): string
    {
        $this->indent++;
        $children = $this->generateChildren($widget->screens());
        $this->indent--;
        $wrappers = $this->generateStyleWrappers($widget->getStyle());
        return $wrappers . "Column(\n{$this->indentStr()}    children: [\n{$this->indentStr()}      const AppBar(title: Text('Navigation')),\n{$children}\n{$this->indentStr()}    ],\n{$this->indentStr()  })";
    }

    private function generateTabView(TabView $widget): string
    {
        $this->indent++;
        $children = $this->generateChildren($widget->tabs());
        $this->indent--;
        $wrappers = $this->generateStyleWrappers($widget->getStyle());
        return $wrappers . "DefaultTabController(\n{$this->indentStr()}    length: " . count($widget->tabs()) . ",\n{$this->indentStr()}    child: Column(\n{$this->indentStr()}      children: [\n{$this->indentStr()}        const TabBar(\n{$this->indentStr()}          tabs: [\n{$this->indentStr()}            Tab(text: 'Tab'),\n{$this->indentStr()}          ],\n{$this->indentStr()}        ),\n{$this->indentStr()}        Expanded(\n{$this->indentStr()}          child: TabBarView(\n{$this->indentStr()}            children: [\n{$children}\n{$this->indentStr()}            ],\n{$this->indentStr()}          ),\n{$this->indentStr()}        ),\n{$this->indentStr()}      ],\n{$this->indentStr()}    ),\n{$this->indentStr()  })";
    }

    private function generateWebViewWidget(WebView $widget): string
    {
        $wrappers = $this->generateStyleWrappers($widget->getStyle());
        return $wrappers . "const Text('WebView')";
    }

    private function generateCheckbox(Checkbox $widget): string
    {
        $label = addslashes($widget->label());
        $isChecked = $widget->getIsChecked();
        $name = $isChecked?->name ?? 'false';
        $onChange = $widget->getOnChange();
        $actionBody = '';
        if ($onChange !== null) {
            $actionBody = "\n{$this->indentStr()}      {$this->generateAction($onChange)}";
        }
        $wrappers = $this->generateStyleWrappers($widget->getStyle());
        return $wrappers . "Row(\n{$this->indentStr()}    children: [\n{$this->indentStr()}      Text('{$label}'),\n{$this->indentStr()}      Checkbox(\n{$this->indentStr()}        value: {$name},\n{$this->indentStr()}        onChanged: (v) {\n{$this->indentStr()}          setState(() => {$name} = v!);{$actionBody}\n{$this->indentStr()}        },\n{$this->indentStr()}      ),\n{$this->indentStr()}    ],\n{$this->indentStr()}  )";
    }

    private function generateRadioButton(RadioButton $widget): string
    {
        $label = addslashes($widget->label());
        $group = addslashes($widget->group());
        $val = (string) $widget->getValue();
        $selected = $widget->getSelectedValue();
        $groupValue = $selected?->name ?? 'null';
        $onChange = $widget->getOnChange();
        $actionBody = '';
        if ($onChange !== null) {
            $actionBody = "\n{$this->indentStr()}      {$this->generateAction($onChange)}";
        }
        $wrappers = $this->generateStyleWrappers($widget->getStyle());
        return $wrappers . "Row(\n{$this->indentStr()}    children: [\n{$this->indentStr()}      Radio<String>(\n{$this->indentStr()}        value: '{$val}',\n{$this->indentStr()}        groupValue: {$groupValue},\n{$this->indentStr()}        onChanged: (v) {\n{$this->indentStr()}          setState(() => {$groupValue} = v);{$actionBody}\n{$this->indentStr()}        },\n{$this->indentStr()}      ),\n{$this->indentStr()}      Text('{$label}'),\n{$this->indentStr()}    ],\n{$this->indentStr()}  )";
    }

    private function generateDialog(Dialog $widget): string
    {
        $this->indent++;
        $children = $this->generateChildren($widget->children());
        $this->indent--;
        $wrappers = $this->generateStyleWrappers($widget->getStyle());
        return $wrappers . "AlertDialog(\n{$this->indentStr()}    content: SingleChildScrollView(\n{$this->indentStr()}      child: Column(\n{$this->indentStr()}        children: [\n{$children}\n{$this->indentStr()}        ],\n{$this->indentStr()}      ),\n{$this->indentStr()}    ),\n{$this->indentStr()  })";
    }

    private function generateDropdown(Dropdown $widget): string
    {
        $selectedBinding = $widget->getSelectedValue();
        $name = $selectedBinding?->name ?? 'null';
        $options = $widget->options();
        $items = [];
        foreach ($options as $key => $label) {
            $key = addslashes((string) $key);
            $label = addslashes((string) $label);
            $items[] = "{$this->indentStr()}      DropdownMenuItem<String>(\n{$this->indentStr()}        value: '{$key}',\n{$this->indentStr()}        child: Text('{$label}'),\n{$this->indentStr()}      )";
        }
        $itemsCode = implode(",\n", $items);
        $onChange = $widget->getOnChange();
        $actionBody = '';
        if ($onChange !== null) {
            $actionBody = "\n{$this->indentStr()}      {$this->generateAction($onChange)}";
        }
        $wrappers = $this->generateStyleWrappers($widget->getStyle());
        return $wrappers . "DropdownButton<String>(\n{$this->indentStr()}    value: {$name},\n{$this->indentStr()}    items: [\n{$itemsCode},\n{$this->indentStr()}    ],\n{$this->indentStr()}    onChanged: (v) {\n{$this->indentStr()}      setState(() => {$name} = v);{$actionBody}\n{$this->indentStr()}    },\n{$this->indentStr()}  )";
    }

    private function generateProgress(Progress $widget): string
    {
        $progressBinding = $widget->getProgress();
        $progressExpr = $progressBinding !== null ? $progressBinding->name : 'null';
        $wrappers = $this->generateStyleWrappers($widget->getStyle());
        if ($progressExpr !== 'null') {
            return $wrappers . "LinearProgressIndicator(\n{$this->indentStr()}    value: {$progressExpr},\n{$this->indentStr()  })";
        }
        return $wrappers . "const LinearProgressIndicator()";
    }

    private function generateToast(Toast $widget): string
    {
        $message = addslashes($widget->message());
        $wrappers = $this->generateStyleWrappers($widget->getStyle());
        return $wrappers . "Text(\n{$this->indentStr()}    '{$message}',\n{$this->indentStr()}    style: TextStyle(\n{$this->indentStr()}      color: Colors.white,\n{$this->indentStr()}      fontWeight: FontWeight.w500,\n{$this->indentStr()}    ),\n{$this->indentStr()}  )";
    }

    private function generateAction(?Action $action): string
    {
        if ($action === null) {
            return '';
        }

        if ($action->type === ActionType::Closure) {
            $generator = new \Perry\Generator\DartGenerator($this->currentBindings);
            return $action->generate($generator);
        }

        return match ($action->type) {
            ActionType::SetValue => "setState(() => {$action->target->name} = {$this->formatValue($action->value)});",
            ActionType::Append => "setState(() => {$action->target->name} += '{$action->value}');",
            ActionType::Clear => "setState(() => {$action->target->name} = {$this->formatValue($action->target->initialValue)});",
            ActionType::Custom => $action->customCode ?? '',
            default => '',
        };
    }

    private function generateChildren(array $children): string
    {
        $parts = [];
        foreach ($children as $child) {
            $parts[] = $this->indentStr() . '      ' . $this->generateWidget($child) . ',';
        }
        return implode("\n", $parts);
    }

    /**
     * Generate style-wrapping widgets (Container, Padding, etc.)
     * Returns the wrapping widget prefix and its closing parenthesis.
     */
    private function generateStyleWrappers(?Style $style): string
    {
        if ($style === null) {
            return '';
        }

        $props = StyleProperty::class;
        $wraps = [];

        if ($style->has($props::Width) || $style->has($props::Height) || $style->has($props::MinHeight)) {
            $w = $style->has($props::Width) ? $style->get($props::Width) : 'null';
            $h = $style->has($props::Height) ? $style->get($props::Height) : 'null';
            if ($style->has($props::MinHeight)) {
                $wraps[] = "SizedBox(\n{$this->indentStr()}    height: {$style->get($props::MinHeight)},";
            } else {
                $wraps[] = "SizedBox(\n{$this->indentStr()}    width: {$w}, height: {$h},";
            }
        }

        if ($style->has($props::MinWidth) || $style->has($props::MaxWidth) || $style->has($props::MaxHeight)) {
            $parts = [];
            if ($style->has($props::MinWidth)) {
                $parts[] = "minWidth: {$style->get($props::MinWidth)}";
            }
            if ($style->has($props::MaxWidth)) {
                $parts[] = "maxWidth: {$style->get($props::MaxWidth)}";
            }
            if ($style->has($props::MaxHeight)) {
                $parts[] = "maxHeight: {$style->get($props::MaxHeight)}";
            }
            $wraps[] = "ConstrainedBox(\n{$this->indentStr()}    constraints: BoxConstraints(\n{$this->indentStr()}      " . implode(",\n{$this->indentStr()}      ", $parts) . ",\n{$this->indentStr()}    ),";
        }

        if ($style->has($props::Padding)) {
            $p = $style->get($props::Padding);
            $wraps[] = "Padding(\n{$this->indentStr()}    padding: EdgeInsets.all({$p}),";
        }
        if ($style->has($props::PaddingTop)) {
            $wraps[] = "Padding(\n{$this->indentStr()}    padding: EdgeInsets.only(top: {$style->get($props::PaddingTop)}),";
        }
        if ($style->has($props::PaddingBottom)) {
            $wraps[] = "Padding(\n{$this->indentStr()}    padding: EdgeInsets.only(bottom: {$style->get($props::PaddingBottom)}),";
        }

        if ($style->has($props::BackgroundColor)) {
            $wraps[] = "Container(\n{$this->indentStr()}    color: {$this->colorExpr($style->get($props::BackgroundColor))},";
        }

        if ($style->has($props::Opacity)) {
            $wraps[] = "Opacity(\n{$this->indentStr()}    opacity: {$style->get($props::Opacity)},";
        }

        if ($style->has($props::CornerRadius)) {
            $radius = $style->get($props::CornerRadius);
            $color = $style->has($props::BackgroundColor) ? $this->colorExpr($style->get($props::BackgroundColor)) : 'Colors.transparent';
            $wraps[] = "ClipRRect(\n{$this->indentStr()}    borderRadius: BorderRadius.circular({$radius}),";
        }

        if ($style->has($props::BorderWidth) || $style->has($props::BorderColor)) {
            $bw = $style->has($props::BorderWidth) ? $style->get($props::BorderWidth) : 1;
            $bc = $style->has($props::BorderColor) ? $this->colorExpr($style->get($props::BorderColor)) : 'Colors.grey';
            $wraps[] = "Container(\n{$this->indentStr()}    decoration: BoxDecoration(\n{$this->indentStr()}      border: Border.all(color: {$bc}, width: {$bw}),\n{$this->indentStr()}    ),";
        }

        if ($style->has($props::Margin)) {
            $m = $style->get($props::Margin);
            $wraps[] = "Padding(\n{$this->indentStr()}    padding: EdgeInsets.symmetric(horizontal: {$m}),";
        }

        if ($style->has($props::ShadowColor) || $style->has($props::ShadowRadius)) {
            $wraps[] = "Material(\n{$this->indentStr()}    elevation: {$this->getShadowElevation($style)},";
        }

        if ($style->has($props::FlexGrow)) {
            $wraps[] = "Expanded(\n{$this->indentStr()}    flex: {$style->get($props::FlexGrow)},";
        }

        if ($style->has($props::FlexShrink)) {
            $wraps[] = "Flexible(\n{$this->indentStr()}    fit: FlexFit.loose,";
        }

        // Transform
        if ($style->has($props::Rotate)) {
            $angle = $style->get($props::Rotate) * M_PI / 180;
            $angleStr = number_format($angle, 4);
            $wraps[] = "Transform.rotate(\n{$this->indentStr()}    angle: {$angleStr},";
        }
        if ($style->has($props::Scale)) {
            $v = $style->get($props::Scale);
            $wraps[] = "Transform.scale(\n{$this->indentStr()}    scale: {$v},";
        }
        if ($style->has($props::TranslateX) || $style->has($props::TranslateY)) {
            $tx = $style->has($props::TranslateX) ? $style->get($props::TranslateX) : 0;
            $ty = $style->has($props::TranslateY) ? $style->get($props::TranslateY) : 0;
            $wraps[] = "Transform.translate(\n{$this->indentStr()}    offset: Offset({$tx}, {$ty}),";
        }

        if (!empty($wraps)) {
            return implode("\n{$this->indentStr()}    child: ", $wraps) . "\n{$this->indentStr()}    child: ";
        }

        return '';
    }

    private function generateTextStyle(?Style $style): string
    {
        if ($style === null) {
            return '';
        }

        $props = StyleProperty::class;
        $parts = [];

        if ($style->has($props::FontSize)) {
            $parts[] = "fontSize: {$style->get($props::FontSize)}";
        }
        if ($style->has($props::ForegroundColor)) {
            $parts[] = "color: {$this->colorExpr($style->get($props::ForegroundColor))}";
        }
        if ($style->has($props::FontWeight)) {
            $parts[] = "fontWeight: {$this->mapFontWeight($style->get($props::FontWeight))}";
        }
        if ($style->has($props::FontFamily)) {
            $parts[] = "fontFamily: '{$style->get($props::FontFamily)}'";
        }
        if ($style->has($props::LetterSpacing)) {
            $parts[] = "letterSpacing: {$style->get($props::LetterSpacing)}";
        }
        if ($style->has($props::LineSpacing)) {
            $parts[] = "height: {$style->get($props::LineSpacing)}";
        }
        if ($style->has($props::TextDecoration)) {
            $parts[] = "decoration: {$this->mapTextDecoration($style->get($props::TextDecoration))}";
        }

        if (empty($parts)) {
            return '';
        }

        return 'TextStyle(' . implode(', ', $parts) . ')';
    }

    private function getTextAlign(?Style $style): string
    {
        if ($style === null || !$style->has(StyleProperty::TextAlignment)) {
            return '';
        }

        return match ($style->get(StyleProperty::TextAlignment)) {
            'left' => 'TextAlign.left',
            'right' => 'TextAlign.right',
            'center' => 'TextAlign.center',
            'justify' => 'TextAlign.justify',
            default => '',
        };
    }

    private function getTextOverflow(?Style $style): string
    {
        if ($style === null) {
            return '';
        }
        // Flutter uses maxLines + overflow for truncation, no direct "overflow" property yet
        return '';
    }

    private function getCrossAxisAlignment(?Style $style): string
    {
        if ($style === null || !$style->has(StyleProperty::TextAlignment)) {
            return '';
        }

        $align = $style->get(StyleProperty::TextAlignment);
        return match ($align) {
            'left' => "crossAxisAlignment: CrossAxisAlignment.start,\n{$this->indentStr()}    ",
            'right' => "crossAxisAlignment: CrossAxisAlignment.end,\n{$this->indentStr()}    ",
            'center' => "crossAxisAlignment: CrossAxisAlignment.center,\n{$this->indentStr()}    ",
            default => '',
        };
    }

    private function getShadowElevation(?Style $style): int
    {
        if ($style === null) {
            return 0;
        }
        $props = StyleProperty::class;
        if ($style->has($props::ShadowRadius)) {
            return (int) $style->get($props::ShadowRadius);
        }
        return 2;
    }

    private function colorExpr(string $hex): string
    {
        $hex = ltrim($hex, '#');
        if (strlen($hex) === 3) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }
        if (strlen($hex) !== 6) {
            return 'Colors.white';
        }
        return 'Color(0xFF' . strtoupper($hex) . ')';
    }

    private function mapFontWeight(string $weight): string
    {
        $map = [
            'bold' => 'FontWeight.bold',
            'semibold' => 'FontWeight.w600',
            'medium' => 'FontWeight.w500',
            'light' => 'FontWeight.w300',
            'regular' => 'FontWeight.w400',
            700 => 'FontWeight.bold',
            600 => 'FontWeight.w600',
            500 => 'FontWeight.w500',
            300 => 'FontWeight.w300',
        ];
        return $map[$weight] ?? 'FontWeight.w400';
    }

    private function mapTextDecoration(string $decoration): string
    {
        return match ($decoration) {
            'underline' => 'TextDecoration.underline',
            'lineThrough' => 'TextDecoration.lineThrough',
            default => 'TextDecoration.none',
        };
    }

    private function indentStr(): string
    {
        return str_repeat('  ', $this->indent);
    }

    private function generateSegmentedControl(SegmentedControl $widget): string
    {
        $binding = $widget->getSelectedValue();
        $selected = $binding ? $binding->name : 'null';
        $onChange = $widget->getOnChange();
        $actionBody = '';
        if ($onChange !== null) {
            $actionBody = "{$this->generateAction($onChange)}";
        }
        $items = [];
        foreach ($widget->options() as $label => $value) {
            $escLabel = addslashes((string) $label);
            $items[] = "{$this->indentStr()}      ButtonSegment<String>(\n{$this->indentStr()}        value: '{$escLabel}',\n{$this->indentStr()}        label: Text('{$escLabel}'),\n{$this->indentStr()}      )";
        }
        $itemsCode = implode(",\n", $items);
        $wrappers = $this->generateStyleWrappers($widget->getStyle());
        return $wrappers . "SegmentedButton<String>(\n{$this->indentStr()}    segments: [\n{$itemsCode},\n{$this->indentStr()}    ],\n{$this->indentStr()}    selected: { {$selected} },\n{$this->indentStr()}    onSelectionChanged: (v) {\n{$this->indentStr()}      {$actionBody}\n{$this->indentStr()}    },\n{$this->indentStr()  })";
    }

    private function generateContextMenuWidget(ContextMenu $widget): string
    {
        $items = [];
        foreach ($widget->items() as $label => $value) {
            $escLabel = addslashes((string) $label);
            $items[] = "{$this->indentStr()}    PopupMenuItem<String>(\n{$this->indentStr()}      value: '{$escLabel}',\n{$this->indentStr()}      child: Text('{$escLabel}'),\n{$this->indentStr()}    )";
        }
        $itemsCode = implode(",\n", $items);
        $wrappers = $this->generateStyleWrappers($widget->getStyle());
        return $wrappers . "PopupMenuButton<String>(\n{$this->indentStr()}    itemBuilder: (context) => [\n{$itemsCode},\n{$this->indentStr()}    ],\n{$this->indentStr()  })";
    }

    private function generateDatePickerWidget(DatePicker $widget): string
    {
        $date = $widget->getDate();
        $dateVar = $date ? $date->name : 'null';
        $wrappers = $this->generateStyleWrappers($widget->getStyle());
        return $wrappers . "TextButton(\n{$this->indentStr()}    onPressed: () async {\n{$this->indentStr()}      final picked = await showDatePicker(\n{$this->indentStr()}        context: context,\n{$this->indentStr()}        initialDate: DateTime.now(),\n{$this->indentStr()}        firstDate: DateTime(2000),\n{$this->indentStr()}        lastDate: DateTime(2100),\n{$this->indentStr()}      );\n{$this->indentStr()}      if (picked != null) { {$dateVar} = picked.toString(); }\n{$this->indentStr()}    },\n{$this->indentStr()}    child: const Text('{$dateVar}'),\n{$this->indentStr()  })";
    }

    /** @return StyleProperty[] */
    public function supportedStyleProperties(): array
    {
        return [
            StyleProperty::FontSize, StyleProperty::ForegroundColor, StyleProperty::FontWeight,
            StyleProperty::FontFamily, StyleProperty::LetterSpacing, StyleProperty::LineSpacing,
            StyleProperty::TextAlignment, StyleProperty::TextDecoration, StyleProperty::Width,
            StyleProperty::Height, StyleProperty::MinWidth, StyleProperty::MinHeight,
            StyleProperty::MaxWidth, StyleProperty::MaxHeight, StyleProperty::Padding,
            StyleProperty::PaddingTop, StyleProperty::PaddingBottom, StyleProperty::BackgroundColor,
            StyleProperty::Opacity, StyleProperty::CornerRadius, StyleProperty::BorderWidth,
            StyleProperty::BorderColor, StyleProperty::Margin, StyleProperty::ShadowColor,
            StyleProperty::ShadowRadius,
            StyleProperty::FlexGrow, StyleProperty::FlexShrink,
            StyleProperty::Rotate, StyleProperty::Scale, StyleProperty::TranslateX, StyleProperty::TranslateY,
            // Transition
            StyleProperty::TransitionProperty, StyleProperty::TransitionDuration, StyleProperty::TransitionDelay,
            StyleProperty::TransitionTimingFunction,
        ];
    }

    private function generateAnimatedContainer(AnimatedContainer $widget): string
    {
        $child = $widget->getChild();
        return $this->generateWidget($child);
    }

    private function generateTransition(\Perry\UI\Widget\Transition $widget): string
    {
        $child = $widget->getChild();
        return $this->generateWidget($child);
    }
}
