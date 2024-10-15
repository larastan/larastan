<?php

declare(strict_types=1);

namespace Tests\Unit\MigrationWithSchemaConnection;

use Illuminate\Database\Connection;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUsersTable extends Migration
{
    protected $connection = 'foo';

    public function up(): void
    {
        Schema::create('teams', static function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('team')->nullable();
            $table->string('owner_email')->unique();
            $table->timestamps();
        });

        Schema::connection('bar')->create('users', static function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name')->nullable();
            $table->string('email')->unique();
            $table->timestamps();
        });

        Schema::connection('baz')->create('users', static function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name')->nullable();
            $table->string('email')->unique();
            $table->timestamps();
        });

        Schema::connection('bar')->create('teams', static function (Blueprint $table) {
            $table->bigIncrements('id');
        });

        Schema::create('users', static function (Blueprint $table) {
            $table->bigIncrements('id');
        });
    }
}
