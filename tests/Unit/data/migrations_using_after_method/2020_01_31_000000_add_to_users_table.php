<?php

declare(strict_types=1);

namespace Tests\Unit\MigrationsUsingAfterMethod;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterUsersTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', static function (Blueprint $table) {
            $table->after('name', function (Blueprint $table) {
                $table->string('email')->unique();
            });
        });
    }
}
