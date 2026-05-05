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

final class Gtk4Backend extends CodegenBackend
{
    private int $indent = 0;
    private int $objectId = 0;

    public function name(): string
    {
        return 'gtk4';
    }

    public function supports(Target $target): bool
    {
        return $target === Target::Gtk4Linux;
    }

    public function generate(Widget $root): string
    {
        $this->indent = 0;
        $this->objectId = 0;
        $body = $this->generateWidget($root);

        return <<<XML
        <?xml version="1.0" encoding="UTF-8"?>
        <interface>
            <requires lib="gtk" version="4.0" />
            <object class="GtkApplicationWindow" id="main_window">
                <property name="title">Perry App</property>
                <property name="default-width">800</property>
                <property name="default-height">600</property>
                <child>
                    {$body}
                </child>
            </object>
        </interface>
        XML;
    }

    private function nextId(): string
    {
        return 'obj_' . (++$this->objectId);
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
        $id = $this->nextId();
        $text = htmlspecialchars($widget->content());
        $props = $this->generateProperties($widget->getStyle());

        return <<<XML
        {$this->indentStr()}<object class="GtkLabel" id="{$id}">
        {$this->indentStr()}    <property name="label">{$text}</property>
        {$this->indentStr()}    <property name="xalign">0</property>
        {$props}
        {$this->indentStr()}</object>
        XML;
    }

    private function generateButton(Button $widget): string
    {
        $id = $this->nextId();
        $label = htmlspecialchars($widget->label());
        $props = $this->generateProperties($widget->getStyle());

        return <<<XML
        {$this->indentStr()}<object class="GtkButton" id="{$id}">
        {$this->indentStr()}    <property name="label">{$label}</property>
        {$props}
        {$this->indentStr()}</object>
        XML;
    }

    private function generateVStack(VStack $widget): string
    {
        $id = $this->nextId();
        $this->indent++;
        $children = $this->generateChildren($widget->children());
        $this->indent--;

        $props = $this->generateProperties($widget->getStyle());

        return <<<XML
        {$this->indentStr()}<object class="GtkBox" id="{$id}">
        {$this->indentStr()}    <property name="orientation">vertical</property>
        {$this->indentStr()}    <property name="spacing">8</property>
        {$props}
        {$children}
        {$this->indentStr()}</object>
        XML;
    }

    private function generateHStack(HStack $widget): string
    {
        $id = $this->nextId();
        $this->indent++;
        $children = $this->generateChildren($widget->children());
        $this->indent--;

        $props = $this->generateProperties($widget->getStyle());

        return <<<XML
        {$this->indentStr()}<object class="GtkBox" id="{$id}">
        {$this->indentStr()}    <property name="orientation">horizontal</property>
        {$this->indentStr()}    <property name="spacing">8</property>
        {$props}
        {$children}
        {$this->indentStr()}</object>
        XML;
    }

    private function generateSpacer(Spacer $widget): string
    {
        $id = $this->nextId();

        return <<<XML
        {$this->indentStr()}<object class="GtkSeparator" id="{$id}">
        {$this->indentStr()}    <property name="orientation">horizontal</property>
        {$this->indentStr()}</object>
        XML;
    }

    private function generateImage(Image $widget): string
    {
        $id = $this->nextId();
        $src = htmlspecialchars($widget->source());

        return <<<XML
        {$this->indentStr()}<object class="GtkImage" id="{$id}">
        {$this->indentStr()}    <property name="file">{$src}</property>
        {$this->indentStr()}</object>
        XML;
    }

    private function generateScrollView(ScrollView $widget): string
    {
        $id = $this->nextId();
        $this->indent++;
        $children = $this->generateChildren($widget->children());
        $this->indent--;

        return <<<XML
        {$this->indentStr()}<object class="GtkScrolledWindow" id="{$id}">
        {$children}
        {$this->indentStr()}</object>
        XML;
    }

    private function generateTextInput(TextInput $widget): string
    {
        $id = $this->nextId();
        $placeholder = htmlspecialchars($widget->placeholder());

        return <<<XML
        {$this->indentStr()}<object class="GtkEntry" id="{$id}">
        {$this->indentStr()}    <property name="placeholder-text">{$placeholder}</property>
        {$this->indentStr()}</object>
        XML;
    }

    private function generateToggle(Toggle $widget): string
    {
        $id = $this->nextId();
        $label = htmlspecialchars($widget->label());

        return <<<XML
        {$this->indentStr()}<object class="GtkSwitch" id="{$id}">
        {$this->indentStr()}    <property name="active">false</property>
        {$this->indentStr()}</object>
        XML;
    }

    private function generateChildren(array $children): string
    {
        $parts = [];
        foreach ($children as $child) {
            $parts[] = $this->indentStr() . '<child>' . "\n"
                . $this->generateWidget($child) . "\n"
                . $this->indentStr() . '</child>';
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
            $props[] = "{$this->indentStr()}    <property name=\"width-request\">{$style->get(StyleProperty::Width)}</property>";
        }
        if ($style->has(StyleProperty::Height)) {
            $props[] = "{$this->indentStr()}    <property name=\"height-request\">{$style->get(StyleProperty::Height)}</property>";
        }
        if ($style->has(StyleProperty::Opacity)) {
            $props[] = "{$this->indentStr()}    <property name=\"opacity\">{$style->get(StyleProperty::Opacity)}</property>";
        }

        if (empty($props)) {
            return '';
        }

        return "\n" . implode("\n", $props);
    }

    private function indentStr(): string
    {
        return str_repeat('    ', $this->indent);
    }
}
