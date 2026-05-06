<?php

declare(strict_types=1);

namespace Perry\Build;

use Perry\Codegen\CodegenBackend;
use Perry\UI\Widget;

final class Compiler
{
    private Target $target;
    private string $buildDir;

    public function __construct(?Target $target = null, string $buildDir = 'build')
    {
        $this->target = $target ?? Target::autoDetect();
        $this->buildDir = $buildDir;
    }

    public function compile(Widget $root, string $outputName = 'app'): CompileResult
    {
        if (!is_dir($this->buildDir)) {
            mkdir($this->buildDir, 0755, true);
        }

        $codegen = new \Perry\Codegen\CodegenFactory();
        $backend = $codegen->forTarget($this->target);
        $source = $backend->generate($root);

        $colors = ($backend instanceof \Perry\Codegen\AndroidXmlBackend) ? $backend->getColors() : [];

        return match ($this->target) {
            Target::MacOs => $this->compileSwift($source, $outputName),
            Target::IOS, Target::IOSSimulator => $this->compileIOS($source, $outputName),
            Target::Gtk4Linux => $this->compileGtk4($source, $outputName, $backend),
            Target::Windows => $this->compileWindows($source, $outputName, $backend),
            Target::Android => $this->compileAndroid($backend, $source, $outputName, $colors),
            Target::Web, Target::Wasm => $this->compileWeb($source, $outputName),
            default => CompileResult::failure("Compilation not supported for target: {$this->target->value}"),
        };
    }

    private function compileSwift(string $source, string $outputName): CompileResult
    {
        $swiftFile = $this->buildDir . '/' . $outputName . '.swift';
        $binaryPath = $this->buildDir . '/' . $outputName;
        $appBundle = $this->buildDir . '/' . ucfirst($outputName) . '.app';
        $appBinary = $appBundle . '/Contents/MacOS/' . ucfirst($outputName);

        file_put_contents($swiftFile, $source);

        // Create .app bundle structure
        $dirs = [
            $appBundle . '/Contents',
            $appBundle . '/Contents/MacOS',
            $appBundle . '/Contents/Resources',
        ];
        foreach ($dirs as $dir) {
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
        }

        // Generate Info.plist
        $plist = $this->generateInfoPlist(ucfirst($outputName));
        file_put_contents($appBundle . '/Contents/Info.plist', $plist);

        // Compile
        $cmd = sprintf(
            'swiftc -parse-as-library -o %s %s -framework Cocoa -framework SwiftUI -framework WebKit 2>&1',
            escapeshellarg($appBinary),
            escapeshellarg($swiftFile)
        );

        exec($cmd, $output, $exitCode);

        if ($exitCode === 0 && file_exists($appBinary)) {
            return CompileResult::success($appBundle, $swiftFile);
        }

        return CompileResult::failure(
            "Swift compilation failed:\n" . implode("\n", $output),
            $swiftFile
        );
    }

    private function generateInfoPlist(string $appName): string
    {
        return <<<XML
        <?xml version="1.0" encoding="UTF-8"?>
        <!DOCTYPE plist PUBLIC "-//Apple//DTD PLIST 1.0//EN" "http://www.apple.com/DTDs/PropertyList-1.0.dtd">
        <plist version="1.0">
        <dict>
            <key>CFBundleExecutable</key>
            <string>{$appName}</string>
            <key>CFBundleIdentifier</key>
            <string>com.perry.{$appName}</string>
            <key>CFBundleName</key>
            <string>{$appName}</string>
            <key>CFBundlePackageType</key>
            <string>APPL</string>
            <key>CFBundleShortVersionString</key>
            <string>1.0</string>
            <key>CFBundleVersion</key>
            <string>1</string>
            <key>NSPrincipalClass</key>
            <string>NSApplication</string>
            <key>NSHighResolutionCapable</key>
            <true/>
        </dict>
        </plist>
        XML;
    }

    private function compileIOS(string $source, string $outputName): CompileResult
    {
        $swiftFile = $this->buildDir . '/' . $outputName . '.swift';
        $outputFile = $this->buildDir . '/' . $outputName;

        file_put_contents($swiftFile, $source);

        $sdkPath = trim(shell_exec('xcrun --sdk iphonesimulator --show-sdk-path 2>/dev/null') ?: '');

        if (empty($sdkPath)) {
            return CompileResult::failure("iOS SDK not found. Install Xcode.", $swiftFile);
        }

        $cmd = sprintf(
            'swiftc -parse-as-library -o %s %s -sdk %s -target arm64-apple-ios17.0-simulator -framework UIKit -framework SwiftUI -framework WebKit 2>&1',
            escapeshellarg($outputFile),
            escapeshellarg($swiftFile),
            escapeshellarg($sdkPath)
        );

        exec($cmd, $output, $exitCode);

        if ($exitCode === 0 && file_exists($outputFile)) {
            return CompileResult::success($outputFile, $swiftFile);
        }

        return CompileResult::failure(
            "iOS compilation failed:\n" . implode("\n", $output),
            $swiftFile
        );
    }

    private function compileGtk4(string $source, string $outputName, CodegenBackend $backend): CompileResult
    {
        $xmlFile = $this->buildDir . '/' . $outputName . '.ui';
        $cFile = $this->buildDir . '/' . $outputName . '.c';
        $outputFile = $this->buildDir . '/' . $outputName;

        file_put_contents($xmlFile, $source);

        $cSource = $backend->generateMainActivity($outputName);
        file_put_contents($cFile, $cSource);

        $pkgConfig = trim(shell_exec('pkg-config --cflags --libs gtk4 2>/dev/null') ?: '');

        if (empty($pkgConfig)) {
            return CompileResult::failure("GTK4 not found. Install libgtk-4-dev.", $cFile);
        }

        $cmd = sprintf(
            'cc -o %s %s %s 2>&1',
            escapeshellarg($outputFile),
            escapeshellarg($cFile),
            $pkgConfig
        );

        exec($cmd, $output, $exitCode);

        if ($exitCode === 0 && file_exists($outputFile)) {
            return CompileResult::success($outputFile, $cFile);
        }

        return CompileResult::failure(
            "GTK4 compilation failed:\n" . implode("\n", $output),
            $cFile
        );
    }

    private function compileWindows(string $source, string $outputName, CodegenBackend $backend): CompileResult
    {
        $appXamlFile = $this->buildDir . '/App.xaml';
        $appCsFile = $this->buildDir . '/App.xaml.cs';
        $mainWindowXamlFile = $this->buildDir . '/MainWindow.xaml';
        $mainWindowCsFile = $this->buildDir . '/MainWindow.xaml.cs';
        $csprojFile = $this->buildDir . '/' . $outputName . '.csproj';
        $outputFile = $this->buildDir . '/' . $outputName . '.exe';

        $appXaml = $this->generateAppXaml();
        file_put_contents($appXamlFile, $appXaml);

        $appCs = $this->generateAppXamlCs();
        file_put_contents($appCsFile, $appCs);

        file_put_contents($mainWindowXamlFile, $source);

        $csSource = $backend->generateMainActivity($outputName);
        file_put_contents($mainWindowCsFile, $csSource);

        $csproj = $this->generateCsproj($outputName);
        file_put_contents($csprojFile, $csproj);

        $cmd = sprintf('dotnet build %s -o %s 2>&1', escapeshellarg($csprojFile), escapeshellarg($this->buildDir));

        exec($cmd, $output, $exitCode);

        // On macOS cross-compilation, WinExe produces .dll instead of .exe
        $dllFile = $this->buildDir . '/' . $outputName . '.dll';
        if ($exitCode === 0 && file_exists($outputFile)) {
            return CompileResult::success($outputFile, $mainWindowCsFile);
        }
        if ($exitCode === 0 && file_exists($dllFile)) {
            return CompileResult::success($dllFile, $mainWindowCsFile);
        }

        return CompileResult::failure(
            "Windows compilation failed. Install .NET SDK.\n" . implode("\n", $output),
            $mainWindowCsFile
        );
    }

    private function generateAppXaml(): string
    {
        return <<<XAML
<Application x:Class="PerryApp.App"
             xmlns="http://schemas.microsoft.com/winfx/2006/xaml/presentation"
             xmlns:x="http://schemas.microsoft.com/winfx/2006/xaml"
             StartupUri="MainWindow.xaml">
</Application>
XAML;
    }

    private function generateAppXamlCs(): string
    {
        return <<<CS
using System.Windows;

namespace PerryApp
{
    public partial class App : Application
    {
    }
}
CS;
    }

    private function compileAndroid(CodegenBackend $backend, string $source, string $outputName, array $colors = []): CompileResult
    {
        $sdkPath = $this->findAndroidSdk();
        if ($sdkPath === null) {
            $layoutDir = $this->buildDir . '/res/layout';
            if (!is_dir($layoutDir)) {
                mkdir($layoutDir, 0755, true);
            }
            file_put_contents($layoutDir . '/activity_main.xml', $source);
            return CompileResult::failure(
                "Android SDK not found. Set ANDROID_HOME environment variable.",
                $layoutDir . '/activity_main.xml'
            );
        }

        $projectDir = $this->buildDir . '/android';
        $appDir = $projectDir . '/app/src/main';
        $javaDir = $appDir . '/java/com/perry/' . $outputName;
        $resDir = $appDir . '/res';
        $layoutDir = $resDir . '/layout';
        $valuesDir = $resDir . '/values';
        $gradleWrapperDir = $projectDir . '/gradle/wrapper';

        foreach ([$javaDir, $layoutDir, $valuesDir, $gradleWrapperDir] as $dir) {
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
        }

        file_put_contents($layoutDir . '/activity_main.xml', $source);

        $this->writeColorsXml($valuesDir, $colors);
        $this->writeThemesXml($valuesDir);

        file_put_contents($projectDir . '/build.gradle', $this->generateAndroidRootBuildGradle());
        file_put_contents($projectDir . '/app/build.gradle', $this->generateAndroidAppBuildGradle($outputName));
        file_put_contents($projectDir . '/settings.gradle', $this->generateAndroidSettingsGradle());
        file_put_contents($projectDir . '/gradle.properties', "android.useAndroidX=true\norg.gradle.jvmargs=-Xmx2048m\n");
        file_put_contents($appDir . '/AndroidManifest.xml', $this->generateAndroidManifest($outputName));
        file_put_contents($javaDir . '/MainActivity.kt', $backend->generateMainActivity($outputName));
        file_put_contents($gradleWrapperDir . '/gradle-wrapper.properties', $this->generateGradleWrapperProperties());

        $cmd = "cd " . escapeshellarg($projectDir)
            . " && ANDROID_HOME=" . escapeshellarg($sdkPath)
            . " gradle assembleDebug --no-daemon 2>&1";

        exec($cmd, $output, $exitCode);

        $apkPath = $projectDir . '/app/build/outputs/apk/debug/app-debug.apk';
        $outputApk = $this->buildDir . '/' . $outputName . '.apk';

        if ($exitCode === 0 && file_exists($apkPath)) {
            copy($apkPath, $outputApk);
            return CompileResult::success($outputApk, $layoutDir . '/activity_main.xml');
        }

        return CompileResult::failure(
            "Android build failed:\n" . implode("\n", $output),
            $layoutDir . '/activity_main.xml'
        );
    }

    private function findAndroidSdk(): ?string
    {
        $env = getenv('ANDROID_HOME');
        if ($env !== false && is_dir($env)) {
            return $env;
        }

        $home = $_ENV['ANDROID_HOME'] ?? null;
        if ($home !== null && is_dir($home)) {
            return $home;
        }

        // Common paths
        $candidates = [
            '/Users/jay/Library/Android/sdk',
            '/opt/android-sdk',
            getenv('HOME') . '/Android/Sdk',
        ];

        foreach ($candidates as $path) {
            if (is_dir($path)) {
                return $path;
            }
        }

        return null;
    }

    private function generateAndroidRootBuildGradle(): string
    {
        return <<<'GRADLE'
plugins {
    id 'com.android.application' version '8.9.0' apply false
    id 'org.jetbrains.kotlin.android' version '2.0.21' apply false
}
GRADLE;
    }

    private function generateAndroidAppBuildGradle(string $outputName): string
    {
        $pkg = 'com.perry.' . $outputName;
        return <<<GRADLE
plugins {
    id 'com.android.application'
    id 'org.jetbrains.kotlin.android'
}

android {
    namespace '{$pkg}'
    compileSdk 34

    defaultConfig {
        applicationId '{$pkg}'
        minSdk 24
        targetSdk 34
        versionCode 1
        versionName '1.0'
    }

    buildTypes {
        release {
            minifyEnabled false
        }
    }

    compileOptions {
        sourceCompatibility JavaVersion.VERSION_17
        targetCompatibility JavaVersion.VERSION_17
    }

    kotlinOptions {
        jvmTarget = '17'
    }
}

repositories {
    google()
    mavenCentral()
}

dependencies {
    implementation 'androidx.core:core-ktx:1.12.0'
    implementation 'androidx.appcompat:appcompat:1.6.1'
    implementation 'com.google.android.material:material:1.11.0'
}
GRADLE;
    }

    private function generateAndroidManifest(string $outputName): string
    {
        $pkg = 'com.perry.' . $outputName;
        $label = ucfirst($outputName);
        return <<<XML
<?xml version="1.0" encoding="utf-8"?>
<manifest xmlns:android="http://schemas.android.com/apk/res/android">
    <application
        android:allowBackup="true"
        android:label="{$label}"
        android:supportsRtl="true"
        android:theme="@style/Theme.Perry">
        <activity
            android:name=".MainActivity"
            android:exported="true">
            <intent-filter>
                <action android:name="android.intent.action.MAIN" />
                <category android:name="android.intent.category.LAUNCHER" />
            </intent-filter>
        </activity>
    </application>
</manifest>
XML;
    }

    private function writeColorsXml(string $valuesDir, array $colors): void
    {
        $entries = '';
        foreach ($colors as $name => $hex) {
            $entries .= "    <color name=\"{$name}\">{$hex}</color>\n";
        }

        file_put_contents($valuesDir . '/colors.xml', <<<XML
<?xml version="1.0" encoding="utf-8"?>
<resources>
    <color name="perry_background">#000000</color>
    <color name="perry_surface">#1E1E1E</color>
    <color name="perry_text_primary">#FFFFFF</color>
    <color name="perry_text_secondary">#888888</color>
{$entries}</resources>
XML);
    }

    private function writeThemesXml(string $valuesDir): void
    {
        file_put_contents($valuesDir . '/themes.xml', <<<XML
<?xml version="1.0" encoding="utf-8"?>
<resources>
    <style name="Theme.Perry" parent="Theme.MaterialComponents.DayNight.NoActionBar">
        <item name="colorPrimary">#CC7700</item>
        <item name="colorPrimaryVariant">#AA6600</item>
        <item name="colorOnPrimary">@color/perry_text_primary</item>
        <item name="colorSecondary">#CC7700</item>
        <item name="android:windowBackground">@color/perry_background</item>
        <item name="android:statusBarColor">@color/perry_background</item>
        <item name="android:navigationBarColor">@color/perry_background</item>
    </style>
</resources>
XML);
    }

    private function generateGradleWrapperProperties(): string
    {
        return <<<'PROPS'
distributionBase=GRADLE_USER_HOME
distributionPath=wrapper/dists
distributionUrl=https\://services.gradle.org/distributions/gradle-8.4-bin.zip
zipStoreBase=GRADLE_USER_HOME
zipStorePath=wrapper/dists
PROPS;
    }

    private function generateAndroidSettingsGradle(): string
    {
        return <<<'SETTINGS'
pluginManagement {
    repositories {
        google()
        mavenCentral()
        gradlePluginPortal()
    }
}

include ':app'
SETTINGS;
    }

    private function compileWeb(string $source, string $outputName): CompileResult
    {
        $htmlFile = $this->buildDir . '/' . $outputName . '.html';
        file_put_contents($htmlFile, $source);

        return CompileResult::success($htmlFile, $htmlFile);
    }

    private function generateGtk4C(string $uiFile): string
    {
        $uiPath = addslashes($uiFile);

        return <<<'C'
        #include <gtk/gtk.h>

        static void activate(GtkApplication *app, gpointer user_data) {
            GtkBuilder *builder = gtk_builder_new_from_file("UI_PATH");
            GtkWidget *window = GTK_WIDGET(gtk_builder_get_object(builder, "main_window"));
            gtk_window_set_application(GTK_WINDOW(window), app);
            gtk_widget_set_visible(window, TRUE);
            g_object_unref(builder);
        }

        int main(int argc, char *argv[]) {
            GtkApplication *app = gtk_application_new("com.perry.app", G_APPLICATION_DEFAULT_FLAGS);
            g_signal_connect(app, "activate", G_CALLBACK(activate), NULL);
            int status = g_application_run(G_APPLICATION(app), argc, argv);
            g_object_unref(app);
            return status;
        }
        C;
    }

    private function generateWpfCSharp(string $appName, string $xamlFile): string
    {
        return <<<'CS'
        using System.Windows;

        namespace PerryApp {
            public partial class MainWindow : Window {
                public MainWindow() {
                    InitializeComponent();
                }
            }

            public class App : Application {
                protected override void OnStartup(StartupEventArgs e) {
                    base.OnStartup(e);
                    var window = new MainWindow();
                    window.Show();
                }
            }

            public class Program {
                [System.STAThread]
                public static void Main() {
                    var app = new App();
                    app.Run();
                }
            }
        }
        CS;
    }

    private function generateCsproj(string $appName): string
    {
        return <<<XML
        <Project Sdk="Microsoft.NET.Sdk">
            <PropertyGroup>
                <OutputType>WinExe</OutputType>
                <TargetFramework>net10.0-windows</TargetFramework>
                <TargetPlatformVersion>10.0.19041.0</TargetPlatformVersion>
                <EnableWindowsTargeting>true</EnableWindowsTargeting>
                <UseWPF>true</UseWPF>
                <RootNamespace>PerryApp</RootNamespace>
                <AssemblyName>{$appName}</AssemblyName>
                <Nullable>enable</Nullable>
            </PropertyGroup>
        </Project>
        XML;
    }
}
