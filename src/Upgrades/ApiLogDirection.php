<?php

namespace LaravelEnso\Api\Upgrades;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use LaravelEnso\Upgrade\Contracts\MigratesTable;
use LaravelEnso\Upgrade\Helpers\Table;

class ApiLogDirection implements MigratesTable
{
    public function isMigrated(): bool
    {
        return Table::exists('api_logs')
            && Table::hasColumn('api_logs', 'direction')
            && ! Table::hasColumn('api_logs', 'type');
    }

    public function migrateTable(): void
    {
        Schema::table('api_logs', function (Blueprint $table) {
            $table->renameColumn('type', 'direction');
        });

        Schema::table('api_logs', function (Blueprint $table) {
            $table->unsignedTinyInteger('direction')->change();
        });

        if (! Table::hasIndex('api_logs', 'api_logs_direction_created_at_index')) {
            Schema::table('api_logs', fn (Blueprint $table) => $table->index(['direction', 'created_at']));
        }
    }
}
