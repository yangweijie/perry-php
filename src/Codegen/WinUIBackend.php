<?php

declare(strict_types=1);

namespace Perry\Codegen;

use Perry\Build\Target;
use Perry\UI\Styling\StyleProperty;
use Perry\UI\Widget;
use Perry\UI\Widget\Button;
use Perry\UI\Widget\HStack;
use Perry\UI\Widget\Image;
use Perry\UI\Widget\ScrollView;
use Perry\UI\Widget\Spacer;
use Perry\UI\Widget\Text;
use Perry\UI\Widget\TextInput;
use Perry\UI\Widget\Toggle;
use Perry\UI\Widget\AppContainer;
use Perry\UI\Widget\ListWidget;
use Perry\UI\Widget\NavigationView;
use Perry\UI\Widget\Slider;
use Perry\UI\Widget\TabView;
use Perry\UI\Widget\VStack;
use Perry\UI\WidgetKind;

final class WinUIBackend extends CodegenBackend
{
    private int $indent = 0;

    /** @var array<array{id: string, method: string, action: \Perry\UI\Action}> */
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

        return <<<XAML
<Window
    x:Class="PerryApp.MainWindow"
    xmlns="http://schemas.microsoft.com/winfx/2006/xaml/presentation"
    xmlns:x="http://schemas.microsoft.com/winfx/2006/xaml"
    Title="Perry Calculator - Working!"
    Width="600"
    Height="800"
    Left="100"
    Top="100"
    Background="#FFFFFF"
    ShowInTaskbar="True"
    ResizeMode="CanResize"
    WindowStyle="SingleBorderWindow">
    <Border BorderBrush="#FF0000" BorderThickness="5">
        <StackPanel Background="#FFFF00" Margin="20">
            <TextBlock Text="=== CALCULATOR IS WORKING! ===" FontSize="24" FontWeight="Bold" Foreground="#FF0000" HorizontalAlignment="Center" Margin="10"/>
            {$body}
        </StackPanel>
    </Border>
</Window>
XAML;
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
        foreach ($this->buttonActions as $item) {
            $action = $item['action'];
            $methodName = $item['method'];
            $body = $this->generateActionBody($action);
            $methods .= <<<CS

        private void {$methodName}(object sender, RoutedEventArgs e) {
{$body}
            UpdateUI();
        }
CS;
        }

        $fields = $this->generateFields();
        
        $textBlockFieldDeclarations = '';
        foreach ($this->textBindings as $bindingName => $textBlockName) {
            $textBlockFieldDeclarations .= "        internal System.Windows.Controls.TextBlock {$textBlockName};\n";
        }
        
        $updateUICode = '';
        foreach ($this->textBindings as $bindingName => $textBlockName) {
            $updateUICode .= "            if ({$textBlockName} != null) {$textBlockName}.Text = {$bindingName}?.ToString() ?? \"\";\n";
        }
        
        $findNameCode = '';
        foreach ($this->textBindings as $bindingName => $textBlockName) {
            $findNameCode .= "            {$textBlockName} = FindName(\"{$textBlockName}\") as System.Windows.Controls.TextBlock;\n";
        }

        return <<<CS
using System.Windows;
using System.Windows.Controls;

namespace PerryApp
{
    public partial class MainWindow : Window
    {
{$fields}{$textBlockFieldDeclarations}
        public MainWindow() {
            InitializeComponent();
{$findNameCode}
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

        return '            // Action type not yet fully supported for WinUI: ' . $action->type->value;
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
            WidgetKind::ListWidget => $this->generateListWidget($widget),
            WidgetKind::NavigationView => $this->generateNavigationView($widget),
            WidgetKind::TabView => $this->generateTabView($widget),
            default => '',
        };
    }

    private function generateText(Text $widget): string
    {
        $text = htmlspecialchars($widget->content());
        $props = $this->generateProperties($widget->getStyle());
        
        $nameAttr = '';
        $binding = $widget->getBinding();
        if ($binding !== null) {
            $bindingName = $binding->name;
            $textBlockName = 'textBlock_' . $bindingName;
            $this->textBindings[$bindingName] = $textBlockName;
            $nameAttr = " x:Name=\"{$textBlockName}\"";
        }
        
        return <<<XAML
        {$this->indentStr()}<TextBlock Text="{$text}"{$nameAttr}{$props} />
        XAML;
    }

    private function generateButton(Button $widget): string
    {
        $label = htmlspecialchars($widget->label());
        $props = $this->generateProperties($widget->getStyle());

        $action = $widget->getAction();
        $clickAttr = '';
        if ($action !== null) {
            $safeId = $this->safeMethodName($widget->label());
            $methodName = 'On' . $safeId . 'Click';
            $this->buttonActions[] = ['id' => $widget->label(), 'method' => $methodName, 'action' => $action];
            $clickAttr = " Click=\"{$methodName}\"";
        }

        return <<<XAML
        {$this->indentStr()}<Button Content="{$label}"{$props}{$clickAttr} />
        XAML;
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

    private function generateVStack(VStack $widget): string
    {
        $this->indent++;
        $children = $this->generateChildren($widget->children());
        $this->indent--;

        $props = $this->generateProperties($widget->getStyle());
        return <<<XAML
        {$this->indentStr()}<StackPanel Orientation="Vertical"{$props}>
        {$children}
        {$this->indentStr()}</StackPanel>
        XAML;
    }

    private function generateHStack(HStack $widget): string
    {
        $this->indent++;
        $children = $this->generateChildren($widget->children());
        $this->indent--;

        $props = $this->generateProperties($widget->getStyle());
        return <<<XAML
        {$this->indentStr()}<StackPanel Orientation="Horizontal"{$props}>
        {$children}
        {$this->indentStr()}</StackPanel>
        XAML;
    }

    private function generateSpacer(Spacer $widget): string
    {
        return <<<XAML
        {$this->indentStr()}<Border />
        XAML;
    }

    private function generateImage(Image $widget): string
    {
        $src = htmlspecialchars($widget->source());
        return <<<XAML
        {$this->indentStr()}<Image Source="{$src}" />
        XAML;
    }

    private function generateScrollView(ScrollView $widget): string
    {
        $this->indent++;
        $children = $this->generateChildren($widget->children());
        $this->indent--;

        return <<<XAML
        {$this->indentStr()}<ScrollViewer>
        {$children}
        {$this->indentStr()}</ScrollViewer>
        XAML;
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
            $this->buttonActions[] = ['id' => $name, 'method' => $methodName, 'action' => $action];
            $onChange = " ValueChanged=\"{$methodName}\"";
        }

        return <<<XAML
        {$this->indentStr()}<Slider
            Minimum="{$min}"
            Maximum="{$max}"
            Step="{$step}"
            Value="{Binding ElementName={$name}}"{$onChange}{$props} />
        XAML;
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
            $this->buttonActions[] = ['id' => $name, 'method' => $methodName, 'action' => $action];
            $onChange = " TextChanged=\"{$methodName}\"";
        }

        return <<<XAML
        {$this->indentStr()}<TextBox
            PlaceholderText="{$placeholder}"
            Text="{Binding ElementName={$name}}"{$onChange}{$props} />
        XAML;
    }

    private function generateToggle(Toggle $widget): string
    {
        $label = htmlspecialchars($widget->label());
        $props = $this->generateProperties($widget->getStyle());

        $onToggle = '';
        $action = $widget->getOnToggle();
        if ($action !== null) {
            $methodName = 'On' . ucfirst($label) . 'Toggle';
            $this->buttonActions[] = ['id' => $label, 'method' => $methodName, 'action' => $action];
            $onToggle = " IsCheckedChanged=\"{$methodName}\"";
        }

        return <<<XAML
        {$this->indentStr()}<ToggleSwitch Header="{$label}"{$onToggle}{$props} />
        XAML;
    }

    private function generateListWidget(\Perry\UI\Widget\ListWidget $widget): string
    {
        $this->indent++;
        $items = $this->generateChildren($widget->items());
        $this->indent--;
        return <<<XAML
        {$this->indentStr()}<ItemsControl>
        {$items}
        {$this->indentStr()}</ItemsControl>
        XAML;
    }

    private function generateNavigationView(NavigationView $widget): string
    {
        $this->indent++;
        $screens = $this->generateChildren($widget->screens());
        $this->indent--;
        return <<<XAML
        {$this->indentStr()}<Frame>
        {$screens}
        {$this->indentStr()}</Frame>
        XAML;
    }

    private function generateTabView(TabView $widget): string
    {
        $this->indent++;
        $tabs = $this->generateChildren($widget->tabs());
        $this->indent--;
        return <<<XAML
        {$this->indentStr()}<TabControl>
        {$tabs}
        {$this->indentStr()}</TabControl>
        XAML;
    }

    private function generateChildren(array $children): string
    {
        $parts = [];
        foreach ($children as $child) {
            $parts[] = $this->generateWidget($child);
        }
        return implode("\n", $parts);
    }

    private function generateProperties(?\Perry\UI\Styling\Style $style): string
    {
        if ($style === null) {
            return '';
        }

        $props = [];
        
        // Colors - fix double # issue
        if ($style->has(StyleProperty::BackgroundColor)) {
            $color = ltrim($style->get(StyleProperty::BackgroundColor), '#');
            $props[] = "Background=\"#{$color}\"";
        }
        if ($style->has(StyleProperty::ForegroundColor)) {
            $color = ltrim($style->get(StyleProperty::ForegroundColor), '#');
            $props[] = "Foreground=\"#{$color}\"";
        }
        if ($style->has(StyleProperty::BorderColor)) {
            $color = ltrim($style->get(StyleProperty::BorderColor), '#');
            $props[] = "BorderBrush=\"#{$color}\"";
        }
        
        // Sizing
        if ($style->has(StyleProperty::Width)) {
            $props[] = "Width=\"{$style->get(StyleProperty::Width)}\"";
        }
        if ($style->has(StyleProperty::Height)) {
            $props[] = "Height=\"{$style->get(StyleProperty::Height)}\"";
        }
        
        // Border - CornerRadius not supported on StackPanel, skip for now
        if ($style->has(StyleProperty::BorderWidth)) {
            $props[] = "BorderThickness=\"{$style->get(StyleProperty::BorderWidth)}\"";
        }
        
        // Margin & Padding - not supported on StackPanel, skip for VStack/HStack
        // Font
        if ($style->has(StyleProperty::FontSize)) {
            $props[] = "FontSize=\"{$style->get(StyleProperty::FontSize)}\"";
        }
        
        if (empty($props)) {
            return '';
        }
        
        return ' ' . implode(' ', $props);
    }

    private function indentStr(): string
    {
        return str_repeat('    ', $this->indent);
    }
}
