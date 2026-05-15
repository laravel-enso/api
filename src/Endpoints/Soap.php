<?php

namespace LaravelEnso\Api\Endpoints;

use LaravelEnso\Api\Contracts\SoapEndpoint;
use LaravelEnso\Api\Enums\Method;

abstract class Soap implements SoapEndpoint
{
    public function method(): Method
    {
        return Method::POST;
    }

    public function url(): string
    {
        return $this->wsdl()
            ?? $this->options()['location']
            ?? $this->operation();
    }

    public function body(): string|array
    {
        return $this->arguments();
    }

    public function wsdl(): ?string
    {
        return null;
    }

    public function options(): array
    {
        return [];
    }
}
