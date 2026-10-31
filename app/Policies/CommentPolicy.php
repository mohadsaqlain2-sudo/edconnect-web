<?php

namespace App\Policies;

use App\Models\Comment;
use App\Models\User;

/**
 * Authorization for comments (FR-13).
 * You can delete your own comment; the author of the post can also delete
 * comments written under it, and admins keep full moderation rights.
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
     * Delete your own comment, a comment on your own post, or as an admin.
     */
    public function delete(User $user, Comment $comment): bool
    {
        return $user->id === $comment->user_id
            || $user->id === $comment->post?->user_id
            || $user->isAdmin();
    }
}
