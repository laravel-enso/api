<?php

namespace LaravelEnso\Api\Contracts;

interface SoapEndpoint
{
    public function wsdl(): ?string;

    public function operation(): string;

    public function arguments(): array;

    public function options(): array;
}
