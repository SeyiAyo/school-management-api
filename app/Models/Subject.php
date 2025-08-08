<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Subject extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'created_by',
    ];

    /**
     * Get the admin who created this subject.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * The classes that have this subject.
     */
    public function classes()
    {
        return $this->belongsToMany(SchoolClass::class, 'class_subject', 'subject_id', 'class_id')
                    ->withPivot('teacher_id')
                    ->withTimestamps();
    }

    /**
     * Get all teachers assigned to teach this subject across different classes.
     */
    public function teachers()
    {
        return $this->belongsToMany(Teacher::class, 'class_subject', 'subject_id', 'teacher_id')
                    ->withPivot('class_id')
                    ->withTimestamps();
    }
}
