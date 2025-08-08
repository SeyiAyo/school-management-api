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
            // Drop the foreign key constraint first
            $table->dropForeign(['teacher_id']);
            
            // Make teacher_id nullable
            $table->unsignedBigInteger('teacher_id')->nullable()->change();
            
            // Re-add the foreign key constraint
            $table->foreign('teacher_id')->references('id')->on('teachers')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('classes', function (Blueprint $table) {
            // Drop the foreign key constraint
            $table->dropForeign(['teacher_id']);
            
            // Make teacher_id required again
            $table->unsignedBigInteger('teacher_id')->nullable(false)->change();
            
            // Re-add the foreign key constraint with cascade delete
            $table->foreign('teacher_id')->references('id')->on('teachers')->onDelete('cascade');
        });
    }
};
