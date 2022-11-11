<?php

declare(strict_types=1);

namespace Database\Migrations;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('users', static function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('stringButInt');
            $table->float('floatButRoundedDecimalString');
            $table->json('allowed_ips');
            $table->json('meta');
            $table->json('options');
            $table->json('properties');
            $table->boolean('blocked');
            $table->unknownColumnType('unknown_column');
            $table->integer('balance');
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
        });
    }
}
