<?php

namespace App\Notifications;

use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;

/**
 * "X commented on your post" (FR-23). Queued, database channel only.
 */
class PostCommented extends Notification implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $commenter,
        public Post $post,
        public Comment $comment,
    ) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'post_commented',
            'icon' => 'bi-chat-dots',
            'actor_id' => $this->commenter->id,
            'actor_name' => $this->commenter->name,
            'actor_photo' => $this->commenter->photoUrl(),
            'message' => __(':name commented on your post.', ['name' => $this->commenter->name]),
            // A short preview so the entry makes sense in the dropdown.
            'excerpt' => Str::limit($this->comment->body, 60),
            'url' => route('posts.show', $this->post),
        ];
    }
}
