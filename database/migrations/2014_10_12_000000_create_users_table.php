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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('email')->unique();
            $table->string('username')->unique();
            $table->string('password');
            $table->enum('role', ['Superadmin', 'Teacher', 'Corporate Admin','Student']);
            $table->unsignedBigInteger('corporate_id')->nullable();
            $table->json('info')->nullable();
            $table->string('avatar')->nullable()->default('public/avatars/default.png');
            $table->enum('status', ['Active', 'Non-Active', 'Pending']);
            $table->string('verification_token')->nullable()->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->rememberToken();
            $table->timestamps();

            $table->foreign('corporate_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
