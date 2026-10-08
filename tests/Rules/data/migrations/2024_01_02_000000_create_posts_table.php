<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->string('title');
        });

        Schema::table('posts', function (Blueprint $table) {
            $table->string('subtitle');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('posts');
    }
};
