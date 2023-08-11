<?php

declare(strict_types=1);

namespace Tests\Unit\BasicMigrations;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddEmailToUsersTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('users') === false) {
            return;
        }

        Schema::table('users', static function (Blueprint $table) {
            if (Schema::hasColumn('users', 'email') === false) {
                $table->string('email')->unique();
            }
        });
    }
}
