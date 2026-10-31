<?php

namespace App\Policies;

use App\Models\User;

/**
 * User management rules for the admin area (FR-27).
 * An administrator can never block or delete themselves or another admin.
 */
class UserPolicy
{
    public function block(User $admin, User $target): bool
    {
        return $admin->isAdmin()
            && ! $admin->is($target)
            && ! $target->isAdmin();
    }

    public function unblock(User $admin, User $target): bool
    {
        return $this->block($admin, $target);
    }

    public function delete(User $admin, User $target): bool
    {
        return $this->block($admin, $target);
    }
}
