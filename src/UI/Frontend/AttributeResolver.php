<?php

declare(strict_types=1);

namespace Perry\UI\Frontend;

use Perry\UI\Action;
use Perry\UI\ActionRegistry;
use Perry\UI\Binding;
use Perry\UI\NamedState;
use Perry\UI\Styling\Style;

/**
 * AttributeResolver resolves HTML DSL attributes to Perry framework objects.
 *
 * Handles:
 *  - bind="name"       → Binding (via NamedState)
 *  - style="name"      → Style object (from pre-configured styles array)
 *  - onclick="name"    → Action (via ActionRegistry) + action name string
 *  - onchange="name"   → Action (for Slider/TextInput/Toggle)
 *  - ontoggle="name"   → Action (for Toggle)
 *  - numeric attributes → typed values (min, max, step)
 *
 * This is NOT a singleton — each HtmlFrontend instance has its own
 * resolver bound to its style map.
 */
final class AttributeResolver
{
    /** @var array<string, Style> */
    private array $styles = [];

    /**
     * @param array<string, Style> $styles Named styles available for reference
     */
    public function __construct(array $styles = [])
    {
        $this->styles = $styles;
    }

    /**
     * Set a named style.
     */
    public function addStyle(string $name, Style $style): void
    {
        $this->styles[$name] = $style;
    }

    /**
     * Batch-set styles.
     *
     * @param array<string, Style> $styles
     */
    public function addStyles(array $styles): void
    {
        foreach ($styles as $name => $style) {
            $this->styles[$name] = $style;
        }
    }

    /**
     * Resolve a style attribute value to a Style object.
     *
     * @param  string|null $name The style name from the HTML attribute
     * @return Style|null        The Style object, or null if not found / not set
     */
    public function resolveStyle(?string $name): ?Style
    {
        if ($name === null || $name === '') {
            return null;
        }
        return $this->styles[$name] ?? throw new \RuntimeException(
            "Style '{$name}' is not defined. Did you forget to addStyle() it?"
        );
    }

    /**
     * Resolve a bind attribute to a Binding object.
     *
     * Looks up the named state key. The state key must have been
     * pre-registered (e.g., via a <bind> element or NamedState::create()).
     *
     * @param  string|null $name  The state key name (e.g., "display")
     * @return Binding|null       The Binding object, or null if not set
     */
    public function resolveBinding(?string $name): ?Binding
    {
        if ($name === null || $name === '') {
            return null;
        }
        $ns = NamedState::instance();
        if (!$ns->has($name)) {
            throw new \RuntimeException(
                "State key '{$name}' is not defined. "
                . "Did you forget a <bind name=\"{$name}\" /> element?"
            );
        }
        return $ns->binding($name);
    }

    /**
     * Resolve an action name (onclick, onchange, etc.) to the registered Action.
     *
     * Returns [Action|null, string|null] — the Action object (for widget constructors
     * that need it) and the action name string (for Widget::actionName()).
     *
     * @param  string|null $name The action name (e.g., "clear")
     * @return array{0: Action|null, 1: string|null}  [Action, actionName]
     */
    public function resolveAction(?string $name): array
    {
        if ($name === null || $name === '') {
            return [null, null];
        }
        $action = ActionRegistry::get($name);
        if ($action === null) {
            throw new \RuntimeException(
                "Action '{$name}' is not registered. "
                . "Did you forget ActionRegistry::register('{$name}', \$action)?"
            );
        }
        return [$action, $name];
    }

    /**
     * Parse a numeric HTML attribute value.
     *
     * @param  string|null $value   Raw attribute value
     * @param  float       $default Fallback if null/empty
     * @return float
     */
    public static function parseFloat(?string $value, float $default = 0.0): float
    {
        if ($value === null || $value === '') {
            return $default;
        }
        return (float) $value;
    }

    /**
     * Parse a boolean HTML attribute value.
     *
     * @param  string|null $value   Raw attribute value
     * @param  bool        $default Fallback if null/empty
     * @return bool
     */
    public static function parseBool(?string $value, bool $default = false): bool
    {
        if ($value === null || $value === '') {
            return $default;
        }
        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }
}
