<?php

namespace LaravelEnso\Api\Enums;

enum Method: string
{
    case GET = 'get';
    case POST = 'post';
    case PUT = 'put';
    case DELETE = 'delete';
}
