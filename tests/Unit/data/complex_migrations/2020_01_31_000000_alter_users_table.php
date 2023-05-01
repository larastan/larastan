<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Do Some stuff, create temp tables
        Schema::drop('users');

        // Do more stuff

        $this->createTable();

        // Stuff

        $this->alterTable();
    }

    private function createTable(): void
    {
        Schema::create('users', static function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name')->nullable();
            $table->string('email')->unique();
            $table->date('birthday');
            $table->timestamps();
        });
    }

    private function alterTable(): void
    {
        Schema::table('users', static function (Blueprint $table) {
            $table->dropColumn('name');
            $table->integer('active');
        });
    }
};
