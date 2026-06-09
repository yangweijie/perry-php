<?php

declare(strict_types=1);

namespace Perry\Codegen;

use Perry\Build\Target;
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
    
    /** @var bool 是否需要 WebView2 支持 */
    private bool $needsWebView2 = false;

    /** @var string|null WebView 的 HTML 内容 */
    private ?string $webViewHtml = null;
    
    /** @var array<string, string> 绑定变量名到 TextBlock 名称的映射 */
    private array $textBindings = [];

    /** @var array<string, string> 绑定变量名到 CheckBox 名称的映射 */
    private array $checkboxBindings = [];

    /** @var array<string, string> 绑定变量名到 TabControl 名称的映射 */
    private array $tabControlBindings = [];

    /** @var string 应用标题 */
    private string $appTitle = 'Perry App';

    /** @var string C# 命名空间 */
    private string $appNamespace = 'PerryApp';

    /** @var string 窗口背景色 */
    private string $appBackground = 'White';

    /** @var array<string, int> 已生成的方法名计数器，用于去重 */
    private array $methodNameCounts = [];

    public function needsWebView2(): bool
    {
        return $this->needsWebView2;
    }

    public function getWebViewHtml(): ?string
    {
        return $this->webViewHtml;
    }

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
        $this->needsWebView2 = false;
        $this->webViewHtml = null;
        $this->textBindings = [];
        $this->checkboxBindings = [];
        $this->tabControlBindings = [];
        $this->methodNameCounts = [];
        
        // Extract state variables and window size from AppContainer
        if ($root instanceof \Perry\UI\Widget\AppContainer) {
            foreach ($root->bindings() as $binding) {
                $this->stateVars[$binding->name] = $binding->initialValue;
            }
            $this->windowWidth = $root->windowWidth() !== null ? (string) $root->windowWidth() : '800';
            $this->windowHeight = $root->windowHeight() !== null ? (string) $root->windowHeight() : '600';
            if ($root->getTitle() !== null) $this->appTitle = $root->getTitle();
            if ($root->getNamespace() !== null) $this->appNamespace = $root->getNamespace();
            if ($root->getBackground() !== null) $this->appBackground = $root->getBackground();
        }
        
        $body = $this->generateWidget($root);
        $indentedBody = implode("\n", array_map(
            fn($line) => '            ' . $line,
            explode("\n", $body)
        ));

        $wv2ns = $this->needsWebView2
            ? "\n        xmlns:wv2=\"clr-namespace:Microsoft.Web.WebView2.Wpf;assembly=Microsoft.Web.WebView2.Wpf\""
            : '';

        return trim(<<<XAML
<Window x:Class="{$this->appNamespace}.MainWindow"
        xmlns="http://schemas.microsoft.com/winfx/2006/xaml/presentation"
        xmlns:x="http://schemas.microsoft.com/winfx/2006/xaml"{$wv2ns}
        Title="{$this->appTitle}"
        Width="{$this->windowWidth}"
        Height="{$this->windowHeight}"
        Background="{$this->appBackground}"
        WindowStartupLocation="CenterScreen">
    <Grid>
{$indentedBody}
    </Grid>
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
            // Use async void if the body contains await (streaming API calls)
            $asyncKw = str_contains($body, 'await ') ? 'async ' : '';
            // Skip trailing UpdateUI() if the body already contains it
            $trailingUpdate = str_contains($body, 'UpdateUI()') ? '' : "\n            UpdateUI();";
            $methods .= <<<CS

        private {$asyncKw}void {$methodName}(object sender, {$eventArgsType} e) {
{$prependCode}{$body}{$trailingUpdate}
        }
CS;
        }

        $fields = $this->generateFields();

        $updateUICode = '';
        foreach ($this->textBindings as $bindingName => $textBlockName) {
            $updateUICode .= "            if ({$textBlockName} != null) {$textBlockName}.Text = {$bindingName}.ToString() ?? \"\";\n";
        }
        // Sync CheckBox IsChecked state
        foreach ($this->checkboxBindings as $bindingName => $checkboxName) {
            $updateUICode .= "            if ({$checkboxName} != null) {$checkboxName}.IsChecked = {$bindingName};\n";
        }
        // Sync TabControl SelectedIndex state
        foreach ($this->tabControlBindings as $bindingName => $tabControlName) {
            $updateUICode .= "            if ({$tabControlName} != null) {$tabControlName}.SelectedIndex = {$bindingName};\n";
        }

        $usings = "using System;\n";
        if ($hasSliderEvent) {
            $usings .= "using System.Windows.Controls.Primitives;\n";
        }
        $usings .= "using System.Windows;\nusing System.Windows.Controls;\n";
        if ($this->needsWebView2) {
            $usings .= "using Microsoft.Web.WebView2.Wpf;\nusing Microsoft.Web.WebView2.Core;\n";
        }

        if ($this->needsWebView2 && $this->webViewHtml !== null) {
            // Write HTML to file alongside the executable, then load via Source
            // This avoids C# string escaping issues and is more robust than embedding HTML in source
            $csWebViewInit = <<<CS_WEBVIEW

        private async void MainWindow_Loaded(object sender, RoutedEventArgs e)
        {
            // Initialize WebView2
            try {
                var appDir = System.IO.Path.GetDirectoryName(System.Reflection.Assembly.GetExecutingAssembly().Location) ?? ".";
                var htmlFile = System.IO.Path.Combine(appDir, "{$outputName}.html");
                await webView.EnsureCoreWebView2Async();
                webView.CoreWebView2.NavigateToString(System.IO.File.ReadAllText(htmlFile));
            } catch (Exception ex) {
                try {
                    webView.CoreWebView2.NavigateToString("<html><body><h1>Error</h1><p>" + ex.Message + "</p></body></html>");
                } catch {
                    // WebView2 may not be available
                }
            }

            // Health check: verify PHP backend is reachable
            try {
                var health = await App.Api.GetAsync<System.Text.Json.JsonElement>("/api/health");
                if (health != null) {
                    cleanStatus = "Backend connected";
                    UpdateUI();
                }
            } catch {
                cleanStatus = "Backend unavailable - please start the server";
                UpdateUI();
            }
        }
CS_WEBVIEW;
        } elseif ($this->needsWebView2) {
            $csWebViewInit = <<<'CS_WEBVIEW'

        private async void MainWindow_Loaded(object sender, RoutedEventArgs e)
        {
            try {
                await webView.EnsureCoreWebView2Async();
                webView.CoreWebView2.NavigateToString("<html><body><h1>WebView2 Ready</h1></body></html>");
            } catch (Exception ex) {
                // WebView2 not available
            }
        }
CS_WEBVIEW;
        } else {
            $csWebViewInit = <<<'CS_WEBVIEW'

        private void MainWindow_Loaded(object sender, RoutedEventArgs e)
        {
            // WebView2 not used in this app
        }
CS_WEBVIEW;
        }

        $openBrowserMethod = <<<'CS'

        private void OpenInDefaultBrowser(string url)
        {
            try {
                System.Diagnostics.Process.Start(new System.Diagnostics.ProcessStartInfo
                {
                    FileName = url,
                    UseShellExecute = true
                });
            } catch (Exception ex)
            {
                MessageBox.Show("Please open this URL manually:" + Environment.NewLine + Environment.NewLine + url, "WebView2 Download", MessageBoxButton.OK, MessageBoxImage.Information);
            }
        }
CS;

        return <<<CS
{$usings}
namespace {$this->appNamespace}
{
    public partial class MainWindow : Window
    {
{$fields}
        public MainWindow() {
            InitializeComponent();
            UpdateUI();
            Loaded += MainWindow_Loaded;
        }
{$methods}

        private void UpdateUI()
        {
{$updateUICode}
        }
{$csWebViewInit}
{$openBrowserMethod}
    }
}
CS;
    }

    private function generateActionBody(\Perry\UI\Action $action): string
    {
        if ($action->type === \Perry\UI\ActionType::Custom) {
            $code = $action->customCode;
            // Multi-line code = actual C# implementation, embed directly
            if (str_contains($code, "\n")) {
                $lines = explode("\n", $code);
                $indented = array_map(fn($line) => '            ' . $line, $lines);
                return implode("\n", $indented);
            }
            // Single-line starting with // = comment placeholder
            if (str_starts_with(trim($code), '//')) {
                return '            // Custom action: ' . $code;
            }
            // Single-line actual code
            return '            ' . $code;
        }

        if ($action->type === \Perry\UI\ActionType::Closure) {
            $generator = new \Perry\Generator\CSharpGenerator(array_keys($this->stateVars));
            $code = $action->generate($generator);
            $code = $this->indentCs($code, 3);
            // Ensure code ends with semicolon
            $code = rtrim($code);
            if (!empty($code) && substr($code, -1) !== ';') {
                $code .= ';';
            }
            return $code;
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

        if ($widget instanceof \Perry\UI\Composition) {
            return $this->generateWidget($widget->toWidget());
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
            WidgetKind::Checkbox => $this->generateCheckbox($widget),
            WidgetKind::RadioButton => $this->generateRadioButton($widget),
            WidgetKind::Progress => $this->generateProgress($widget),
            WidgetKind::Dialog => $this->generateDialog($widget),
            WidgetKind::Toast => $this->generateToast($widget),
            WidgetKind::Dropdown => $this->generateDropdown($widget),
            WidgetKind::ListWidget => $this->generateListWidget($widget),
            WidgetKind::NavigationView => $this->generateNavigationView($widget),
            WidgetKind::TabView => $this->generateTabView($widget),
            WidgetKind::SegmentedControl => $this->generateSegmentedControl($widget),
            WidgetKind::ContextMenu => $this->generateContextMenuWidget($widget),
            WidgetKind::DatePicker => $this->generateDatePickerWidget($widget),
        WidgetKind::AnimatedContainer => $this->generateAnimatedContainer($widget),
        WidgetKind::Transition => $this->generateTransition($widget),
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
        $buttonProps = $this->generateProperties($style, ['corner_radius']);

        $action = $widget->getAction();
        $clickAttr = '';
        if ($action !== null) {
            $baseName = 'On' . $this->safeMethodName($widget->label());
            $methodName = $baseName . 'Click';
            // 去重: 如果方法名已存在，添加数字后缀
            if (isset($this->methodNameCounts[$methodName])) {
                $this->methodNameCounts[$methodName]++;
                $methodName = $baseName . $this->methodNameCounts[$methodName] . 'Click';
            } else {
                $this->methodNameCounts[$methodName] = 1;
            }
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

    /**
     * Format a color value for XAML.
     * Hex colors (#RRGGBB, #AARRGGBB) keep the # prefix.
     * Named colors (Transparent, White, Black, etc.) are returned as-is.
     */
    private function formatXamlColor(string $color): string
    {
        $color = trim($color);
        // Already has # prefix — hex color
        if (str_starts_with($color, '#')) {
            return $color;
        }
        // Looks like hex without # (all hex chars, 6 or 8 digits)
        if (preg_match('/^[0-9a-fA-F]{6}([0-9a-fA-F]{2})?$/', $color)) {
            return '#' . $color;
        }
        // Named color (Transparent, White, Red, etc.) — return as-is with PascalCase
        return ucfirst(strtolower($color));
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

        $excludeStackProps = array_merge(['corner_radius'], $hasPadding ? self::PADDING_PROPS : []);
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

        $excludeStackProps = array_merge(['corner_radius'], $hasPadding ? self::PADDING_PROPS : []);
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

        $isOn = $widget->getIsOn();
        $isCheckedAttr = '';
        if ($isOn !== null) {
            $defaultVal = $isOn->initialValue ? 'True' : 'False';
            $isCheckedAttr = " IsChecked=\"{$defaultVal}\" x:Name=\"checkbox_{$isOn->name}\"";
            $this->checkboxBindings[$isOn->name] = 'checkbox_' . $isOn->name;
        }

        $onToggle = '';
        $action = $widget->getOnToggle();
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
        {$this->indentStr()}<CheckBox Content="{$label}"{$isCheckedAttr}{$onToggle}{$props} />
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
        $this->needsWebView2 = true;
        $this->webViewHtml = $widget->html();
        return trim(<<<XAML
        {$this->indentStr()}<wv2:WebView2 x:Name="webView" />
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
        $parts = [];
        $labels = $widget->getLabels();
        $selectedIndexAttr = '';
        $selected = $widget->getSelected();
        if ($selected !== null) {
            $tabControlName = 'tabControl_main';
            $selectedIndexAttr = " SelectedIndex=\"{$selected->initialValue}\" x:Name=\"{$tabControlName}\"";
            $this->tabControlBindings[$selected->name] = $tabControlName;
        }
        foreach ($widget->tabs() as $i => $tab) {
            $header = htmlspecialchars($labels[$i] ?? 'Tab ' . ($i + 1));
            $this->indent++;
            $content = $this->generateWidget($tab);
            $this->indent--;
            $parts[] = trim(<<<XAML
        {$this->indentStr()}<TabItem Header="{$header}">
        {$content}
        {$this->indentStr()}</TabItem>
XAML);
        }
        $tabs = implode("\n", $parts);
        $this->indent--;
        return trim(<<<XAML
        {$this->indentStr()}<TabControl{$selectedIndexAttr}>
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
        
        // Colors - handle both hex (#RRGGBB) and named colors (Transparent, White, etc.)
        if ($style->has(StyleProperty::BackgroundColor) && $includeProp(StyleProperty::BackgroundColor)) {
            $color = $this->formatXamlColor($style->get(StyleProperty::BackgroundColor));
            $props[] = "Background=\"{$color}\"";
        }
        if ($style->has(StyleProperty::ForegroundColor) && $includeProp(StyleProperty::ForegroundColor)) {
            $color = $this->formatXamlColor($style->get(StyleProperty::ForegroundColor));
            $props[] = "Foreground=\"{$color}\"";
        }
        if ($style->has(StyleProperty::BorderColor) && $includeProp(StyleProperty::BorderColor)) {
            $color = $this->formatXamlColor($style->get(StyleProperty::BorderColor));
            $props[] = "BorderBrush=\"{$color}\"";
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

    private function generateCheckbox(Checkbox $widget): string
    {
        $label = htmlspecialchars($widget->label());
        $props = $this->generateProperties($widget->getStyle());

        $isChecked = $widget->getIsChecked();
        $isCheckedAttr = $isChecked ? " IsChecked=\"{" . $isChecked->name . "}\"" : '';

        $onChange = $widget->getOnChange();
        $clickAttr = '';
        if ($onChange !== null) {
            $safeId = $this->safeMethodName($widget->label());
            $methodName = 'On' . $safeId . 'CheckChange';
            $this->buttonActions[] = [
                'id' => $widget->label(),
                'method' => $methodName,
                'action' => $onChange,
                'eventType' => 'CheckChanged',
                'bindingName' => $isChecked ? $isChecked->name : '',
            ];
            $clickAttr = " Checked=\"{$methodName}\" Unchecked=\"{$methodName}\"";
        }

        return trim(<<<XAML
        {$this->indentStr()}<CheckBox Content="{$label}"{$isCheckedAttr}{$clickAttr}{$props} />
XAML);
    }

    private function generateRadioButton(RadioButton $widget): string
    {
        $label = htmlspecialchars($widget->label());
        $group = htmlspecialchars($widget->group());
        $value = htmlspecialchars($widget->getValue());
        $props = $this->generateProperties($widget->getStyle());

        $selectedValue = $widget->getSelectedValue();
        $isCheckedAttr = '';
        if ($selectedValue !== null) {
            $isCheckedAttr = " IsChecked=\"{" . $selectedValue->name . " == '" . $value . "'}\"";
        }

        $onChange = $widget->getOnChange();
        $clickAttr = '';
        if ($onChange !== null) {
            $safeId = $this->safeMethodName($widget->label());
            $methodName = 'On' . $safeId . 'RadioChange_' . $group;
            $this->buttonActions[] = [
                'id' => $widget->label(),
                'method' => $methodName,
                'action' => $onChange,
            ];
            $clickAttr = " Checked=\"{$methodName}\"";
        }

        return trim(<<<XAML
        {$this->indentStr()}<RadioButton Content="{$label}" GroupName="{$group}"{$isCheckedAttr}{$clickAttr}{$props} />
XAML);
    }

    private function generateProgress(Progress $widget): string
    {
        $props = $this->generateProperties($widget->getStyle());

        $progress = $widget->getProgress();
        $valueAttr = $progress ? " Value=\"{" . $progress->name . "}\"" : '';

        return trim(<<<XAML
        {$this->indentStr()}<ProgressBar{$valueAttr}{$props} />
XAML);
    }

    private function generateDialog(Dialog $widget): string
    {
        // WinUI has ContentDialog, but it requires code-behind. For codegen simplicity,
        // generate a Border overlay that acts as a modal dialog container.
        $this->indent++;
        $children = $this->generateChildren($widget->children());
        $this->indent--;

        $isOpen = $widget->getIsOpen();
        $visibility = $isOpen ? " Visibility=\"Visible\"" : ' Visibility="Collapsed"';

        return trim(<<<XAML
        {$this->indentStr()}<Border Background="#80FFFFFF"{$visibility}>
        {$this->indentStr()}    <Border Background="White" BorderBrush="Gray" BorderThickness="1" CornerRadius="8" Padding="20" Margin="50">
        {$children}
        {$this->indentStr()}    </Border>
        {$this->indentStr()}</Border>
XAML);
    }

    private function generateToast(Toast $widget): string
    {
        $message = htmlspecialchars($widget->message());

        return trim(<<<XAML
        {$this->indentStr()}<Border Background="#333333" CornerRadius="4" Padding="12,8" Margin="20" HorizontalAlignment="Center">
        {$this->indentStr()}    <TextBlock Text="{$message}" Foreground="White" TextWrapping="Wrap" />
        {$this->indentStr()}</Border>
XAML);
    }

    private function generateDropdown(Dropdown $widget): string
    {
        $props = $this->generateProperties($widget->getStyle());

        $selectedValue = $widget->getSelectedValue();
        $selectedAttr = $selectedValue ? " SelectedValue=\"{" . $selectedValue->name . "}\"" : '';

        $onChange = $widget->getOnChange();
        $changeAttr = '';
        if ($onChange !== null) {
            $methodName = 'OnDropdownChange';
            $this->buttonActions[] = [
                'id' => 'dropdown',
                'method' => $methodName,
                'action' => $onChange,
            ];
            $changeAttr = " SelectionChanged=\"{$methodName}\"";
        }

        $this->indent++;
        $items = '';
        foreach ($widget->options() as $label => $value) {
            $escapedLabel = htmlspecialchars((string) $label);
            $items .= "\n" . $this->indentStr() . "<ComboBoxItem Content=\"{$escapedLabel}\" />";
        }
        $this->indent--;

        return trim(<<<XAML
        {$this->indentStr()}<ComboBox{$selectedAttr}{$changeAttr}{$props}>{$items}
        {$this->indentStr()}</ComboBox>
XAML);
    }

    private function generateSegmentedControl(SegmentedControl $widget): string
    {
        $props = $this->generateProperties($widget->getStyle());
        $selectedValue = $widget->getSelectedValue();
        $onChange = $widget->getOnChange();

        $changeAttr = '';
        if ($onChange !== null) {
            $methodName = 'OnSegmentedChange';
            $this->buttonActions[] = ['id' => 'segmented', 'method' => $methodName, 'action' => $onChange];
            $changeAttr = " SelectionChanged=\"{$methodName}\"";
        }

        $this->indent++;
        $items = '';
        foreach ($widget->options() as $label => $val) {
            $escapedLabel = htmlspecialchars((string) $label);
            $selectedPrefix = ' ';
            if ($selectedValue !== null) {
                $selectedPrefix = " IsChecked=\"True\"";
            }
            $items .= "\n" . $this->indentStr() . "<RadioButton Content=\"{$escapedLabel}\"{$selectedPrefix} GroupName=\"seg\" />";
        }
        $this->indent--;

        return trim(<<<XAML
        {$this->indentStr()}<StackPanel Orientation="Horizontal"{$props}{$changeAttr}>{$items}
        {$this->indentStr()}</StackPanel>
XAML);
    }

    private function generateContextMenuWidget(ContextMenu $widget): string
    {
        $isOpen = $widget->getIsOpen();
        $visibility = $isOpen ? 'Visible' : 'Collapsed';

        $this->indent++;
        $items = '';
        foreach ($widget->items() as $label => $val) {
            $escapedLabel = htmlspecialchars((string) $label);
            $items .= "\n" . $this->indentStr() . "<MenuItem Header=\"{$escapedLabel}\" />";
        }
        $this->indent--;

        return trim(<<<XAML
        {$this->indentStr()}<Menu Visibility="{$visibility}">{$items}
        {$this->indentStr()}</Menu>
XAML);
    }

    private function generateDatePickerWidget(DatePicker $widget): string
    {
        $props = $this->generateProperties($widget->getStyle());
        $isOpen = $widget->getIsOpen();
        $visibility = $isOpen ? 'Visible' : 'Collapsed';

        return trim(<<<XAML
        {$this->indentStr()}<DatePicker Visibility="{$visibility}"{$props} />
XAML);
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
            // Transition
            StyleProperty::TransitionProperty, StyleProperty::TransitionDuration, StyleProperty::TransitionDelay,
            StyleProperty::TransitionTimingFunction,
        ];
    }
}
