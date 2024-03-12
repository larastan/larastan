<?php

declare(strict_types=1);

namespace Database\Migrations;

use App\Role;
use App\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    protected $connection = 'foo';

    public function up(): void
    {
        // This is testing Postgres schemas (sub-databases)
        Schema::create('private.threads', static function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->boolean('active');
            $table->timestamps();
        });
    }
};
