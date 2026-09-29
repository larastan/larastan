<?php

declare(strict_types=1);

namespace Tests\Unit\MigrationsMovedToAnotherConnection;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSubcontractAgreementsTable extends Migration
{
    public function up(): void
    {
        Schema::create('subcontract_agreements', static function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid');
        });
    }
}
