<?php

namespace LaravelEnso\Api\Contracts;

use LaravelEnso\Api\Enums\Methods;

interface Endpoint
{
    public function method(): Methods;

    public function url(): string;

    public function body(): string|array;
}
