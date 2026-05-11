<?php

declare(strict_types=1);

namespace Perry\UI\Frontend;

/**
 * TemplateRegistry — stores reusable HTML DSL template definitions.
 *
 * Templates are defined via <template id="xxx">...</template> in the HTML DSL
 * and instantiated via <use template="xxx" param="value" />.
 */
final class TemplateRegistry
{
    private static ?TemplateRegistry $instance = null;

    /** @var array<string, string> templateId => bodyHtml */
    private array $templates = [];

    public static function instance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public static function reset(): void
    {
        self::$instance = null;
    }

    public function register(string $id, string $bodyHtml): void
    {
        $id = trim($id);
        if ($id === '') {
            throw new \RuntimeException('Template id must not be empty');
        }
        if (isset($this->templates[$id])) {
            throw new \RuntimeException("Template '{$id}' is already registered");
        }
        $this->templates[$id] = $bodyHtml;
    }

    public function get(string $id): string
    {
        if (!isset($this->templates[$id])) {
            throw new \RuntimeException("Template '{$id}' is not defined");
        }
        return $this->templates[$id];
    }

    public function has(string $id): bool
    {
        return isset($this->templates[$id]);
    }

    public function names(): array
    {
        return array_keys($this->templates);
    }

    public function clear(): void
    {
        $this->templates = [];
    }
}
