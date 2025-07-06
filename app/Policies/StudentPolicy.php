<?php

namespace App\Policies;

use App\Models\Student;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class StudentPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function viewAny(User $user)
    {
        // Admin, teachers, and parents can view all students
        return in_array($user->role, ['admin', 'teacher', 'parent']);
    }

    /**
     * Determine whether the user can view the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Student  $student
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $user, Student $student)
    {
        // Admin and teachers can view any student
        // Students can view their own profile
        // Parents can view their children (would need a relationship between parent and student)
        return in_array($user->role, ['admin', 'teacher']) || 
               ($user->role === 'student' && $user->id === $student->user_id);
        
        // Note: To implement parent-student relationship, we would need to add:
        // || ($user->role === 'parent' && $user->parent->students->contains($student->id))
    }

    /**
     * Determine whether the user can create models.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function create(User $user)
    {
        // Only admin can create students
        return $user->role === 'admin';
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Student  $student
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $user, Student $student)
    {
        // Admin and teachers can update any student
        // Students can update their own profile
        return in_array($user->role, ['admin', 'teacher']) || 
               ($user->role === 'student' && $user->id === $student->user_id);
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Student  $student
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(User $user, Student $student)
    {
        // Only admin can delete students
        return $user->role === 'admin';
    }
}
