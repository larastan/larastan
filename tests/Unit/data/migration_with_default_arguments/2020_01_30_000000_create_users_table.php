<?php

declare(strict_types=1);

namespace Tests\Unit\MigrationWithSchemaConnection;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUsersTable extends Migration
{
    public function up(): void
    {
        Schema::create('users', static function (Blueprint $table) {
            $table->id();
            $table->ipAddress();
            $table->ipAddress('custom_ip_address');
            $table->macAddress();
            $table->macAddress('custom_mac_address');
            $table->uuid();
            $table->uuid('custom_uuid');
            $table->ulid();
            $table->ulid('custom_ulid');
            $table->softDeletes();
            $table->softDeletes('custom_soft_deletes');
        });
    }
}
