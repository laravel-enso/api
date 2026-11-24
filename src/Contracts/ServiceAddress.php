<?php

namespace LaravelEnso\Api\Contracts;

interface ServiceAddress
{
    public function operation(): string;

    public function url(): string;

    public function params(): array;
}
