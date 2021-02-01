<?php

declare(strict_types=1);

namespace Tests\Unit\AdditionalMigrations;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTeamsTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('teams', static function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('team')->nullable();
            $table->string('owner_email')->unique();
            $table->timestamps();
        });
    }
}
