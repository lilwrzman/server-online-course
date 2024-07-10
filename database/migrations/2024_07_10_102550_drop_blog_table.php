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
        Schema::dropIfExists('blogs');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::create('blogs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('author_id')->nullable();
            $table->string('title');
            $table->longText('content');
            $table->string('slug')->unique();
            $table->string('thumbnail')->nullable()->default('/storage/blogs/thumbnail.png');
            $table->timestamps();

            $table->foreign('author_id')->references('id')
                ->on('users')->onDelete('set null');
        });
    }
};
