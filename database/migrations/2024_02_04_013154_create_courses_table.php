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
        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('learning_path_id')->nullable();
            $table->unsignedBigInteger('teacher_id')->nullable();
            $table->string('title');
            $table->longText('description');
            $table->string('slug')->unique();
            $table->string('thumbnail')->nullable()->default('public/courses/thumbnail.png');
            $table->integer('price');
            $table->enum('status', ['Drafted', 'Published'])->nullable()->default('Drafted');
            $table->timestamps();

            $table->foreign('learning_path_id')->references('id')
                ->on('learning_paths')->onDelete('set null');
            $table->foreign('teacher_id')->references('id')
                ->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('courses');
    }
};
