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

    // public static function needsAuth(int $code): bool
    // {
    //     return in_array($code, [self::Unauthorized, self::Forbidden]);
    // }
    public function needsAuth(): bool
    {
        return match ($this) {
            self::OK => false,
            self::Created => false,
            self::Unauthorized => true,
            self::Forbidden => true,
            self::NotFound => false,
            self::UnprocessableEntity => false,
        };
    }
}
