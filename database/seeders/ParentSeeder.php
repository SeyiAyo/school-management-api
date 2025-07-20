<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\ParentModel;

class ParentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $parents = [
            [
                'name' => 'John Smith',
                'email' => 'john.smith@parent.com',
                'phone' => '+1234567890',
                'address' => '123 Oak Street, Springfield',
                'occupation' => 'Software Engineer'
            ],
            [
                'name' => 'Sarah Johnson',
                'email' => 'sarah.johnson@parent.com',
                'phone' => '+1234567892',
                'address' => '456 Pine Avenue, Springfield',
                'occupation' => 'Teacher'
            ],
            [
                'name' => 'Michael Brown',
                'email' => 'michael.brown@parent.com',
                'phone' => '+1234567894',
                'address' => '789 Maple Drive, Springfield',
                'occupation' => 'Doctor'
            ],
            [
                'name' => 'Emily Davis',
                'email' => 'emily.davis@parent.com',
                'phone' => '+1234567896',
                'address' => '321 Elm Street, Springfield',
                'occupation' => 'Nurse'
            ],
            [
                'name' => 'David Wilson',
                'email' => 'david.wilson@parent.com',
                'phone' => '+1234567898',
                'address' => '654 Cedar Lane, Springfield',
                'occupation' => 'Business Owner'
            ],
            [
                'name' => 'Lisa Anderson',
                'email' => 'lisa.anderson@parent.com',
                'phone' => '+1234567800',
                'address' => '987 Birch Road, Springfield',
                'occupation' => 'Accountant'
            ],
            [
                'name' => 'Robert Taylor',
                'email' => 'robert.taylor@parent.com',
                'phone' => '+1234567802',
                'address' => '147 Willow Way, Springfield',
                'occupation' => 'Lawyer'
            ],
            [
                'name' => 'Jennifer Martinez',
                'email' => 'jennifer.martinez@parent.com',
                'phone' => '+1234567804',
                'address' => '258 Spruce Street, Springfield',
                'occupation' => 'Marketing Manager'
            ]
        ];

        foreach ($parents as $parentData) {
            // Check if user already exists
            $existingUser = User::where('email', $parentData['email'])->first();
            
            if ($existingUser) {
                $this->command->info("Parent {$parentData['name']} already exists, skipping...");
                continue;
            }
            
            // Create user account for parent
            $user = User::create([
                'name' => $parentData['name'],
                'email' => $parentData['email'],
                'password' => Hash::make('password123'), // Default password
                'role' => 'parent'
            ]);

            // Create parent profile
            ParentModel::create([
                'user_id' => $user->id,
                'phone' => $parentData['phone'],
                'address' => $parentData['address'],
                'occupation' => $parentData['occupation']
            ]);
        }

        $this->command->info('Parents seeded successfully!');
    }
}
