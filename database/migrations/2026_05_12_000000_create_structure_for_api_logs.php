<?php

use LaravelEnso\Migrator\Database\Migration;

return new class() extends Migration {
    protected array $permissions = [
        ['name' => 'system.apiLogs.index', 'description' => 'Show index for API logs', 'is_default' => false],
        ['name' => 'system.apiLogs.initTable', 'description' => 'Init table for API logs', 'is_default' => false],
        ['name' => 'system.apiLogs.tableData', 'description' => 'Get table data for API logs', 'is_default' => false],
        ['name' => 'system.apiLogs.exportExcel', 'description' => 'Export excel for API logs', 'is_default' => false],
    ];

    protected array $menu = [
        'name' => 'API Logs', 'icon' => 'code', 'route' => 'system.apiLogs.index', 'order_index' => 153, 'has_children' => false,
    ];

    protected ?string $parentMenu = 'System';
};
