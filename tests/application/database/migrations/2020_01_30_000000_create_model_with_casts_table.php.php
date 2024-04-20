<?php

declare(strict_types=1);

namespace Database\Migrations;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateModelWithCastsTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('model_with_casts', static function (Blueprint $table) {
            $table->bigIncrements('id');

            // Testing property casts
            $table->integer('int');
            $table->integer('integer');
            $table->float('real');
            $table->float('float');
            $table->double('double');
            $table->decimal('decimal');
            $table->string('string');
            $table->boolean('bool');
            $table->boolean('boolean');
            $table->json('object');
            $table->json('array');
            $table->json('json');
            $table->json('collection');
            $table->json('nullable_collection')->nullable();
            $table->date('date');
            $table->dateTime('datetime');
            $table->date('immutable_date');
            $table->dateTime('immutable_datetime');
            $table->timestamp('timestamp');

            $table->timestamps();
            $table->softDeletes();
        });
    }
}
