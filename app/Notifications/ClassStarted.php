<?php

namespace App\Notifications;

use App\Models\ClassRoom;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Queue\SerializesModels;

/**
 * "Your teacher just started the class" (FR-23).
 * Sent to every enrolled student the moment the teacher presses "Start class",
 * so they can open the class page and join the live room right away.
 */
class ClassStarted extends Notification implements ShouldQueue
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
            'type' => 'class_started',
            'icon' => 'bi-broadcast',
            'actor_id' => $this->teacher->id,
            'actor_name' => $this->teacher->name,
            'actor_photo' => $this->teacher->photoUrl(),
            'message' => __(':name started :title - join the live class now.', [
                'name' => $this->teacher->name,
                'title' => $this->classRoom->title,
            ]),
            // The class page then offers the Join button (FR-20).
            'url' => route('classes.show', $this->classRoom),
        ];
    }
}
