<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $driver = DB::connection()->getDriverName();
        
        if ($driver === 'mysql') {
            // MySQL: Modify the existing enum column
            DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('super_admin', 'admin', 'teacher', 'student', 'parent') NOT NULL");
        } elseif ($driver === 'pgsql') {
            // PostgreSQL: Drop existing constraint and add new one with super_admin
            DB::statement("ALTER TABLE users DROP CONSTRAINT IF EXISTS users_role_check");
            DB::statement("ALTER TABLE users ADD CONSTRAINT users_role_check CHECK (role::text = ANY (ARRAY['super_admin'::character varying, 'admin'::character varying, 'teacher'::character varying, 'student'::character varying, 'parent'::character varying]::text[]))");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $driver = DB::connection()->getDriverName();
        
        if ($driver === 'mysql') {
            // MySQL: Remove super_admin from the enum
            DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'teacher', 'student', 'parent') NOT NULL");
        } elseif ($driver === 'pgsql') {
            // PostgreSQL: Update super_admin users to admin, then recreate constraint
            DB::statement("UPDATE users SET role = 'admin' WHERE role = 'super_admin'");
            DB::statement("ALTER TABLE users DROP CONSTRAINT IF EXISTS users_role_check");
            DB::statement("ALTER TABLE users ADD CONSTRAINT users_role_check CHECK (role::text = ANY (ARRAY['admin'::character varying, 'teacher'::character varying, 'student'::character varying, 'parent'::character varying]::text[]))");
        }
    }
};
