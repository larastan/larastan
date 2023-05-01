<?php

declare(strict_types=1);

use App\Address;
use App\User;
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
        Schema::create('addresses', static function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignIdFor(User::class);
            $table->foreignIdFor(User::class, 'custom_foreign_id_for_name');
            $table->foreignIdFor(Address::class);
            $table->foreignIdFor(Address::class, 'nullable_address_id')->nullable();
            $table->foreignId('foreign_id_constrained')->constrained('users');
            $table->foreignId('nullable_foreign_id_constrained')->nullable()->constrained('users');
        });
    }
};
