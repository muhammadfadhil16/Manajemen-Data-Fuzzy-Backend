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
        Schema::rename('fuzzy_rules', 'fuzzy_configs');
    }

    public function down(): void
    {
        Schema::rename('fuzzy_configs', 'fuzzy_rules');
    }
};
