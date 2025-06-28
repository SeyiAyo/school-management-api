<?php

namespace App\Providers;


use App\Models\Student;
use App\Models\Teacher;
use App\Models\ParentModel;

use App\Policies\StudentPolicy;
use App\Policies\TeacherPolicy;
use App\Policies\ParentPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [

        Student::class => StudentPolicy::class,
        Teacher::class => TeacherPolicy::class,
        ParentModel::class => ParentPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        // Define role-based gates
        Gate::define('admin-access', function ($user) {
            return $user->role === 'admin';
        });

        Gate::define('teacher-access', function ($user) {
            return $user->role === 'teacher' || $user->role === 'admin';
        });

        Gate::define('student-access', function ($user) {
            return $user->role === 'student' || $user->role === 'admin' || $user->role === 'teacher';
        });

        Gate::define('parent-access', function ($user) {
            return $user->role === 'parent' || $user->role === 'admin';
        });
    }
}
