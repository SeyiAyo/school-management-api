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
            // PostgreSQL: Use raw SQL to handle column modification
            DB::statement('ALTER TABLE classes DROP COLUMN IF EXISTS grade');
            DB::statement('ALTER TABLE classes ADD COLUMN grade INTEGER UNIQUE');
        } else {
            // MySQL: Use Schema builder
            if (Schema::hasColumn('classes', 'grade')) {
                Schema::table('classes', function (Blueprint $table) {
                    $table->dropColumn('grade');
                });
            }

            Schema::table('classes', function (Blueprint $table) {
                $table->integer('grade')->unique()->after('name');
            });
        }
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
