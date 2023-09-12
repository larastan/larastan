<?php

declare(strict_types=1);

namespace Tests\Unit\BasicMigrations;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAddress2ToUsersTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('users') === true) {
            Schema::table('users', static function (Blueprint $table) {
                if (Schema::hasColumn('users', 'address2') === true) {
                    return;
                }

                $table->string('address2')->nullable();
            });
        }
    }
}
