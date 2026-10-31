<?php

namespace App\Notifications;

use App\Models\ClassRoom;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Queue\SerializesModels;

/**
 * "The teacher you follow created a class" (FR-23).
 * One notification per follower, sent when the class is created.
 */
class NewClassFromFollowedTeacher extends Notification implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public User $teacher, public ClassRoom $classRoom) {}

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
            'type' => 'new_class',
            'icon' => 'bi-easel',
            'actor_id' => $this->teacher->id,
            'actor_name' => $this->teacher->name,
            'actor_photo' => $this->teacher->photoUrl(),
            'message' => __(':name scheduled a new class: :title', [
                'name' => $this->teacher->name,
                'title' => $this->classRoom->title,
            ]),
            'url' => route('classes.show', $this->classRoom),
        ];
    }
}
