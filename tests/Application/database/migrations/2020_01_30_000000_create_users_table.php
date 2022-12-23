<?php

declare(strict_types=1);

namespace Database\Migrations;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

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
            $table->json('favorites');
            $table->string('secret');
            $table->boolean('blocked');
            $table->unknownColumnType('unknown_column');
            $table->rememberToken();
            $table->enum('enum_status', ['active', 'inactive']);

            // Testing property casts
            $table->integer('int');
            $table->integer('integer');
            $table->float('real');
            $table->float('float');
            $table->double('double');
            $table->decimal('decimal');
            $table->string('string');
            $table->boolean('bool');
            $table->boolean('boolean');
            $table->json('object');
            $table->json('array');
            $table->json('json');
            $table->json('collection');
            $table->date('date');
            $table->dateTime('datetime');
            $table->date('immutable_date');
            $table->dateTime('immutable_datetime');
            $table->timestamp('timestamp');

            $table->timestamps();
            $table->softDeletes();
        });
    }
}
