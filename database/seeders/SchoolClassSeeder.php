<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\SchoolClass;
use App\Models\Teacher;

class SchoolClassSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get teacher IDs to assign to classes
        $teacherIds = Teacher::pluck('id')->toArray();
        
        $classes = [
            [
                'name' => 'Mathematics 101',
                'grade' => '9th Grade',
                'teacher_id' => $teacherIds[0] ?? 1, // Assign to first teacher or default to ID 1
            ],
            [
                'name' => 'English Literature',
                'grade' => '9th Grade',
                'teacher_id' => $teacherIds[1] ?? 2, // Assign to second teacher or default to ID 2
            ],
            [
                'name' => 'Physics Fundamentals',
                'grade' => '10th Grade',
                'teacher_id' => $teacherIds[2] ?? 3, // Assign to third teacher or default to ID 3
            ],
            [
                'name' => 'World History',
                'grade' => '10th Grade',
                'teacher_id' => $teacherIds[3] ?? 4, // Assign to fourth teacher or default to ID 4
            ],
        ];
        
        foreach ($classes as $class) {
            SchoolClass::create($class);
        }
    }
}
