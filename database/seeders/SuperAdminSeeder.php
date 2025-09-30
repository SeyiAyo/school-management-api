<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Check if super admin already exists
        $superAdmin = User::where('role', Role::SUPER_ADMIN)->first();

        if (!$superAdmin) {
            User::create([
                'name' => 'Super Administrator',
                'email' => 'superadmin@schoolmanagement.com',
                'password' => Hash::make('SuperAdmin123!'),
                'role' => Role::SUPER_ADMIN,
                'email_verified_at' => now(), // Pre-verify the super admin
            ]);

            $this->command->info('Super Admin created successfully!');
            $this->command->info('Email: superadmin@schoolmanagement.com');
            $this->command->info('Password: SuperAdmin123!');
        } else {
            $this->command->info('Super Admin already exists.');
        }
    }
}
