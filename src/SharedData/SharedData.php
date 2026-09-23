<?php

namespace Witify\Support\SharedData;

use Illuminate\Support\Arr;

class SharedData
{
    /** @var array<string, mixed> */
    private array $data = [];

    // Dynamic data that can be set by the user
    /** @var array<string, callable(): mixed> */
    private array $callbacks = [];

    public function put(string $key, mixed $value): void
    {
        if (is_callable($value)) {
            $this->callbacks[$key] = $value;
        } else {
            Arr::set($this->data, $key, $value);
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function set(array $data): void
    {
        $this->data = $data;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return Arr::get($this->data, $key, $default);
    }

    /**
     * @return array<string, mixed>
     */
    public function all(): array
    {
        foreach ($this->callbacks as $key => $callback) {
            $item = $callback();
            if (! empty($item)) {
                Arr::set($this->data, $key, $item);
            }
        }

        return $this->data;
    }
}
