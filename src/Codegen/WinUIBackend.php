<?php

declare(strict_types=1);

namespace Perry\Codegen;

use Perry\Build\Target;
use Perry\UI\Styling\Style;
use Perry\UI\Styling\StyleProperty;
use Perry\UI\Widget;
use Perry\UI\Widget\Button;
use Perry\UI\Widget\HStack;
use Perry\UI\Widget\Image;
use Perry\UI\Widget\ScrollView;
use Perry\UI\Widget\Spacer;
use Perry\UI\Widget\Text;
use Perry\UI\Widget\TextEditor;
use Perry\UI\Widget\TextInput;
use Perry\UI\Widget\Toggle;
use Perry\UI\Widget\AppContainer;
use Perry\UI\Widget\ListWidget;
use Perry\UI\Widget\NavigationView;
use Perry\UI\Widget\Slider;
use Perry\UI\Widget\TabView;
use Perry\UI\Widget\VStack;
use Perry\UI\Widget\WebView;
use Perry\UI\WidgetKind;

final class WinUIBackend extends CodegenBackend
{
    private int $indent = 0;

    /** @var array<array{id: string, method: string, action: \Perry\UI\Action, eventType?: string, bindingName?: string}> */
    private array $buttonActions = [];

    /** @var array<string, mixed> */
    private array $stateVars = [];
    
    /** @var string|null */
    private ?string $windowWidth = null;
    
    /** @var string|null */
    private ?string $windowHeight = null;
    
    /** @var array<string, string> 绑定变量名到 TextBlock 名称的映射 */
    private array $textBindings = [];

    public function name(): string
    {
        return 'winui';
    }

    public function supports(Target $target): bool
    {
        return $target === Target::Windows;
    }

    public function generate(Widget $root): string
    {
        $this->indent = 0;
        $this->buttonActions = [];
        $this->stateVars = [];
        $this->textBindings = [];
        
        // Extract state variables and window size from AppContainer
        if ($root instanceof \Perry\UI\Widget\AppContainer) {
            foreach ($root->bindings() as $binding) {
                $this->stateVars[$binding->name] = $binding->initialValue;
            }
            $this->windowWidth = $root->windowWidth() !== null ? (string) $root->windowWidth() : '800';
            $this->windowHeight = $root->windowHeight() !== null ? (string) $root->windowHeight() : '600';
        }
        
        $body = $this->generateWidget($root);
        $indentedBody = implode("\n", array_map(
            fn($line) => '            ' . $line,
            explode("\n", $body)
        ));

        return trim(<<<XAML
<Window x:Class="PerryApp.MainWindow"
        xmlns="http://schemas.microsoft.com/winfx/2006/xaml/presentation"
        xmlns:x="http://schemas.microsoft.com/winfx/2006/xaml"
        Title="Perry App"
        Width="{$this->windowWidth}"
        Height="{$this->windowHeight}"
        Background="White"
        WindowStartupLocation="CenterScreen">
{$indentedBody}
</Window>
XAML);
    }

    private function generateFields(): string
    {
        $fields = '';
        foreach ($this->stateVars as $name => $defaultValue) {
            $type = $this->inferCSharpType($defaultValue);
            $value = $this->formatCSharpValue($defaultValue, $type);
            $fields .= "        public {$type} {$name} = {$value};\n";
        }
        return $fields ? $fields . "\n" : '';
    }

    private function inferCSharpType(mixed $value): string
    {
        if (is_string($value)) return 'string';
        if (is_float($value)) return 'double';
        if (is_int($value)) return 'int';
        if (is_bool($value)) return 'bool';
        return 'object';
    }

    private function formatCSharpValue(mixed $value, string $type): string
    {
        if (is_string($value)) return '"' . addslashes($value) . '"';
        if (is_bool($value)) return $value ? 'true' : 'false';
        return (string)$value;
    }

    public function generateMainActivity(string $outputName): string
    {
        $methods = '';
        $hasSliderEvent = false;
        $hasTextChanged = false;
        foreach ($this->buttonActions as $item) {
            $action = $item['action'];
            $methodName = $item['method'];
            
            $eventArgsType = 'RoutedEventArgs';
            $prependCode = '';
            if (isset($item['eventType'])) {
                if ($item['eventType'] === 'ValueChanged') {
                    $hasSliderEvent = true;
                    $eventArgsType = 'RoutedPropertyChangedEventArgs<double>';
                    $prependCode = "            {$item['bindingName']} = e.NewValue;\n";
                } elseif ($item['eventType'] === 'TextChanged') {
                    $hasTextChanged = true;
                    $eventArgsType = 'TextChangedEventArgs';
                    $prependCode = "            {$item['bindingName']} = ((TextBox)sender).Text;\n";
                } elseif ($item['eventType'] === 'CheckChanged') {
                    $prependCode = "            {$item['bindingName']} = ((CheckBox)sender).IsChecked ?? false;\n";
                }
            }
            
            $body = $this->generateActionBody($action);
            $methods .= <<<CS

        private void {$methodName}(object sender, {$eventArgsType} e) {
{$prependCode}{$body}
            UpdateUI();
        }
CS;
        }

        $fields = $this->generateFields();
        
        $updateUICode = '';
        foreach ($this->textBindings as $bindingName => $textBlockName) {
            $updateUICode .= "            if ({$textBlockName} != null) {$textBlockName}.Text = {$bindingName}.ToString() ?? \"\";\n";
        }

        $usings = "using System;\n";
        if ($hasSliderEvent) {
            $usings .= "using System.Windows.Controls.Primitives;\n";
        }
        $usings .= "using System.Windows;\nusing System.Windows.Controls;\n";

        return <<<CS
{$usings}
namespace PerryApp
{
    public partial class MainWindow : Window
    {
{$fields}
        public MainWindow() {
            InitializeComponent();
            UpdateUI();
        }
{$methods}

        private void UpdateUI()
        {
{$updateUICode}
        }
    }
}
CS;
    }

    private function generateActionBody(\Perry\UI\Action $action): string
    {
        if ($action->type === \Perry\UI\ActionType::Custom) {
            return '            // Custom action: ' . $action->customCode;
        }

        if ($action->type === \Perry\UI\ActionType::Closure) {
            $generator = new \Perry\Generator\CSharpGenerator(array_keys($this->stateVars));
            $code = $action->generate($generator);
            return $this->indentCs($code, 3);
        }

        if ($action->type === \Perry\UI\ActionType::SetValue) {
            $targetVar = $action->target->name;
            $value = $this->formatActionValue($action->value);
            return "            {$targetVar} = {$value};";
        }

        return '            // Action type not yet fully supported for WinUI: ' . $action->type->value;
    }

    private function formatActionValue(mixed $value): string
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
        if (is_null($value)) {
            return 'null';
        }
        return (string) $value;
    }

    private function indentCs(string $code, int $level): string
    {
        $lines = explode("\n", $code);
        $indent = str_repeat('    ', $level);
        return implode("\n", array_map(fn($line) => $indent . $line, $lines));
    }

    private function generateWidget(Widget $widget): string
    {
        if ($widget instanceof AppContainer) {
            $content = $this->generateWidget($widget->content());
            $containerStyle = $widget->getStyle();
            if ($containerStyle !== null) {
                $props = $this->generateProperties($containerStyle);
                $content = trim(<<<XAML
        {$this->indentStr()}<Border{$props}>
{$content}
        {$this->indentStr()}</Border>
XAML);
            }
            return $content;
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
        $props = $this->generateProperties($widget->getStyle());
        
        $nameAttr = '';
        $text = '';
        $binding = $widget->getBinding();
        if ($binding !== null) {
            $bindingName = $binding->name;
            $textBlockName = 'textBlock_' . $bindingName;
            $this->textBindings[$bindingName] = $textBlockName;
            $nameAttr = " x:Name=\"{$textBlockName}\"";
            // Use binding's initial value as the initial XAML display text
            $initial = $binding->initialValue;
            if ($initial !== null) {
                $text = htmlspecialchars((string) $initial);
            }
        } else {
            $text = htmlspecialchars($widget->content());
        }
        
        return trim(<<<XAML
        {$this->indentStr()}<TextBlock Text="{$text}"{$nameAttr}{$props} />
XAML);
    }

    private function generateButton(Button $widget): string
    {
        $label = htmlspecialchars($widget->label());
        $style = $widget->getStyle();

        // WPF Button 不支持 CornerRadius 属性（仅 Border 支持），需将其分离出来
        $cornerRadius = null;
        if ($style !== null && $style->has(StyleProperty::CornerRadius)) {
            $cornerRadius = $style->get(StyleProperty::CornerRadius);
        }

        // 生成按钮属性时排除 CornerRadius，后续通过 ControlTemplate 处理
        $buttonProps = $this->generateProperties($style, ['cornerRadius']);

        $action = $widget->getAction();
        $clickAttr = '';
        if ($action !== null) {
            $safeId = $this->safeMethodName($widget->label());
            $methodName = 'On' . $safeId . 'Click';
            $this->buttonActions[] = ['id' => $widget->label(), 'method' => $methodName, 'action' => $action];
            $clickAttr = " Click=\"{$methodName}\"";
        }

        if ($cornerRadius !== null) {
            return trim(<<<XAML
        {$this->indentStr()}<Button{$buttonProps}{$clickAttr}>
            <Button.Template>
                <ControlTemplate TargetType="Button">
                    <Border CornerRadius="{$cornerRadius}" Background="{TemplateBinding Background}" BorderBrush="{TemplateBinding BorderBrush}" BorderThickness="{TemplateBinding BorderThickness}">
                        <ContentPresenter HorizontalAlignment="Center" VerticalAlignment="Center" />
                    </Border>
                </ControlTemplate>
            </Button.Template>
            <TextBlock Text="{$label}" />
        </Button>
XAML);
        }

        return trim(<<<XAML
        {$this->indentStr()}<Button Content="{$label}"{$buttonProps}{$clickAttr} />
XAML);
    }

    private function safeMethodName(string $label): string
    {
        $map = [
            '⌫' => 'Backspace',
            'C' => 'Clear',
            '%' => 'Percent',
            '÷' => 'Divide',
            '×' => 'Multiply',
            '-' => 'Minus',
            '+' => 'Plus',
            '+/-' => 'Negate',
            '.' => 'Dot',
            '=' => 'Equals',
        ];
        return $map[$label] ?? ucfirst(preg_replace('/[^a-zA-Z0-9]/', '', $label));
    }

    private const PADDING_PROPS = [
        'padding', 'padding_top', 'padding_bottom', 'padding_leading', 'padding_trailing',
    ];

    private function generateVStack(VStack $widget): string
    {
        $children = $widget->children();
        if ($this->hasSpacerChild($children)) {
            return $this->generateVStackGrid($widget, $children);
        }

        $style = $widget->getStyle();
        $hasPadding = $style !== null && $this->styleHasPadding($style);

        $this->indent++;
        $childXaml = $this->generateChildren($children);
        $this->indent--;

        $excludeStackProps = array_merge(['cornerRadius'], $hasPadding ? self::PADDING_PROPS : []);
        $stackProps = $this->generateProperties($style, $excludeStackProps);

        $stackPanel = trim(<<<XAML
        {$this->indentStr()}<StackPanel Orientation="Vertical"{$stackProps}>
        {$childXaml}
        {$this->indentStr()}</StackPanel>
XAML);

        if ($hasPadding) {
            $borderProps = $this->generateProperties($style, [], self::PADDING_PROPS);
            return trim(<<<XAML
        {$this->indentStr()}<Border{$borderProps}>
{$stackPanel}
        {$this->indentStr()}</Border>
XAML);
        }

        return $stackPanel;
    }

    private function generateHStack(HStack $widget): string
    {
        $children = $widget->children();
        if ($this->hasSpacerChild($children)) {
            return $this->generateHStackGrid($widget, $children);
        }

        $style = $widget->getStyle();
        $hasPadding = $style !== null && $this->styleHasPadding($style);

        $this->indent++;
        $childXaml = $this->generateChildren($children);
        $this->indent--;

        $excludeStackProps = array_merge(['cornerRadius'], $hasPadding ? self::PADDING_PROPS : []);
        $stackProps = $this->generateProperties($style, $excludeStackProps);

        $stackPanel = trim(<<<XAML
        {$this->indentStr()}<StackPanel Orientation="Horizontal"{$stackProps}>
        {$childXaml}
        {$this->indentStr()}</StackPanel>
XAML);

        if ($hasPadding) {
            $borderProps = $this->generateProperties($style, [], self::PADDING_PROPS);
            return trim(<<<XAML
        {$this->indentStr()}<Border{$borderProps}>
{$stackPanel}
        {$this->indentStr()}</Border>
XAML);
        }

        return $stackPanel;
    }

    private function styleHasPadding(Style $style): bool
    {
        return $style->has(StyleProperty::Padding)
            || $style->has(StyleProperty::PaddingTop)
            || $style->has(StyleProperty::PaddingBottom)
            || $style->has(StyleProperty::PaddingLeading)
            || $style->has(StyleProperty::PaddingTrailing);
    }

    /**
     * @param array<int, Widget> $children
     */
    private function hasSpacerChild(array $children): bool
    {
        foreach ($children as $child) {
            if ($child instanceof Spacer) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param array<int, Widget> $children
     */
    private function generateHStackGrid(HStack $widget, array $children): string
    {
        $this->indent++;
        $colDefs = '';
        $childParts = [];
        foreach ($children as $i => $child) {
            $colDefs .= "                <ColumnDefinition Width=\"" . ($child instanceof Spacer ? '*' : 'Auto') . "\" />\n";
            if ($child instanceof Spacer) {
                $childParts[] = $this->indentStr() . "<Rectangle Grid.Column=\"{$i}\" Fill=\"Transparent\" />";
            } else {
                $xaml = $this->generateWidget($child);
                $xaml = $this->addGridPosition($xaml, $i, 'Column');
                $childParts[] = $xaml;
            }
        }
        $this->indent--;
        $colDefs = rtrim($colDefs);
        $childrenXaml = implode("\n", $childParts);

        return <<<XAML
        {$this->indentStr()}<Grid>
        {$this->indentStr()}    <Grid.ColumnDefinitions>
{$colDefs}
        {$this->indentStr()}    </Grid.ColumnDefinitions>
        {$childrenXaml}
        {$this->indentStr()}</Grid>
XAML;
    }

    /**
     * @param array<int, Widget> $children
     */
    private function generateVStackGrid(VStack $widget, array $children): string
    {
        $this->indent++;
        $rowDefs = '';
        $childParts = [];
        foreach ($children as $i => $child) {
            $rowDefs .= "                <RowDefinition Height=\"" . ($child instanceof Spacer ? '*' : 'Auto') . "\" />\n";
            if ($child instanceof Spacer) {
                $childParts[] = $this->indentStr() . "<Rectangle Grid.Row=\"{$i}\" Fill=\"Transparent\" />";
            } else {
                $xaml = $this->generateWidget($child);
                $xaml = $this->addGridPosition($xaml, $i, 'Row');
                $childParts[] = $xaml;
            }
        }
        $this->indent--;
        $rowDefs = rtrim($rowDefs);
        $childrenXaml = implode("\n", $childParts);

        return <<<XAML
        {$this->indentStr()}<Grid>
        {$this->indentStr()}    <Grid.RowDefinitions>
{$rowDefs}
        {$this->indentStr()}    </Grid.RowDefinitions>
        {$childrenXaml}
        {$this->indentStr()}</Grid>
XAML;
    }

    private function addGridPosition(string $xaml, int $index, string $axis): string
    {
        return preg_replace(
            '/^(<[a-zA-Z]+(\s[^>]*?)?)(\s*\/?>)/',
            '$1 Grid.' . $axis . '="' . $index . '"$3',
            $xaml
        );
    }

    private function generateSpacer(Spacer $widget): string
    {
        return trim(<<<XAML
        {$this->indentStr()}<Rectangle Fill="Transparent" />
XAML);
    }

    private function generateImage(Image $widget): string
    {
        $src = htmlspecialchars($widget->source());
        return trim(<<<XAML
        {$this->indentStr()}<Image Source="{$src}" />
XAML);
    }

    private function generateScrollView(ScrollView $widget): string
    {
        $this->indent++;
        $children = $this->generateChildren($widget->children());
        $this->indent--;

        $props = $this->generateProperties($widget->getStyle());
        $scrollAttrs = ' VerticalScrollBarVisibility="Auto"';
        return trim(<<<XAML
        {$this->indentStr()}<ScrollViewer{$props}{$scrollAttrs}>
        {$children}
        {$this->indentStr()}</ScrollViewer>
XAML);
    }

    private function generateSlider(Slider $widget): string
    {
        $binding = $widget->value();
        $name = $binding->name;
        $min = $widget->min();
        $max = $widget->max();
        $step = $widget->step();
        $props = $this->generateProperties($widget->getStyle());

        $onChange = '';
        $action = $widget->getOnChange();
        if ($action !== null) {
            $methodName = 'On' . ucfirst($name) . 'Change';
            $this->buttonActions[] = [
                'id' => $name,
                'method' => $methodName,
                'action' => $action,
                'eventType' => 'ValueChanged',
                'bindingName' => $name,
            ];
            $onChange = " ValueChanged=\"{$methodName}\"";
        }

        return trim(<<<XAML
        {$this->indentStr()}<Slider
            x:Name="slider_{$name}"
            Minimum="{$min}"
            Maximum="{$max}"
            TickFrequency="{$step}"{$onChange}{$props} />
XAML);
    }

    private function generateTextInput(TextInput $widget): string
    {
        $placeholder = htmlspecialchars($widget->placeholder());
        $binding = $widget->value();
        $name = $binding->name;
        $props = $this->generateProperties($widget->getStyle());

        $onChange = '';
        $action = $widget->getOnChange();
        if ($action !== null) {
            $methodName = 'On' . ucfirst($name) . 'Change';
            $this->buttonActions[] = [
                'id' => $name,
                'method' => $methodName,
                'action' => $action,
                'eventType' => 'TextChanged',
                'bindingName' => $name,
            ];
            $onChange = " TextChanged=\"{$methodName}\"";
        }

        return trim(<<<XAML
        {$this->indentStr()}<TextBox
            x:Name="textbox_{$name}"
            PlaceholderText="{$placeholder}"{$onChange}{$props} />
XAML);
    }

    private function generateToggle(Toggle $widget): string
    {
        $label = htmlspecialchars($widget->label());
        $props = $this->generateProperties($widget->getStyle());

        $onToggle = '';
        $action = $widget->getOnToggle();
        $isOn = $widget->getIsOn();
        if ($action !== null) {
            $safeId = $this->safeMethodName($widget->label());
            $methodName = 'On' . $safeId . 'Toggle';
            $bindingName = $isOn !== null ? $isOn->name : '';
            $this->buttonActions[] = [
                'id' => $widget->label(),
                'method' => $methodName,
                'action' => $action,
                'eventType' => 'CheckChanged',
                'bindingName' => $bindingName,
            ];
            $onToggle = " Click=\"{$methodName}\"";
        }

        return trim(<<<XAML
        {$this->indentStr()}<CheckBox Content="{$label}"{$onToggle}{$props} />
XAML);
    }

    private function generateTextEditorWidget(TextEditor $widget): string
    {
        $binding = $widget->getBinding();
        $name = $binding->name;
        $this->textBindings[$name] = 'textblock_' . $name;
        return trim(<<<XAML
        {$this->indentStr()}<TextBox
            x:Name="textblock_{$name}"
            AcceptsReturn="True"
            TextWrapping="Wrap"
            VerticalScrollBarVisibility="Auto" />
XAML);
    }

    private function generateWebViewWidget(WebView $widget): string
    {
        return trim(<<<XAML
        {$this->indentStr()}<WebView2 Source="about:blank" />
XAML);
    }

    private function generateListWidget(\Perry\UI\Widget\ListWidget $widget): string
    {
        $this->indent++;
        $items = $this->generateChildren($widget->items());
        $this->indent--;
        return trim(<<<XAML
        {$this->indentStr()}<ItemsControl>
        {$items}
        {$this->indentStr()}</ItemsControl>
XAML);
    }

    private function generateNavigationView(NavigationView $widget): string
    {
        $this->indent++;
        $screens = $this->generateChildren($widget->screens());
        $this->indent--;
        return trim(<<<XAML
        {$this->indentStr()}<Frame>
        {$screens}
        {$this->indentStr()}</Frame>
XAML);
    }

    private function generateTabView(TabView $widget): string
    {
        $this->indent++;
        $tabs = $this->generateChildren($widget->tabs());
        $this->indent--;
        return trim(<<<XAML
        {$this->indentStr()}<TabControl>
        {$tabs}
        {$this->indentStr()}</TabControl>
XAML);
    }

    private function generateChildren(array $children): string
    {
        $parts = [];
        foreach ($children as $child) {
            $parts[] = $this->generateWidget($child);
        }
        return implode("\n", $parts);
    }

    /**
     * @param array<string> $excludeProps Property value strings to exclude
     * @param array<string> $onlyProps    If non-empty, only include these property value strings
     */
    private function generateProperties(?Style $style, array $excludeProps = [], array $onlyProps = []): string
    {
        if ($style === null) {
            return '';
        }

        $includeProp = function (StyleProperty $prop) use ($excludeProps, $onlyProps): bool {
            if (in_array($prop->value, $excludeProps, true)) {
                return false;
            }
            if ($onlyProps !== [] && !in_array($prop->value, $onlyProps, true)) {
                return false;
            }
            return true;
        };

        $props = [];
        
        // Colors - fix double # issue
        if ($style->has(StyleProperty::BackgroundColor) && $includeProp(StyleProperty::BackgroundColor)) {
            $color = ltrim($style->get(StyleProperty::BackgroundColor), '#');
            $props[] = "Background=\"#{$color}\"";
        }
        if ($style->has(StyleProperty::ForegroundColor) && $includeProp(StyleProperty::ForegroundColor)) {
            $color = ltrim($style->get(StyleProperty::ForegroundColor), '#');
            $props[] = "Foreground=\"#{$color}\"";
        }
        if ($style->has(StyleProperty::BorderColor) && $includeProp(StyleProperty::BorderColor)) {
            $color = ltrim($style->get(StyleProperty::BorderColor), '#');
            $props[] = "BorderBrush=\"#{$color}\"";
        }
        
        // Sizing
        if ($style->has(StyleProperty::Width) && $includeProp(StyleProperty::Width)) {
            $props[] = "Width=\"{$style->get(StyleProperty::Width)}\"";
        }
        if ($style->has(StyleProperty::Height) && $includeProp(StyleProperty::Height)) {
            $props[] = "Height=\"{$style->get(StyleProperty::Height)}\"";
        }
        if ($style->has(StyleProperty::MinWidth) && $includeProp(StyleProperty::MinWidth)) {
            $props[] = "MinWidth=\"{$style->get(StyleProperty::MinWidth)}\"";
        }
        if ($style->has(StyleProperty::MinHeight) && $includeProp(StyleProperty::MinHeight)) {
            $props[] = "MinHeight=\"{$style->get(StyleProperty::MinHeight)}\"";
        }
        if ($style->has(StyleProperty::MaxWidth) && $includeProp(StyleProperty::MaxWidth)) {
            $props[] = "MaxWidth=\"{$style->get(StyleProperty::MaxWidth)}\"";
        }
        if ($style->has(StyleProperty::MaxHeight) && $includeProp(StyleProperty::MaxHeight)) {
            $props[] = "MaxHeight=\"{$style->get(StyleProperty::MaxHeight)}\"";
        }
        
        // Border
        if ($style->has(StyleProperty::BorderWidth) && $includeProp(StyleProperty::BorderWidth)) {
            $props[] = "BorderThickness=\"{$style->get(StyleProperty::BorderWidth)}\"";
        }
        if ($style->has(StyleProperty::CornerRadius) && $includeProp(StyleProperty::CornerRadius)) {
            $props[] = "CornerRadius=\"{$style->get(StyleProperty::CornerRadius)}\"";
        }
        
        // Font
        if ($style->has(StyleProperty::FontSize) && $includeProp(StyleProperty::FontSize)) {
            $props[] = "FontSize=\"{$style->get(StyleProperty::FontSize)}\"";
        }
        if ($style->has(StyleProperty::FontWeight) && $includeProp(StyleProperty::FontWeight)) {
            $props[] = "FontWeight=\"" . $this->mapFontWeight($style->get(StyleProperty::FontWeight)) . "\"";
        }
        if ($style->has(StyleProperty::FontFamily) && $includeProp(StyleProperty::FontFamily)) {
            $props[] = "FontFamily=\"{$style->get(StyleProperty::FontFamily)}\"";
        }
        if ($style->has(StyleProperty::TextAlignment) && $includeProp(StyleProperty::TextAlignment)) {
            $props[] = "TextAlignment=\"" . $this->mapTextAlignment($style->get(StyleProperty::TextAlignment)) . "\"";
        }
        if ($style->has(StyleProperty::LetterSpacing) && $includeProp(StyleProperty::LetterSpacing)) {
            $props[] = "CharacterSpacing=\"{$style->get(StyleProperty::LetterSpacing)}\"";
        }
        
        // Layout — single Padding="left,top,right,bottom"
        $left = 0; $top = 0; $right = 0; $bottom = 0;
        if ($style->has(StyleProperty::Padding) && $includeProp(StyleProperty::Padding)) {
            $v = (int) $style->get(StyleProperty::Padding);
            $left = $top = $right = $bottom = $v;
        }
        if ($style->has(StyleProperty::PaddingTop) && $includeProp(StyleProperty::PaddingTop)) {
            $top = (int) $style->get(StyleProperty::PaddingTop);
        }
        if ($style->has(StyleProperty::PaddingBottom) && $includeProp(StyleProperty::PaddingBottom)) {
            $bottom = (int) $style->get(StyleProperty::PaddingBottom);
        }
        if ($style->has(StyleProperty::PaddingLeading) && $includeProp(StyleProperty::PaddingLeading)) {
            $left = (int) $style->get(StyleProperty::PaddingLeading);
        }
        if ($style->has(StyleProperty::PaddingTrailing) && $includeProp(StyleProperty::PaddingTrailing)) {
            $right = (int) $style->get(StyleProperty::PaddingTrailing);
        }
        if ($left || $top || $right || $bottom) {
            $props[] = "Padding=\"{$left},{$top},{$right},{$bottom}\"";
        }
        if ($style->has(StyleProperty::Margin) && $includeProp(StyleProperty::Margin)) {
            $props[] = "Margin=\"{$style->get(StyleProperty::Margin)}\"";
        }
        if ($style->has(StyleProperty::Opacity) && $includeProp(StyleProperty::Opacity)) {
            $props[] = "Opacity=\"{$style->get(StyleProperty::Opacity)}\"";
        }

        // Flex layout
        if ($style->has(StyleProperty::FlexGrow) && $includeProp(StyleProperty::FlexGrow)) {
            $props[] = "HorizontalAlignment=\"Stretch\"";
        }

        // Transform (basic RenderTransform)
        $xforms = [];
        if ($style->has(StyleProperty::Rotate)) {
            $xforms[] = "<RotateTransform Angle=\"{$style->get(StyleProperty::Rotate)}\" />";
        }
        if ($style->has(StyleProperty::Scale)) {
            $v = $style->get(StyleProperty::Scale);
            $xforms[] = "<ScaleTransform ScaleX=\"{$v}\" ScaleY=\"{$v}\" />";
        }
        if ($style->has(StyleProperty::TranslateX) || $style->has(StyleProperty::TranslateY)) {
            $tx = $style->has(StyleProperty::TranslateX) ? $style->get(StyleProperty::TranslateX) : 0;
            $ty = $style->has(StyleProperty::TranslateY) ? $style->get(StyleProperty::TranslateY) : 0;
            $xforms[] = "<TranslateTransform X=\"{$tx}\" Y=\"{$ty}\" />";
        }
        if (!empty($xforms)) {
            $xformStr = implode('', $xforms);
            $props[] = "RenderTransform=\"<TransformGroup>{$xformStr}</TransformGroup>\"";
        }

        if (empty($props)) {
            return '';
        }
        
        return ' ' . implode(' ', $props);
    }

    private function mapFontWeight(string $weight): string
    {
        $map = [
            'bold' => 'Bold',
            'semibold' => 'SemiBold',
            'medium' => 'Medium',
            'light' => 'Light',
            'regular' => 'Normal',
            'thin' => 'Thin',
            'black' => 'Black',
        ];
        return $map[strtolower($weight)] ?? 'Normal';
    }

    private function mapTextAlignment(string $alignment): string
    {
        return match (strtolower($alignment)) {
            'left' => 'Left',
            'right' => 'Right',
            'center' => 'Center',
            'justify' => 'Justify',
            default => 'Left',
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
            StyleProperty::BackgroundColor, StyleProperty::ForegroundColor, StyleProperty::BorderColor,
            StyleProperty::BorderWidth, StyleProperty::CornerRadius, StyleProperty::Width,
            StyleProperty::Height, StyleProperty::MinWidth, StyleProperty::MinHeight,
            StyleProperty::MaxWidth, StyleProperty::MaxHeight, StyleProperty::FontSize,
            StyleProperty::FontWeight, StyleProperty::FontFamily, StyleProperty::TextAlignment,
            StyleProperty::Padding, StyleProperty::PaddingTop, StyleProperty::PaddingBottom,
            StyleProperty::PaddingLeading, StyleProperty::PaddingTrailing, StyleProperty::Margin,
            StyleProperty::Opacity, StyleProperty::TextDecoration, StyleProperty::LineSpacing,
            StyleProperty::LetterSpacing,
            StyleProperty::FlexDirection, StyleProperty::JustifyContent, StyleProperty::AlignItems,
            StyleProperty::FlexWrap, StyleProperty::Gap, StyleProperty::FlexGrow, StyleProperty::FlexShrink,
            // Transform
            StyleProperty::Rotate, StyleProperty::Scale, StyleProperty::TranslateX, StyleProperty::TranslateY,
        ];
    }
}
