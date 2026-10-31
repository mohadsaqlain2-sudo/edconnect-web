<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Who may report which content (FR-32).
 */
class ReportPolicy
{
    /**
     * A user may report a post or comment that belongs to somebody else.
     * The duplicate check lives in the controller so that the user gets a
     * friendly flash message instead of a bare 403.
     */
    public function create(User $user, Model $reportable): bool
    {
        $ownerId = $reportable->user_id ?? null;

        return $ownerId !== null && $ownerId !== $user->id;
    }
}
