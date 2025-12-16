<?php

namespace LaravelEnso\Api\Contracts;

interface UsesBasicAuth
{
    public function username(): string;

    public function password(): string;
}
