<?php

declare(strict_types=1);

namespace Perry\Codegen;

use Perry\Build\Target;
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

final class AndroidXmlBackend extends CodegenBackend
{
    private int $indent = 0;

    public function name(): string
    {
        return 'android-xml';
    }

    public function supports(Target $target): bool
    {
        return $target === Target::Android;
    }

    public function generate(Widget $root): string
    {
        $this->indent = 1;
        $body = $this->generateWidget($root);

        return <<<XML
        <?xml version="1.0" encoding="utf-8"?>
        <LinearLayout xmlns:android="http://schemas.android.com/apk/res/android"
            android:layout_width="match_parent"
            android:layout_height="match_parent"
            android:orientation="vertical">

        {$body}
        </LinearLayout>
        XML;
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
            WidgetKind::Spacer => $this->indentStr() . "<Space\n{$this->indentStr()}    android:layout_width=\"0dp\"\n{$this->indentStr()}    android:layout_height=\"0dp\"\n{$this->indentStr()}    android:layout_weight=\"1\" />\n",
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
        return <<<XML
        {$this->indentStr()}<TextView
        {$this->indentStr()}    android:layout_width="wrap_content"
        {$this->indentStr()}    android:layout_height="wrap_content"
        {$this->indentStr()}    android:text="{$text}" />
        XML;
    }

    private function generateButton(Button $widget): string
    {
        $label = htmlspecialchars($widget->label());
        return <<<XML
        {$this->indentStr()}<Button
        {$this->indentStr()}    android:layout_width="wrap_content"
        {$this->indentStr()}    android:layout_height="wrap_content"
        {$this->indentStr()}    android:text="{$label}" />
        XML;
    }

    private function generateVStack(VStack $widget): string
    {
        $this->indent++;
        $children = $this->generateChildren($widget->children());
        $this->indent--;

        return <<<XML
        {$this->indentStr()}<LinearLayout
        {$this->indentStr()}    android:layout_width="match_parent"
        {$this->indentStr()}    android:layout_height="wrap_content"
        {$this->indentStr()}    android:orientation="vertical">
        {$children}
        {$this->indentStr()}</LinearLayout>
        XML;
    }

    private function generateHStack(HStack $widget): string
    {
        $this->indent++;
        $children = $this->generateChildren($widget->children());
        $this->indent--;

        return <<<XML
        {$this->indentStr()}<LinearLayout
        {$this->indentStr()}    android:layout_width="match_parent"
        {$this->indentStr()}    android:layout_height="wrap_content"
        {$this->indentStr()}    android:orientation="horizontal">
        {$children}
        {$this->indentStr()}</LinearLayout>
        XML;
    }

    private function generateImage(Image $widget): string
    {
        $src = htmlspecialchars($widget->source());
        return <<<XML
        {$this->indentStr()}<ImageView
        {$this->indentStr()}    android:layout_width="wrap_content"
        {$this->indentStr()}    android:layout_height="wrap_content"
        {$this->indentStr()}    android:src="{$src}" />
        XML;
    }

    private function generateScrollView(ScrollView $widget): string
    {
        $this->indent++;
        $children = $this->generateChildren($widget->children());
        $this->indent--;

        return <<<XML
        {$this->indentStr()}<ScrollView
        {$this->indentStr()}    android:layout_width="match_parent"
        {$this->indentStr()}    android:layout_height="match_parent">
        {$children}
        {$this->indentStr()}</ScrollView>
        XML;
    }

    private function generateTextInput(TextInput $widget): string
    {
        $hint = htmlspecialchars($widget->placeholder());
        return <<<XML
        {$this->indentStr()}<EditText
        {$this->indentStr()}    android:layout_width="match_parent"
        {$this->indentStr()}    android:layout_height="wrap_content"
        {$this->indentStr()}    android:hint="{$hint}" />
        XML;
    }

    private function generateToggle(Toggle $widget): string
    {
        $label = htmlspecialchars($widget->label());
        return <<<XML
        {$this->indentStr()}<Switch
        {$this->indentStr()}    android:layout_width="wrap_content"
        {$this->indentStr()}    android:layout_height="wrap_content"
        {$this->indentStr()}    android:text="{$label}" />
        XML;
    }

    private function generateChildren(array $children): string
    {
        $parts = [];
        foreach ($children as $child) {
            $parts[] = $this->generateWidget($child);
        }
        return implode("\n", $parts);
    }

    private function indentStr(): string
    {
        return str_repeat('    ', $this->indent);
    }
}
