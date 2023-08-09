<?php

declare(strict_types=1);

namespace Tests\Unit\MigrationsUsingDropColumn;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAddDropToUsersTable extends Migration
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
            $table->string('country')->nullable();
            $table->string('city')->nullable();
            $table->timestamps();
        });

        Schema::table('users', static function (Blueprint $table) {
            $table->dropColumn(['country', 'city']);
        });
    }
}
