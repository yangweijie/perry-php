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
use Perry\UI\Widget\VStack;
use Perry\UI\WidgetKind;

final class WinUIBackend extends CodegenBackend
{
    private int $indent = 0;

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
        return <<<XAML
        {$this->indentStr()}<Button Content="{$label}"{$props} />
        XAML;
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

    private function generateTextInput(TextInput $widget): string
    {
        $placeholder = htmlspecialchars($widget->placeholder());
        return <<<XAML
        {$this->indentStr()}<TextBox PlaceholderText="{$placeholder}" />
        XAML;
    }

    private function generateToggle(Toggle $widget): string
    {
        $label = htmlspecialchars($widget->label());
        return <<<XAML
        {$this->indentStr()}<ToggleSwitch Header="{$label}" />
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

        if ($style->has(StyleProperty::Width)) {
            $props[] = "Width=\"{$style->get(StyleProperty::Width)}\"";
        }
        if ($style->has(StyleProperty::Height)) {
            $props[] = "Height=\"{$style->get(StyleProperty::Height)}\"";
        }
        if ($style->has(StyleProperty::Opacity)) {
            $props[] = "Opacity=\"{$style->get(StyleProperty::Opacity)}\"";
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
