<?php

namespace LaravelEnso\Api\Exceptions;

use Illuminate\Support\Str;
use LaravelEnso\Api\Operation;
use LaravelEnso\Helpers\Exceptions\EnsoException;

class Service extends EnsoException
{
    public static function disabled(Operation $operation): self
    {
        $service = Str::of($operation::class)
            ->explode('\\')
            ->splice(1, 1)
            ->first();

        return new static(__(':service WebService is disabled', [
            'service' => $service,
        ]));
    }
}
