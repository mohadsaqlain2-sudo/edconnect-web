<?php

namespace App\Notifications;

use App\Models\Post;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Queue\SerializesModels;

/**
 * "X liked your post" (FR-23). Queued, database channel only.
 */
class PostLiked extends Notification implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public User $liker, public Post $post) {}

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
            'type' => 'post_liked',
            'icon' => 'bi-hand-thumbs-up',
            'actor_id' => $this->liker->id,
            'actor_name' => $this->liker->name,
            'actor_photo' => $this->liker->photoUrl(),
            'message' => __(':name liked your post.', ['name' => $this->liker->name]),
            'url' => route('posts.show', $this->post),
        ];
    }
}
