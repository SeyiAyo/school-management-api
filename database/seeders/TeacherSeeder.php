<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Teacher;

class TeacherSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $teachers = [
            [
                'name' => 'John Smith',
                'email' => 'john.smith@school.com',
                'phone' => '1234567890',
                'subject_specialty' => 'Mathematics',
                'qualification' => 'PhD in Mathematics',
                'date_of_birth' => '1980-05-15',
                'address' => '123 Teacher St, School City',
                'gender' => 'Male',
            ],
            [
                'name' => 'Sarah Johnson',
                'email' => 'sarah.johnson@school.com',
                'phone' => '2345678901',
                'subject_specialty' => 'English Literature',
                'qualification' => 'Masters in English',
                'date_of_birth' => '1985-08-22',
                'address' => '456 Faculty Ave, School City',
                'gender' => 'Female',
            ],
            [
                'name' => 'Michael Lee',
                'email' => 'michael.lee@school.com',
                'phone' => '3456789012',
                'subject_specialty' => 'Physics',
                'qualification' => 'Masters in Physics',
                'date_of_birth' => '1978-11-10',
                'address' => '789 Science Blvd, School City',
                'gender' => 'Male',
            ],
            [
                'name' => 'Emily Chen',
                'email' => 'emily.chen@school.com',
                'phone' => '4567890123',
                'subject_specialty' => 'History',
                'qualification' => 'PhD in History',
                'date_of_birth' => '1982-03-28',
                'address' => '101 History Lane, School City',
                'gender' => 'Female',
            ],
        ];
        
        foreach ($teachers as $teacher) {
            Teacher::create($teacher);
        }
    }
}
