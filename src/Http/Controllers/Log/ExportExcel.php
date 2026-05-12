<?php

namespace LaravelEnso\Api\Http\Controllers\Log;

use Illuminate\Routing\Controller;
use LaravelEnso\Api\Tables\Builders\Log;
use LaravelEnso\Tables\Traits\Excel;

class ExportExcel extends Controller
{
    use Excel;

    protected string $tableClass = Log::class;
}
