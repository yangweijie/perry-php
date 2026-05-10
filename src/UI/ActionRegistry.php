<?php

declare(strict_types=1);

namespace Perry\UI;

/**
 * ActionRegistry — named action registry for declarative event dispatch.
 *
 * Maps string names to Action objects, allowing widgets to reference
 * actions by name rather than carrying Action objects directly.
 *
 * Key benefits:
 * 1. Multiple widgets can share the same named action
 * 2. Backends can generate a single function body for shared actions
 * 3. PHP-side dispatch for live preview and testing
 *
 * Usage:
 *   ActionRegistry::register('clear', $clearAction);
 *   $action = ActionRegistry::get('clear');
 *   ActionRegistry::dispatch('clear');  // PHP-side execution
 *
 * All existing code continues to work unchanged.
 */
final class ActionRegistry
{
    private static ?self $instance = null;

    /** @var array<string, Action> */
    private array $actions = [];

    private function __construct() {}

    public static function instance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Reset the singleton (primarily for testing).
     */
    public static function reset(): void
    {
        self::$instance = null;
    }

    /**
     * Register an action by name.
     */
    public static function register(string $name, Action $action): void
    {
        self::instance()->actions[$name] = $action;
    }

    /**
     * Get a registered action by name.
     */
    public static function get(string $name): ?Action
    {
        return self::instance()->actions[$name] ?? null;
    }

    /**
     * Check if an action is registered.
     */
    public static function has(string $name): bool
    {
        return isset(self::instance()->actions[$name]);
    }

    /**
     * Get all registered action names.
     * @return string[]
     */
    public static function names(): array
    {
        return array_keys(self::instance()->actions);
    }

    /**
     * PHP-side dispatch: execute a named action's closure directly.
     * Only works with Closure-type actions.
     *
     * @throws \RuntimeException if action not found or not dispatchable.
     */
    public static function dispatch(string $name): mixed
    {
        $action = self::get($name);
        if ($action === null) {
            throw new \RuntimeException("Action '{$name}' not registered");
        }
        if ($action->closure === null) {
            throw new \RuntimeException("Action '{$name}' has no closure, cannot dispatch in PHP");
        }
        return ($action->closure)();
    }
}
