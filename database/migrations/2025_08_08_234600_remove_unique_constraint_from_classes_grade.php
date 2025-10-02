<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $driver = DB::connection()->getDriverName();
        
        if ($driver === 'pgsql') {
            // PostgreSQL: Drop unique constraint if it exists
            DB::statement('ALTER TABLE classes DROP CONSTRAINT IF EXISTS classes_grade_unique');
        } else {
            // MySQL
            Schema::table('classes', function (Blueprint $table) {
                $table->dropUnique(['grade']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $driver = DB::connection()->getDriverName();
        
        if ($driver === 'pgsql') {
            // PostgreSQL: Re-add unique constraint
            DB::statement('ALTER TABLE classes ADD CONSTRAINT classes_grade_unique UNIQUE (grade)');
        } else {
            // MySQL
            Schema::table('classes', function (Blueprint $table) {
                $table->unique('grade');
            });
        }
    }
};
