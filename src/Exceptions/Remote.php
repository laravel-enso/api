<?php

namespace LaravelEnso\Api\Exceptions;

use LaravelEnso\Helpers\Exceptions\EnsoException;

class Remote extends EnsoException
{
    public static function error(string $remoteError): self
    {
        return new static(__('Web service remote error :error', [
            'error' => $remoteError,
        ]));
    }
}
