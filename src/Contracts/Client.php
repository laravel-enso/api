<?php

namespace LaravelEnso\Api\Contracts;

use Illuminate\Http\Client\Response;
use LaravelEnso\Api\SoapResponse;

interface Client
{
    public function call(): Response|SoapResponse;

    public function tries(): int;
}
