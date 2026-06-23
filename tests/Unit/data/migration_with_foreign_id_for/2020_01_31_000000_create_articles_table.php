<?php

declare(strict_types=1);

namespace Tests\Unit\Data\MigrationWithForeignIdFor;

use App\Member;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateArticlesTable extends Migration
{
    public function up(): void
    {
        Schema::create('articles', static function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('title');
            $table->foreignIdFor(Member::class);
            $table->timestamps();
        });
    }
}
