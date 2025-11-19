<?php

namespace App\Policies;

use App\Models\Vacation;
use App\Models\User;

class VacationPolicy
{
    /**
     * Determine if the user can view any vacations.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasAnyPermission(['vacation list', 'vacation office', 'vacation center']);
    }

    /**
     * Determine if the user can view the vacation.
     */
    public function view(User $user, Vacation $vacation): bool
    {
        return $user->hasAnyPermission(['vacation list', 'vacation office', 'vacation center']);
    }

    /**
     * Determine if the user can create vacations.
     */
    public function create(User $user): bool
    {
        return $user->hasAnyPermission(['vacation add', 'vacation office', 'vacation center']);
    }

    /**
     * Determine if the user can update the vacation.
     */
    public function update(User $user, Vacation $vacation): bool
    {
        return $user->hasAnyPermission(['vacation edit', 'vacation office', 'vacation center']);
    }

    /**
     * Determine if the user can delete the vacation.
     */
    public function delete(User $user, Vacation $vacation): bool
    {
        return $user->hasPermissionTo('vacation delete');
    }

    /**
     * Determine if the user can manage daily vacations.
     */
    public function manageDaily(User $user): bool
    {
        return $user->hasAnyPermission(['vacation office', 'vacation center']);
    }

    /**
     * Determine if the user can manage sick leave.
     */
    public function manageSick(User $user): bool
    {
        return $user->hasAnyPermission(['vacation office', 'vacation center']);
    }

    /**
     * Determine if the user can manage time-off requests.
     */
    public function manageTime(User $user): bool
    {
        return $user->hasAnyPermission(['vacation office', 'vacation center']);
    }
}
