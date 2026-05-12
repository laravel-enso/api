<?php

namespace LaravelEnso\Api\Enums;

use Illuminate\Http\Request;
use LaravelEnso\Enums\Contracts\Frontend;
use LaravelEnso\Enums\Contracts\Mappable;
use LaravelEnso\Enums\Contracts\Select;
use LaravelEnso\Enums\Traits\Select as Options;

enum Method: int implements Frontend, Mappable, Select
{
    use Options;

    case GET = 1;
    case POST = 2;
    case PUT = 3;
    case PATCH = 4;
    case DELETE = 5;
    case OPTIONS = 6;
    case HEAD = 7;

    public static function fromRequest(Request $request): self
    {
        return match ($request->method()) {
            'GET' => self::GET,
            'POST' => self::POST,
            'PUT' => self::PUT,
            'PATCH' => self::PATCH,
            'DELETE' => self::DELETE,
            'OPTIONS' => self::OPTIONS,
            'HEAD' => self::HEAD,
        };
    }

    public function clientMethod(): string
    {
        return strtolower($this->name);
    }

    public function map(): string
    {
        return $this->name;
    }

    public static function registerBy(): string
    {
        return 'apiLogMethod';
    }
}
