<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SchoolClass extends Model
{
    use HasFactory;

    protected $table = 'classes'; // Specify the table name since 'class' is a reserved keyword

    protected $fillable = [
        'name',
        'description',
        'teacher_id',
        'capacity',
    ];

    /**
     * Get the teacher that owns the class.
     */
    public function teacher()
    {
        return $this->belongsTo(Teacher::class);
    }

    /**
     * The students that belong to the class.
     */
    public function students()
    {
        return $this->belongsToMany(Student::class, 'class_student', 'class_id', 'student_id');
    }
}
