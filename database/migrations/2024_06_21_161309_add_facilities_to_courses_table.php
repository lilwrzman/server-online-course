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
        Schema::table('courses', function (Blueprint $table) {
            $table->json('facilities')->default(json_encode([
                'Materi Elektronik : Materi disajikan dalam bentuk video',
                'Forum Diskusi : Setiap materi memiliki sebuah forum diskusi',
                'Evaluasi Pembelajaran : Ujian akhir kelas',
                'Sertifikat kompetensi']));
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->dropColumn('facilities');
        });
    }
};
