<?php

namespace LaravelEnso\Api\Enums;

enum Call: int
{
    case Inbound = 1;
    case Outbound = 2;
}
