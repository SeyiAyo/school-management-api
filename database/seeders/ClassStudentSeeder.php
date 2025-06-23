<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\SchoolClass;
use App\Models\Student;
use Illuminate\Support\Facades\DB;

class ClassStudentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all classes and students
        $classes = SchoolClass::all();
        $students = Student::all();
        
        // Assign students to classes (many-to-many relationship)
        // Each student can be in multiple classes
        
        // Class 1 (Mathematics 101) - Students 1, 2, 3, 4
        if ($classes->count() >= 1 && $students->count() >= 4) {
            for ($i = 0; $i < 4; $i++) {
                DB::table('class_student')->insert([
                    'class_id' => $classes[0]->id,
                    'student_id' => $students[$i]->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
        
        // Class 2 (English Literature) - Students 1, 3, 5
        if ($classes->count() >= 2 && $students->count() >= 5) {
            DB::table('class_student')->insert([
                ['class_id' => $classes[1]->id, 'student_id' => $students[0]->id, 'created_at' => now(), 'updated_at' => now()],
                ['class_id' => $classes[1]->id, 'student_id' => $students[2]->id, 'created_at' => now(), 'updated_at' => now()],
                ['class_id' => $classes[1]->id, 'student_id' => $students[4]->id, 'created_at' => now(), 'updated_at' => now()],
            ]);
        }
        
        // Class 3 (Physics Fundamentals) - Students 2, 4, 5, 6
        if ($classes->count() >= 3 && $students->count() >= 6) {
            DB::table('class_student')->insert([
                ['class_id' => $classes[2]->id, 'student_id' => $students[1]->id, 'created_at' => now(), 'updated_at' => now()],
                ['class_id' => $classes[2]->id, 'student_id' => $students[3]->id, 'created_at' => now(), 'updated_at' => now()],
                ['class_id' => $classes[2]->id, 'student_id' => $students[4]->id, 'created_at' => now(), 'updated_at' => now()],
                ['class_id' => $classes[2]->id, 'student_id' => $students[5]->id, 'created_at' => now(), 'updated_at' => now()],
            ]);
        }
        
        // Class 4 (World History) - Students 3, 4, 6
        if ($classes->count() >= 4 && $students->count() >= 6) {
            DB::table('class_student')->insert([
                ['class_id' => $classes[3]->id, 'student_id' => $students[2]->id, 'created_at' => now(), 'updated_at' => now()],
                ['class_id' => $classes[3]->id, 'student_id' => $students[3]->id, 'created_at' => now(), 'updated_at' => now()],
                ['class_id' => $classes[3]->id, 'student_id' => $students[5]->id, 'created_at' => now(), 'updated_at' => now()],
            ]);
        }
    }
}
