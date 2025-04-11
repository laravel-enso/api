<?php

namespace LaravelEnso\Api\Contracts;

use LaravelEnso\Api\Enums\Method;

interface Endpoint
{
    public function method(): Method;

    public function url(): string;

    public function body(): string|array;
}
