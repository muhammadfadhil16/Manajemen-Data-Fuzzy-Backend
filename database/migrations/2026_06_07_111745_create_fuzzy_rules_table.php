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
        Schema::create('fuzzy_rules', function (Blueprint $table) {
            $table->id();
            $table->enum('lcd', ['buruk', 'sedang', 'baik']);
            $table->enum('keyboard', ['buruk', 'sedang', 'baik']);
            $table->enum('ram', ['rendah', 'sedang', 'tinggi']);
            $table->enum('baterai', ['rendah', 'sedang', 'tinggi']);
            $table->enum('processor', ['rendah', 'sedang', 'tinggi']);
            $table->enum('output', ['tidak_layak', 'cukup_layak', 'layak']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fuzzy_rules');
    }
};
