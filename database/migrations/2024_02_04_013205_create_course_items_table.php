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
        Schema::create('course_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('course_id');
            $table->string('title');
            $table->longText('content');
            $table->enum('type', ['Material', 'Quiz', 'Exam']);
            $table->string('slug')->unique();
            $table->json('info')->nullable();
            $table->integer('order');
            $table->timestamps();

            $table->foreign('course_id')->references('id')
                ->on('courses');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('course_items');
    }
};
