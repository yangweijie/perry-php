<?php

declare(strict_types=1);

namespace Perry\UI;

final class State
{
    /** @var array<string, mixed> */
    private array $store = [];

    /** @var array<string, list<callable>> */
    private array $listeners = [];

    public function create(mixed $initialValue): StateId
    {
        $id = StateId::next();
        $this->store[$id->id] = $initialValue;
        return $id;
    }

    public function get(StateId $id): mixed
    {
        return $this->store[$id->id] ?? null;
    }

    public function set(StateId $id, mixed $value): void
    {
        $old = $this->store[$id->id] ?? null;
        $this->store[$id->id] = $value;

        if ($old !== $value) {
            $this->notify($id);
        }
    }

    public function subscribe(StateId $id, callable $callback): void
    {
        $this->listeners[$id->id][] = $callback;
    }

    private function notify(StateId $id): void
    {
        foreach ($this->listeners[$id->id] ?? [] as $callback) {
            $callback($this->store[$id->id]);
        }
    }
}
