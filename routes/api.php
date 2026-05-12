<?php

use Illuminate\Support\Facades\Route;
use LaravelEnso\Api\Http\Controllers\Log\ExportExcel;
use LaravelEnso\Api\Http\Controllers\Log\InitTable;
use LaravelEnso\Api\Http\Controllers\Log\TableData;

Route::middleware(['api', 'auth', 'core'])
    ->prefix('api/system/apiLogs')
    ->as('system.apiLogs.')
    ->group(function () {
        Route::get('initTable', InitTable::class)->name('initTable');
        Route::get('tableData', TableData::class)->name('tableData');
        Route::get('exportExcel', ExportExcel::class)->name('exportExcel');
    });
