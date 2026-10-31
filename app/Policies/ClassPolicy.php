<?php

namespace App\Policies;

use App\Models\ClassRoom;
use App\Models\User;

/**
 * Authorization for live classes (FR-18, FR-20, FR-24, FR-28).
 * Laravel discovers this class automatically because it is called
 * ClassPolicy and lives next to the ClassRoom model namespace.
 */
class ClassPolicy
{
    /**
     * Who may read the class page? Everybody, unless an admin hid it (FR-28).
     * A hidden class stays visible for its own teacher and for admins.
     */
    public function view(User $user, ClassRoom $classRoom): bool
    {
        return ! $classRoom->is_hidden
            || $user->isAdmin()
            || $classRoom->teacher_id === $user->id;
    }

    /**
     * Only the teacher who created the class may change it. Admins keep
     * moderation rights over everything.
     */
    public function update(User $user, ClassRoom $classRoom): bool
    {
        return $classRoom->teacher_id === $user->id || $user->isAdmin();
    }

    /**
     * Same rule for deleting a class (FR-18).
     */
    public function delete(User $user, ClassRoom $classRoom): bool
    {
        return $this->update($user, $classRoom);
    }

    /**
     * Switching between scheduled / live / completed (FR-24).
     */
    public function changeStatus(User $user, ClassRoom $classRoom): bool
    {
        return $this->update($user, $classRoom);
    }

    /**
     * Who may open the live room (FR-20)?
     * Exactly the teacher who owns the class and the students enrolled in it.
     * Admins are moderators, not participants: they hide or delete a class
     * from the admin panel instead of entering a room with minors.
     * A class hidden by an admin is closed to everybody (FR-28).
     */
    public function join(User $user, ClassRoom $classRoom): bool
    {
        // Moderation: a hidden class cannot be joined by anybody.
        if ($classRoom->is_hidden) {
            return false;
        }

        // The teacher who owns the class.
        if ($classRoom->teacher_id === $user->id) {
            return true;
        }

        // Everybody else has to be an enrolled student of this class.
        return $classRoom->students()->whereKey($user->id)->exists();
    }
}
