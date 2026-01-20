<?php

namespace LaravelEnso\Api\Upgrades;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use LaravelEnso\Upgrade\Contracts\MigratesTable;
use LaravelEnso\Upgrade\Helpers\Table;

class AddPayloadToApiLogs implements MigratesTable
{
    public function isMigrated(): bool
    {
        return Table::hasColumn('api_logs', 'payload');
    }

    public function migrateTable(): void
    {
        Schema::table('api_logs', function (Blueprint $table) {
            $table->json('payload')->nullable()->after('method');
        });
    }
}
