<?php

declare(strict_types=1);

namespace Tests\Rules\Data;

use Illuminate\Support\Facades\Schema;

function schemaChecksOutsideOfMigrations(): bool
{
    if (! Schema::hasTable('users')) {
        return false;
    }

    if (! Schema::hasColumn('users', 'name')) {
        return false;
    }

    return ! Schema::connection('mysql')->hasTable('users');
}
