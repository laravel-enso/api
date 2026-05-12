<?php

namespace LaravelEnso\Api\Http\Controllers\Log;

use Illuminate\Routing\Controller;
use LaravelEnso\Api\Tables\Builders\Log;
use LaravelEnso\Tables\Traits\Data;

class TableData extends Controller
{
    use Data;

    protected string $tableClass = Log::class;
}
