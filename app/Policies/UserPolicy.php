<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\Response;

class UserPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return false;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, User $model): bool
    {
        return false;
    }

    /**
     * Determine whether the user can create models.
     * Administrator can create manager and user
     * Manager can create user
     */
    public function create(User $user, string $role): bool
    {
        return match ($user->role) {
            'administrator' => in_array($role, ['manager', 'user']),
            'manager' => $role === 'user',
            'user' => false,
            default => false,
        };
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, User $model): bool
    {
        return match ($user->role) {
            // can edit any user
            'administrator' => true,
            // can only edit users with the role user
            'manager' => $model->role === 'user',
            // can only edit themselves
            'user' => $user->id === $model->id,
            default => false,
        };
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, User $model): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, User $model): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, User $model): bool
    {
        return false;
    }
}
