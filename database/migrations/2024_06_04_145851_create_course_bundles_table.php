<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('course_bundles', function (Blueprint $table) {
            $table->id();
            $table->string('bundle_code')->unique();
            $table->unsignedBigInteger('corporate_id')->nullable();
            $table->unsignedBigInteger('redeem_code_id')->nullable();
            $table->integer('price');
            $table->integer('quota');
            $table->timestamps();

            $table->foreign('redeem_code_id')->references('id')->on('redeem_codes')->onDelete('restrict');
            $table->foreign('corporate_id')->references('id')->on('users')->onDelete("set null");

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('course_bundles');
    }
};
