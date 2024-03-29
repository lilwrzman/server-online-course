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
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->longText('content');
            $table->string('thumbnail')->nullable()->default('public/events/thumbnail.png');
            $table->string('slug')->unique();
            $table->integer('quota');
            $table->enum('type', ['Online', 'Offline']);
            $table->enum('status', ['Upcoming', 'Ongoing', 'Completed']);
            $table->json('info')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
