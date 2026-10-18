<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('users', 'flag')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->boolean('flag');
        });
    }
};
