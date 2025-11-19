<?php

namespace App\Policies;

use App\Models\Archive;
use App\Models\User;

class ArchivePolicy
{
    /**
     * Determine if the user can view any archives.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('archives list');
    }

    /**
     * Determine if the user can view the archive.
     */
    public function view(User $user, Archive $archive): bool
    {
        return $user->hasPermissionTo('archives list');
    }

    /**
     * Determine if the user can create archives.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('archives add');
    }

    /**
     * Determine if the user can update the archive.
     */
    public function update(User $user, Archive $archive): bool
    {
        return $user->hasPermissionTo('archives edit');
    }

    /**
     * Determine if the user can delete the archive.
     */
    public function delete(User $user, Archive $archive): bool
    {
        return $user->hasPermissionTo('archives delete');
    }

    /**
     * Determine if the user can manage document types.
     */
    public function manageDocumentTypes(User $user): bool
    {
        return $user->hasPermissionTo('archive types');
    }
}
