<?php

namespace App\Policies;

use App\Models\ParentModel;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ParentPolicy
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
        // Admin can view all parents
        return $user->role === 'admin';
    }

    /**
     * Determine whether the user can view the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\ParentModel  $parent
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $user, ParentModel $parent)
    {
        // Admin can view any parent
        // Parents can view their own profile
        return $user->role === 'admin' || 
               ($user->role === 'parent' && $user->id === $parent->user_id);
    }

    /**
     * Determine whether the user can create models.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function create(User $user)
    {
        // Only admin can create parents
        return $user->role === 'admin';
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\ParentModel  $parent
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $user, ParentModel $parent)
    {
        // Admin can update any parent
        // Parents can update their own profile
        return $user->role === 'admin' || 
               ($user->role === 'parent' && $user->id === $parent->user_id);
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\ParentModel  $parent
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(User $user, ParentModel $parent)
    {
        // Only admin can delete parents
        return $user->role === 'admin';
    }
}
