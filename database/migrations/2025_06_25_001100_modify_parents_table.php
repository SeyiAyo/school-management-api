<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ModifyParentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('parents', function (Blueprint $table) {
            // Add user_id foreign key
            $table->foreignId('user_id')->after('id')->constrained('users')->onDelete('cascade');
        });
        
        // Check and drop columns individually to avoid errors
        Schema::table('parents', function (Blueprint $table) {
            if (Schema::hasColumn('parents', 'name')) {
                $table->dropColumn('name');
            }
        });
        
        Schema::table('parents', function (Blueprint $table) {
            if (Schema::hasColumn('parents', 'email')) {
                $table->dropColumn('email');
            }
        });
        
        Schema::table('parents', function (Blueprint $table) {
            if (Schema::hasColumn('parents', 'password')) {
                $table->dropColumn('password');
            }
        });
        
        Schema::table('parents', function (Blueprint $table) {
            if (Schema::hasColumn('parents', 'remember_token')) {
                $table->dropColumn('remember_token');
            }
        });
        
        Schema::table('parents', function (Blueprint $table) {
            if (Schema::hasColumn('parents', 'email_verified_at')) {
                $table->dropColumn('email_verified_at');
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('parents', function (Blueprint $table) {
            // Add back the removed columns
            $table->string('name')->after('id');
            $table->string('email')->unique()->after('name');
            $table->string('password')->after('email');
            $table->rememberToken();
            $table->timestamp('email_verified_at')->nullable();
            
            // Remove the foreign key and column
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');
        });
    }
}
