<?php

namespace LaravelEnso\Api\Enums;

use LaravelEnso\Enums\Contracts\Frontend;
use LaravelEnso\Enums\Contracts\Mappable;
use LaravelEnso\Enums\Contracts\Select;
use LaravelEnso\Enums\Traits\Select as Options;

enum Direction: int implements Frontend, Mappable, Select
{
    use Options;

    case Inbound = 1;
    case Outbound = 2;

    public function map(): string
    {
        return $this->name;
    }

    public static function registerBy(): string
    {
        return 'apiLogDirections';
    }
}
