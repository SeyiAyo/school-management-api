<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Student;
use App\Models\ParentModel;

class StudentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all parents to assign to students
        $parents = ParentModel::with('user')->get();
        
        $students = [
            [
                'name' => 'Alex Johnson',
                'email' => 'alex.johnson@student.com',
                'phone' => '+1234567810',
                'date_of_birth' => '2008-03-15',
                'address' => '123 Student Ave, Springfield',
                'gender' => 'male',
                'parent_email' => 'john.smith@parent.com'
            ],
            [
                'name' => 'Emma Williams',
                'email' => 'emma.williams@student.com',
                'phone' => '+1234567811',
                'date_of_birth' => '2009-07-22',
                'address' => '456 Learner St, Springfield',
                'gender' => 'female',
                'parent_email' => 'sarah.johnson@parent.com'
            ],
            [
                'name' => 'Noah Brown',
                'email' => 'noah.brown@student.com',
                'phone' => '+1234567812',
                'date_of_birth' => '2007-11-10',
                'address' => '789 Education Blvd, Springfield',
                'gender' => 'male',
                'parent_email' => 'michael.brown@parent.com'
            ],
            [
                'name' => 'Sophia Davis',
                'email' => 'sophia.davis@student.com',
                'phone' => '+1234567813',
                'date_of_birth' => '2008-05-18',
                'address' => '321 Knowledge Lane, Springfield',
                'gender' => 'female',
                'parent_email' => 'emily.davis@parent.com'
            ],
            [
                'name' => 'Liam Wilson',
                'email' => 'liam.wilson@student.com',
                'phone' => '+1234567814',
                'date_of_birth' => '2009-01-25',
                'address' => '654 Study Street, Springfield',
                'gender' => 'male',
                'parent_email' => 'david.wilson@parent.com'
            ],
            [
                'name' => 'Olivia Anderson',
                'email' => 'olivia.anderson@student.com',
                'phone' => '+1234567815',
                'date_of_birth' => '2008-09-12',
                'address' => '987 Learning Drive, Springfield',
                'gender' => 'female',
                'parent_email' => 'lisa.anderson@parent.com'
            ],
            [
                'name' => 'Mason Taylor',
                'email' => 'mason.taylor@student.com',
                'phone' => '+1234567816',
                'date_of_birth' => '2007-12-03',
                'address' => '147 Academic Avenue, Springfield',
                'gender' => 'male',
                'parent_email' => 'robert.taylor@parent.com'
            ],
            [
                'name' => 'Isabella Martinez',
                'email' => 'isabella.martinez@student.com',
                'phone' => '+1234567817',
                'date_of_birth' => '2009-04-08',
                'address' => '258 School Road, Springfield',
                'gender' => 'female',
                'parent_email' => 'jennifer.martinez@parent.com'
            ],
            [
                'name' => 'Ethan Garcia',
                'email' => 'ethan.garcia@student.com',
                'phone' => '+1234567818',
                'date_of_birth' => '2008-08-20',
                'address' => '369 Education Circle, Springfield',
                'gender' => 'male',
                'parent_email' => 'john.smith@parent.com'
            ],
            [
                'name' => 'Ava Rodriguez',
                'email' => 'ava.rodriguez@student.com',
                'phone' => '+1234567819',
                'date_of_birth' => '2009-06-14',
                'address' => '741 Campus Way, Springfield',
                'gender' => 'female',
                'parent_email' => 'sarah.johnson@parent.com'
            ]
        ];

        foreach ($students as $studentData) {
            // Check if user already exists
            $existingUser = User::where('email', $studentData['email'])->first();
            
            if ($existingUser) {
                $this->command->info("Student {$studentData['name']} already exists, skipping...");
                continue;
            }
            
            // Find parent by email
            $parent = $parents->firstWhere('user.email', $studentData['parent_email']);
            
            // Create user account for student
            $user = User::create([
                'name' => $studentData['name'],
                'email' => $studentData['email'],
                'password' => Hash::make('password123'), // Default password
                'role' => 'student'
            ]);

            // Create student profile
            Student::create([
                'user_id' => $user->id,
                'phone' => $studentData['phone'],
                'date_of_birth' => $studentData['date_of_birth'],
                'address' => $studentData['address'],
                'gender' => $studentData['gender'],
                'parent_id' => $parent ? $parent->id : null
            ]);
        }

        $this->command->info('Students seeded successfully!');
    }
}
