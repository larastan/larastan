<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('users')) {
            Schema::create('users', function (Blueprint $table) {
                $table->id();
            });
        }

        if (Schema::hasColumn('users', 'name')) {
            return;
        }

        if (! Schema::hasColumns('users', ['name', 'email'])) {
            return;
        }

        if (! Schema::connection('mysql')->hasTable('users')) {
            return;
        }

        $schema = Schema::connection('mysql');

        if ($schema->hasColumn('users', 'email')) {
            return;
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('users')) {
            Schema::drop('users');
        }
    }
};
