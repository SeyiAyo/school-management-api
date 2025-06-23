<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Call seeders in the correct order to respect foreign key constraints
        $this->call([
            // First seed admin users
            AdminSeeder::class,
            
            // Then seed teachers and students
            TeacherSeeder::class,
            StudentSeeder::class,
            
            // Then seed classes (which depend on teachers)
            SchoolClassSeeder::class,
            
            // Then seed class-student relationships
            ClassStudentSeeder::class,
            
            // Finally seed attendance records (which depend on classes and students)
            AttendanceSeeder::class,
        ]);
    }
}
