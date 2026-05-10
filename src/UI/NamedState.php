<?php

declare(strict_types=1);

namespace Perry\UI;

/**
 * NamedState — string-keyed state management with batch updates and watchers.
 *
 * Optional singleton wrapper around the existing State system.
 * All existing code continues to work unchanged.
 *
 * Usage:
 *   $ns = NamedState::instance();
 *   $ns->create('count', 0);
 *   $ns->set('count', 5);
 *   $value = $ns->get('count');   // 5
 *   $ns->update(['count' => 10, 'name' => 'hello']);  // batch
 *   $ns->watch('count', fn($v) => ...);
 *
 *   // For codegen compatibility:
 *   $binding = $ns->binding('count');
 *   // equivalent to: new Binding('count', 0)
 */
final class NamedState
{
    private static ?self $instance = null;

    private State $state;

    /** @var array<string, StateId> name => StateId */
    private array $keys = [];

    /** @var array<string, mixed> name => initialValue */
    private array $initialValues = [];

    private function __construct()
    {
        $this->state = new State();
    }

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
     * Create a named state variable.
     *
     * @return StateId The underlying state ID (can be used with State directly).
     */
    public function create(string $name, mixed $initialValue = null): StateId
    {
        if (isset($this->keys[$name])) {
            throw new \RuntimeException("NamedState key '{$name}' already exists");
        }
        $id = $this->state->create($initialValue);
        $this->keys[$name] = $id;
        $this->initialValues[$name] = $initialValue;
        return $id;
    }

    /**
     * Check if a named key exists.
     */
    public function has(string $name): bool
    {
        return isset($this->keys[$name]);
    }

    /**
     * Get value by name.
     */
    public function get(string $name): mixed
    {
        $id = $this->keys[$name] ?? null;
        if ($id === null) {
            throw new \RuntimeException("NamedState key '{$name}' not found");
        }
        return $this->state->get($id);
    }

    /**
     * Set value by name.
     */
    public function set(string $name, mixed $value): void
    {
        $id = $this->keys[$name] ?? null;
        if ($id === null) {
            throw new \RuntimeException("NamedState key '{$name}' not found");
        }
        $this->state->set($id, $value);
    }

    /**
     * Batch update multiple state variables at once.
     * Notifies watchers only once per changed key (via underlying State::set).
     */
    public function update(array $kvPairs): void
    {
        foreach ($kvPairs as $name => $value) {
            $id = $this->keys[$name] ?? null;
            if ($id === null) {
                throw new \RuntimeException("NamedState key '{$name}' not found in batch update");
            }
            $this->state->set($id, $value);
        }
    }

    /**
     * Watch a named key for changes.
     */
    public function watch(string $name, callable $callback): void
    {
        $id = $this->keys[$name] ?? null;
        if ($id === null) {
            throw new \RuntimeException("NamedState key '{$name}' not found");
        }
        $this->state->subscribe($id, $callback);
    }

    /**
     * Get the underlying State object.
     */
    public function state(): State
    {
        return $this->state;
    }

    /**
     * Get the StateId for a named key.
     */
    public function stateId(string $name): ?StateId
    {
        return $this->keys[$name] ?? null;
    }

    /**
     * Get initial value for a named key.
     */
    public function initialValue(string $name): mixed
    {
        return $this->initialValues[$name] ?? null;
    }

    /**
     * Create a Binding for codegen compatibility.
     * This lets NamedState work seamlessly with the existing codegen pipeline.
     */
    public function binding(string $name): Binding
    {
        $initial = $this->initialValues[$name] ?? null;
        return new Binding($name, $initial);
    }

    /**
     * Return all registered key names.
     * @return string[]
     */
    public function names(): array
    {
        return array_keys($this->keys);
    }
}
