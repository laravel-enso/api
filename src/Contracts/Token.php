<?php

namespace LaravelEnso\Api\Contracts;

use LaravelEnso\Api\Enums\Authorization;

interface Token
{
    public function type(): Authorization;

    public function auth(): self;

    public function current(): string;
}
