<?php

namespace LaravelEnso\Api\Enums;

enum ResponseCode: int
{
    case OK = 200;
    case Created = 201;

    case Unauthorized = 401;
    case Forbidden = 403;

    case NotFound = 404;
    case UnprocessableEntity = 422;

    public function needsAuth(): bool
    {
        return in_array($this, [self::Unauthorized, self::Forbidden]);
    }
}
