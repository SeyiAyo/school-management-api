<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Attendance;
use App\Models\SchoolClass;
use App\Models\Student;
use Carbon\Carbon;

class AttendanceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all classes and students
        $classes = SchoolClass::all();
        $students = Student::all();
        
        // Only proceed if we have classes and students
        if ($classes->count() == 0 || $students->count() == 0) {
            return;
        }
        
        // Create attendance records for the past week
        $dates = [
            Carbon::now()->subDays(7)->format('Y-m-d'),
            Carbon::now()->subDays(6)->format('Y-m-d'),
            Carbon::now()->subDays(5)->format('Y-m-d'),
            Carbon::now()->subDays(4)->format('Y-m-d'),
            Carbon::now()->subDays(3)->format('Y-m-d'),
        ];
        
        // For each class
        foreach ($classes as $class) {
            // Get students in this class
            $classStudents = $class->students;
            
            if ($classStudents->count() == 0) {
                continue;
            }
            
            // For each date
            foreach ($dates as $date) {
                // For each student in the class
                foreach ($classStudents as $student) {
                    // Randomly mark as present (1) or absent (0)
                    // With 80% chance of being present
                    $status = (rand(1, 100) <= 80) ? 1 : 0;
                    
                    Attendance::create([
                        'class_id' => $class->id,
                        'student_id' => $student->id,
                        'date' => $date,
                        'status' => $status,
                        'remarks' => $status ? null : 'Absent',
                    ]);
                }
            }
        }
    }
}
