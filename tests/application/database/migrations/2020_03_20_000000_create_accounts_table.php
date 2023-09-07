<?php

declare(strict_types=1);

namespace Database\Migrations;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateAccountsTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        \Schema::create('accounts', static function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->string('active', 1);
            $table->timestamps();
        });
    }
}
