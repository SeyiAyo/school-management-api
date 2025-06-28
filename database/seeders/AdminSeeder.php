<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create a default admin user
        User::create([
            'name' => 'Admin User',
            'email' => 'admin@school.com',
            'password' => Hash::make('password123'),
            'role' => 'admin'
        ]);
        
        // Create a second admin user
        User::create([
            'name' => 'School Principal',
            'email' => 'principal@school.com',
            'password' => Hash::make('password123'),
            'role' => 'admin'
        ]);
    }
}
