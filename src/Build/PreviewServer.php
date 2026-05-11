<?php

declare(strict_types=1);

namespace Perry\Build;

use Perry\App;
use Perry\UI\Frontend\HtmlFrontend;
use Perry\UI\Widget;

class PreviewServer
{
    private string $outputDir;
    private int $serverPid = 0;
    private string $routerFile;
    private string $versionFile;

    public function __construct(
        private readonly string $sourceFile,
        private readonly int $port = 8080,
    ) {
        if (!file_exists($this->sourceFile)) {
            throw new \RuntimeException("Source file not found: {$this->sourceFile}");
        }

        $this->outputDir = sys_get_temp_dir() . '/perry-' . bin2hex(random_bytes(4));
        $this->routerFile = $this->outputDir . '/router.php';
        $this->versionFile = $this->outputDir . '/__perry_version';
    }

    public function serve(): never
    {
        $this->prepareOutputDir();
        $this->generate();
        $this->startPhpServer();

        $lastMtime = filemtime($this->sourceFile);

        echo "\n  Perry Preview Server\n";
        echo "  ─────────────────────\n";
        echo "  Source: {$this->sourceFile}\n";
        echo "  URL:    http://localhost:{$this->port}\n";
        echo "  Watching for changes... (Ctrl+C to stop)\n\n";

        while (true) {
            sleep(1);
            clearstatcache(true, $this->sourceFile);
            $mtime = filemtime($this->sourceFile);

            if ($mtime > $lastMtime) {
                $lastMtime = $mtime;
                echo '  ↻ Source changed at ' . date('H:i:s') . ", regenerating...\n";
                $this->generate();
            }
        }
    }

    private function prepareOutputDir(): void
    {
        if (!is_dir($this->outputDir)) {
            if (!mkdir($this->outputDir, 0755, true) && !is_dir($this->outputDir)) {
                throw new \RuntimeException("Failed to create output directory: {$this->outputDir}");
            }
        }
    }

    private function generate(): void
    {
        $html = $this->buildHtml();
        $html = $this->injectLiveReload($html);
        file_put_contents($this->outputDir . '/index.html', $html);

        file_put_contents($this->versionFile, (string) time());
        file_put_contents($this->routerFile, $this->buildRouter());
    }

    private function buildHtml(): string
    {
        $ext = strtolower(pathinfo($this->sourceFile, \PATHINFO_EXTENSION));

        $root = match ($ext) {
            'html' => (new HtmlFrontend())->parse(file_get_contents($this->sourceFile)),
            'php' => $this->loadPhpWidget(),
            default => throw new \RuntimeException("Unsupported file type: .{$ext}"),
        };

        if (!$root instanceof Widget) {
            throw new \RuntimeException('Source file must produce a valid Widget');
        }

        $app = new App(Target::Web);
        $app->setRoot($root);

        return $app->generateCode('html');
    }

    private function loadPhpWidget(): Widget
    {
        if (function_exists('opcache_invalidate')) {
            opcache_invalidate($this->sourceFile, true);
        }

        $result = require $this->sourceFile;

        if ($result instanceof Widget) {
            return $result;
        }

        throw new \RuntimeException(
            'PHP source must return a Widget instance, got ' . get_debug_type($result)
        );
    }

    private function injectLiveReload(string $html): string
    {
        $script = <<<'JS'
<script>
(function(){var n=null;
function t(){fetch('/__perry_version?'+Date.now()).then(function(r){return r.text()}).then(function(v){if(n===null){n=v;return}
if(n!==v){location.reload()}}).catch(function(){})}
setInterval(t,1000)})();
</script>
JS;
        $pos = strrpos($html, '</body>');
        if ($pos !== false) {
            return substr($html, 0, $pos) . $script . "\n</body>";
        }
        return $html . "\n{$script}\n";
    }

    private function buildRouter(): string
    {
        return <<<'PHP'
<?php
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if ($uri === '/__perry_version') {
    header('Content-Type: text/plain');
    header('Cache-Control: no-cache, must-revalidate');
    readfile(__DIR__ . '/__perry_version');
    return true;
}
return false;
PHP;
    }

    private function startPhpServer(): void
    {
        $cmd = sprintf(
            'php -S 0.0.0.0:%d -t %s %s > /dev/null 2>&1 & echo $!',
            $this->port,
            escapeshellarg($this->outputDir),
            escapeshellarg($this->routerFile)
        );

        $output = [];
        exec($cmd, $output);
        $this->serverPid = (int) ($output[0] ?? 0);

        if ($this->serverPid <= 0) {
            throw new \RuntimeException('Failed to start PHP built-in server');
        }

        register_shutdown_function(function (): void {
            if ($this->serverPid > 0) {
                @exec('kill ' . $this->serverPid . ' 2>/dev/null');
            }
        });

        usleep(300_000);
    }
}
