<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('assessments', 'ram_input') && ! Schema::hasColumn('assessments', 'processor_input')) {
            DB::statement('ALTER TABLE assessments CHANGE ram_input processor_input DOUBLE NOT NULL');
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('assessments', 'processor_input') && ! Schema::hasColumn('assessments', 'ram_input')) {
            DB::statement('ALTER TABLE assessments CHANGE processor_input ram_input DOUBLE NOT NULL');
        }
    }
};
