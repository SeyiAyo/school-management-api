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
        'grade',
        'teacher_id',
        'capacity',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'grade' => 'integer',
        'capacity' => 'integer',
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

    /**
     * The subjects taught in this class.
     */
    public function subjects()
    {
        return $this->belongsToMany(Subject::class, 'class_subject', 'class_id', 'subject_id')
                    ->withPivot('teacher_id')
                    ->withTimestamps();
    }

    /**
     * Get available grade options (1-12).
     *
     * @return array
     */
    public static function getAvailableGrades()
    {
        $grades = [];
        for ($i = 1; $i <= 12; $i++) {
            $grades[] = [
                'value' => $i,
                'label' => (string) $i
            ];
        }
        return $grades;
    }

    /**
     * Get validation rule for grade field.
     *
     * @return string
     */
    public static function getGradeValidationRule()
    {
        return 'integer|min:1|max:12';
    }

    /**
     * Get validation rule for grade field during updates.
     *
     * @param int $classId
     * @return string
     */
    public static function getGradeValidationRuleForUpdate($classId)
    {
        return 'integer|min:1|max:12';
    }
}
