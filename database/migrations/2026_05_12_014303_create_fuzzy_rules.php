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
            $table->string('variable'); // LCD, Baterai, Processor, Keyboard
            $table->string('category'); // rendah, normal, tinggi
            $table->string('curve_type'); // turun, naik, segitiga
            $table->json('parameters'); // [20, 50]
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
