<?php

namespace App\Policies;

use App\Models\Post;
use App\Models\User;

/**
 * Authorization for posts (FR-10, FR-27).
 * Laravel finds this class automatically because it sits in App\Policies and
 * is named PostPolicy for the App\Models\Post model.
 */
class PostPolicy
{
    /**
     * Who may read a post? Everybody, unless an admin hid it (FR-27) - then
     * only the author and admins still see it.
     */
    public function view(User $user, Post $post): bool
    {
        return ! $post->is_hidden
            || $user->isAdmin()
            || $user->id === $post->user_id;
    }

    /**
     * Only the author may edit their own post. Editing changes the author's
     * words, so administrators moderate content without rewriting it.
     */
    public function update(User $user, Post $post): bool
    {
        return $user->id === $post->user_id;
    }

    /**
     * The author may delete their own post; an administrator may remove any
     * post for moderation (FR-10, FR-28).
     */
    public function delete(User $user, Post $post): bool
    {
        return $user->id === $post->user_id || $user->isAdmin();
    }
}
