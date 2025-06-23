<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Admin;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create a default admin user
        Admin::create([
            'name' => 'Admin User',
            'email' => 'admin@school.com',
            'password' => Hash::make('password123'),
        ]);
        
        // Create a second admin user
        Admin::create([
            'name' => 'School Principal',
            'email' => 'principal@school.com',
            'password' => Hash::make('password123'),
        ]);
    }
}
