<?php

namespace App\Policies;

use App\Models\Comment;
use App\Models\User;

/**
 * Authorization for comments (FR-10).
 * A member can delete their own comment; administrators can delete any
 * comment for moderation. The author of the post does not own the comments
 * other people write under it, so they cannot delete them (FR-10).
 */
class CommentPolicy
{
    /**
     * Who may read a comment? Everybody, unless it was hidden (FR-27).
     */
    public function view(User $user, Comment $comment): bool
    {
        return ! $comment->is_hidden
            || $user->isAdmin()
            || $user->id === $comment->user_id;
    }

    /**
     * Delete your own comment, or any comment as an administrator.
     */
    public function delete(User $user, Comment $comment): bool
    {
        return $user->id === $comment->user_id
            || $user->isAdmin();
    }
}
