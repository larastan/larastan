<?php

declare(strict_types=1);

namespace Database\Migrations;

use App\Role;
use App\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRoleUserTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('role_user', static function (Blueprint $table) {
            $table->foreignIdFor(Role::class);
            $table->foreignIdFor(User::class);
        });
    }
}
