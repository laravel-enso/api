<?php

namespace LaravelEnso\Api;

use Illuminate\Support\Collection;
use LaravelEnso\Api\Exceptions\Argument;

abstract class Resource
{
    abstract public function toArray(): array;

    public function resolve(): array
    {
        if ($this->needsValidation()) {
            $this->validate();
        }

        $value = fn ($argument) => $argument instanceof self
            ? $argument->resolve()
            : $argument;

        return array_map($value, $this->toArray());
    }

    public static function collection(Collection $collection): array
    {
        $resource = fn ($item) => (new static($item))->resolve();

        return $collection->map($resource)->toArray();
    }

    public function toJson(): string
    {
        return json_encode($this->toArray());
    }

    protected function mandatoryAttributes(): array
    {
        return [];
    }

    private function validate()
    {
        $missing = array_diff($this->mandatoryAttributes(), array_keys($this->toArray()));

        if (count($missing) > 0) {
            throw Argument::mandatory($missing);
        }
    }

    private function needsValidation(): bool
    {
        return count($this->mandatoryAttributes()) > 0;
    }
}
