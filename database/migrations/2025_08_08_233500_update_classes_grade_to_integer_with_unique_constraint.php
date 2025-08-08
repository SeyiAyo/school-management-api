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
        Schema::table('classes', function (Blueprint $table) {
            // First, drop the existing grade column if it exists
            if (Schema::hasColumn('classes', 'grade')) {
                $table->dropColumn('grade');
            }
            
            // Add new grade column as integer with unique constraint
            $table->integer('grade')->unique()->after('name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('classes', function (Blueprint $table) {
            // Drop the unique constraint and column
            $table->dropUnique(['grade']);
            $table->dropColumn('grade');
            
            // Restore original grade column as string
            $table->string('grade', 50)->after('name');
        });
    }
};
