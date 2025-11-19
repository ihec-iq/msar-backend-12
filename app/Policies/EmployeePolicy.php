<?php

namespace App\Policies;

use App\Models\Employee;
use App\Models\User;

class EmployeePolicy
{
    /**
     * Determine if the user can view any employees.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('employees list');
    }

    /**
     * Determine if the user can view the employee.
     */
    public function view(User $user, Employee $employee): bool
    {
        return $user->hasPermissionTo('employees list');
    }

    /**
     * Determine if the user can create employees.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('employees add');
    }

    /**
     * Determine if the user can update the employee.
     */
    public function update(User $user, Employee $employee): bool
    {
        return $user->hasPermissionTo('employees edit');
    }

    /**
     * Determine if the user can delete the employee.
     */
    public function delete(User $user, Employee $employee): bool
    {
        return $user->hasPermissionTo('employees delete');
    }

    /**
     * Determine if the user can manage employee bonuses.
     */
    public function manageBonus(User $user, Employee $employee): bool
    {
        return $user->hasPermissionTo('employees bonus');
    }

    /**
     * Determine if the user can manage employee promotions.
     */
    public function managePromotion(User $user, Employee $employee): bool
    {
        return $user->hasPermissionTo('employees promotion');
    }

    /**
     * Determine if the user can view employee from specific section.
     */
    public function viewEmployeeType(User $user, string $employeeTypeId): bool
    {
        // Employees type 1 (permanent) - everyone can see
        if ($employeeTypeId == '1') {
            return true;
        }

        // Employee type 2 (contractors) - need vacation office permission
        if ($employeeTypeId == '2') {
            return $user->hasPermissionTo('vacation office');
        }

        // Employee type 3 (daily workers) - need vacation center permission
        if ($employeeTypeId == '3') {
            return $user->hasPermissionTo('vacation center');
        }

        return false;
    }
}
