<?php

namespace LaravelEnso\Api\Enums;

enum Method: string
{
    case get = 'get';
    case post = 'post';
    case put = 'put';
    case delete = 'delete';
}
