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
        $body = $this->generateWidget($root);

        return <<<XAML
        <Window
            x:Class="PerryApp.MainWindow"
            xmlns="http://schemas.microsoft.com/winfx/2006/xaml/presentation"
            xmlns:x="http://schemas.microsoft.com/winfx/2006/xaml"
            Title="Perry App"
            Width="800"
            Height="600">
            {$body}
        </Window>
        XAML;
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
        }
CS;
        }

        return <<<CS
using System.Windows;
using System.Windows.Controls;

namespace PerryApp {
    public partial class App : Application {
        protected override void OnStartup(StartupEventArgs e) {
            base.OnStartup(e);
            var mainWindow = new MainWindow();
            mainWindow.Show();
        }
    }

    public partial class MainWindow : Window {
        public MainWindow() {
            InitializeComponent();
        }
{$methods}
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
            $code = $action->generate(new \Perry\Generator\CSharpGenerator());
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
        return <<<XAML
        {$this->indentStr()}<TextBlock Text="{$text}"{$props} />
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

        // Colors
        if ($style->has(StyleProperty::BackgroundColor)) {
            $props[] = "Background=\"#{$style->get(StyleProperty::BackgroundColor)}\"";
        }
        if ($style->has(StyleProperty::ForegroundColor)) {
            $props[] = "Foreground=\"#{$style->get(StyleProperty::ForegroundColor)}\"";
        }
        if ($style->has(StyleProperty::BorderColor)) {
            $props[] = "BorderBrush=\"#{$style->get(StyleProperty::BorderColor)}\"";
        }

        // Sizing
        if ($style->has(StyleProperty::Width)) {
            $props[] = "Width=\"{$style->get(StyleProperty::Width)}\"";
        }
        if ($style->has(StyleProperty::Height)) {
            $props[] = "Height=\"{$style->get(StyleProperty::Height)}\"";
        }
        if ($style->has(StyleProperty::MinWidth)) {
            $props[] = "MinWidth=\"{$style->get(StyleProperty::MinWidth)}\"";
        }
        if ($style->has(StyleProperty::MinHeight)) {
            $props[] = "MinHeight=\"{$style->get(StyleProperty::MinHeight)}\"";
        }
        if ($style->has(StyleProperty::MaxWidth)) {
            $props[] = "MaxWidth=\"{$style->get(StyleProperty::MaxWidth)}\"";
        }
        if ($style->has(StyleProperty::MaxHeight)) {
            $props[] = "MaxHeight=\"{$style->get(StyleProperty::MaxHeight)}\"";
        }

        // Border
        if ($style->has(StyleProperty::BorderWidth)) {
            $props[] = "BorderThickness=\"{$style->get(StyleProperty::BorderWidth)}\"";
        }
        if ($style->has(StyleProperty::CornerRadius)) {
            $props[] = "CornerRadius=\"{$style->get(StyleProperty::CornerRadius)}\"";
        }

        // Margin & Padding
        if ($style->has(StyleProperty::Margin)) {
            $v = $style->get(StyleProperty::Margin);
            $props[] = "Margin=\"{$v}\"";
        }
        if ($style->has(StyleProperty::Padding)) {
            $v = $style->get(StyleProperty::Padding);
            $props[] = "Padding=\"{$v}\"";
        }
        if ($style->has(StyleProperty::PaddingTop)) {
            $v = $style->get(StyleProperty::PaddingTop);
            $props[] = "Padding=\"{0},{$v},0,0\"";
        }
        if ($style->has(StyleProperty::PaddingBottom)) {
            $v = $style->get(StyleProperty::PaddingBottom);
            $props[] = "Padding=\"0,0,0,{$v}\"";
        }
        if ($style->has(StyleProperty::PaddingLeading)) {
            $v = $style->get(StyleProperty::PaddingLeading);
            $props[] = "Padding=\"{$v},0,0,0\"";
        }
        if ($style->has(StyleProperty::PaddingTrailing)) {
            $v = $style->get(StyleProperty::PaddingTrailing);
            $props[] = "Padding=\"0,0,{$v},0\"";
        }

        // Opacity
        if ($style->has(StyleProperty::Opacity)) {
            $props[] = "Opacity=\"{$style->get(StyleProperty::Opacity)}\"";
        }

        // Font
        if ($style->has(StyleProperty::FontSize)) {
            $props[] = "FontSize=\"{$style->get(StyleProperty::FontSize)}\"";
        }
        if ($style->has(StyleProperty::FontWeight)) {
            $v = $style->get(StyleProperty::FontWeight);
            $props[] = "FontWeight=\"{$v}\"";
        }
        if ($style->has(StyleProperty::FontFamily)) {
            $v = $style->get(StyleProperty::FontFamily);
            $props[] = "FontFamily=\"{$v}\"";
        }
        if ($style->has(StyleProperty::TextAlignment)) {
            $v = $style->get(StyleProperty::TextAlignment);
            $map = ['left' => 'Left', 'center' => 'Center', 'right' => 'Right'];
            $align = $map[$v] ?? 'Left';
            $props[] = "TextAlignment=\"{$align}\"";
        }
        if ($style->has(StyleProperty::TextDecoration)) {
            $v = $style->get(StyleProperty::TextDecoration);
            $props[] = "TextDecorations=\"{$v}\"";
        }
        if ($style->has(StyleProperty::LineSpacing)) {
            $props[] = "LineHeight=\"{$style->get(StyleProperty::LineSpacing)}\"";
        }

        // Shadow (elevation approximation)
        if ($style->has(StyleProperty::ShadowRadius)) {
            $props[] = "Shadow=\"{$style->get(StyleProperty::ShadowRadius)}\"";
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
