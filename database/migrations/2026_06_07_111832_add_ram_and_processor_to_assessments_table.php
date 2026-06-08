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
        Schema::table('assessments', function (Blueprint $table) {
            $table->float('ram_input')->nullable()->after('keyboard_input');
            $table->foreignId('processor_id')->nullable()->constrained('processors')->after('ram_input');
        });
    }

    public function down(): void
    {
        Schema::table('assessments', function (Blueprint $table) {
            $table->dropForeign(['processor_id']);
            $table->dropColumn(['ram_input', 'processor_id']);
        });
    }
};
