<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Student;

class StudentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $students = [
            [
                'name' => 'Alex Johnson',
                'email' => 'alex.johnson@student.com',
                'phone' => '5551234567',
                'date_of_birth' => '2008-03-15',
                'address' => '123 Student Ave, School City',
                'gender' => 'Male',
                'parent_name' => 'Robert Johnson',
                'parent_phone' => '5559876543',
                'parent_email' => 'robert.johnson@parent.com',
            ],
            [
                'name' => 'Emma Williams',
                'email' => 'emma.williams@student.com',
                'phone' => '5552345678',
                'date_of_birth' => '2009-07-22',
                'address' => '456 Learner St, School City',
                'gender' => 'Female',
                'parent_name' => 'Jennifer Williams',
                'parent_phone' => '5558765432',
                'parent_email' => 'jennifer.williams@parent.com',
            ],
            [
                'name' => 'Noah Brown',
                'email' => 'noah.brown@student.com',
                'phone' => '5553456789',
                'date_of_birth' => '2007-11-10',
                'address' => '789 Education Blvd, School City',
                'gender' => 'Male',
                'parent_name' => 'Michael Brown',
                'parent_phone' => '5557654321',
                'parent_email' => 'michael.brown@parent.com',
            ],
            [
                'name' => 'Olivia Davis',
                'email' => 'olivia.davis@student.com',
                'phone' => '5554567890',
                'date_of_birth' => '2008-05-28',
                'address' => '101 Knowledge Lane, School City',
                'gender' => 'Female',
                'parent_name' => 'Sophia Davis',
                'parent_phone' => '5556543210',
                'parent_email' => 'sophia.davis@parent.com',
            ],
            [
                'name' => 'William Miller',
                'email' => 'william.miller@student.com',
                'phone' => '5555678901',
                'date_of_birth' => '2009-01-14',
                'address' => '202 Wisdom St, School City',
                'gender' => 'Male',
                'parent_name' => 'James Miller',
                'parent_phone' => '5555432109',
                'parent_email' => 'james.miller@parent.com',
            ],
            [
                'name' => 'Sophia Wilson',
                'email' => 'sophia.wilson@student.com',
                'phone' => '5556789012',
                'date_of_birth' => '2007-09-03',
                'address' => '303 Scholar Ave, School City',
                'gender' => 'Female',
                'parent_name' => 'Elizabeth Wilson',
                'parent_phone' => '5554321098',
                'parent_email' => 'elizabeth.wilson@parent.com',
            ],
        ];
        
        foreach ($students as $student) {
            Student::create($student);
        }
    }
}
