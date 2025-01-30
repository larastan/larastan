<?php

declare(strict_types=1);

namespace Database\Migrations;

use App\Role;
use App\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UlidAndUuidTableMigration extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('ulid_models', static function (Blueprint $table) {
            $table->ulid('id')->primary();
        });
        Schema::create('uuid_models', static function (Blueprint $table) {
            $table->ulid('id')->primary();
        });
    }
}
