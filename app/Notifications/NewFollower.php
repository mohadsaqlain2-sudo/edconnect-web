<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Queue\SerializesModels;

/**
 * "X started following you" (FR-23).
 *
 * The notification is queued (QUEUE_CONNECTION=database), so following somebody
 * answers immediately and the row is written by the queue worker.
 */
class NewFollower extends Notification implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public User $follower) {}

    /**
     * Only the database channel is used in EduConnect.
     *
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * The payload stored in the notifications table.
     * The views only print "message", "icon" and "url", so the sentence is
     * translated once, here.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'new_follower',
            'icon' => 'bi-person-plus',
            'actor_id' => $this->follower->id,
            'actor_name' => $this->follower->name,
            'actor_photo' => $this->follower->photoUrl(),
            'message' => __(':name started following you.', ['name' => $this->follower->name]),
            // Clicking the entry opens the follower's public profile (FR-25).
            'url' => route('users.show', $this->follower),
        ];
    }
}
