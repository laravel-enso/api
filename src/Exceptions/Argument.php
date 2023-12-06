<?php

namespace LaravelEnso\Api\Exceptions;

use InvalidArgumentException;

class Argument extends InvalidArgumentException
{
    public static function mandatory(array $missing): self
    {
        return new static(__('Mandatory attribute(s) missing ":missing"', [
            'missing' => implode(',', $missing),
        ]));
    }

    public static function unknown(array $unknown): self
    {
        return new static(__('Unknown attribute(s) provided ":unknown"', [
            'unknown' => implode(',', $unknown),
        ]));
    }
}
