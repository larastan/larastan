<?php

declare(strict_types=1);

namespace Tests\Unit\RenameMigrations;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RenameUsersTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('users', static function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name')->nullable();
            $table->string('email')->unique();
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->rename('accounts');
        });
    }
}
