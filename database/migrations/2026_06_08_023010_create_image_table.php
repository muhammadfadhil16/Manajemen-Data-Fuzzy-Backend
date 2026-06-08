<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assessment_images', function (Blueprint $table) {
            $table->id();
            // Menghubungkan gambar ke tabel assessments utama. 
            // onDelete('cascade') artinya jika data penilaian dihapus, gambarnya otomatis ikut terhapus di DB.
            $table->foreignId('assessment_id')->constrained('assessments')->onDelete('cascade');
            $table->string('image_path');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assessment_images');
    }
};