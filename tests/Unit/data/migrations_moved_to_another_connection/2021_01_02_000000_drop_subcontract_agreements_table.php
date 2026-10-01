<?php

declare(strict_types=1);

namespace Tests\Unit\MigrationsMovedToAnotherConnection;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

class DropSubcontractAgreementsTable extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('subcontract_agreements');
    }
}
