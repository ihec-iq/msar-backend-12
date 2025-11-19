<?php

namespace App\Policies;

use App\Models\User;

class StockPolicy
{
    /**
     * Determine if the user can view any stock items.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('stocks list');
    }

    /**
     * Determine if the user can view the stock item.
     */
    public function view(User $user): bool
    {
        return $user->hasPermissionTo('stocks list');
    }

    /**
     * Determine if the user can create stock items.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('stocks add');
    }

    /**
     * Determine if the user can update stock items.
     */
    public function update(User $user): bool
    {
        return $user->hasPermissionTo('stocks edit');
    }

    /**
     * Determine if the user can delete stock items.
     */
    public function delete(User $user): bool
    {
        return $user->hasPermissionTo('stocks delete');
    }

    /**
     * Determine if the user can manage input vouchers.
     */
    public function manageInputVoucher(User $user): bool
    {
        return $user->hasPermissionTo('input voucher');
    }

    /**
     * Determine if the user can manage output vouchers.
     */
    public function manageOutputVoucher(User $user): bool
    {
        return $user->hasPermissionTo('output voucher');
    }

    /**
     * Determine if the user can manage retrieval vouchers.
     */
    public function manageRetrievalVoucher(User $user): bool
    {
        return $user->hasPermissionTo('retrieval voucher');
    }

    /**
     * Determine if the user can manage direct vouchers.
     */
    public function manageDirectVoucher(User $user): bool
    {
        return $user->hasPermissionTo('direct voucher');
    }

    /**
     * Determine if the user can view stock reports.
     */
    public function viewReports(User $user): bool
    {
        return $user->hasPermissionTo('stock reports');
    }
}
