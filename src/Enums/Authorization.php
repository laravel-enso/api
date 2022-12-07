<?php

namespace LaravelEnso\Api\Enums;

enum Authorization: string
{
    case Basic = 'Basic';
    case Bearer = 'Bearer';
}
