<?php

namespace App\Notifications;

use App\Models\ClassRoom;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Queue\SerializesModels;

/**
 * "Your class starts in a few minutes" (FR-23).
 * Built by the scheduled command classes:notify-starting-soon and sent to the
 * teacher plus every enrolled student, once per class.
 */
class ClassStartingSoon extends Notification implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public ClassRoom $classRoom) {}

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
            'type' => 'class_starting_soon',
            'icon' => 'bi-alarm',
            'actor_id' => $this->classRoom->teacher_id,
            'actor_name' => $this->classRoom->teacher?->name,
            'actor_photo' => $this->classRoom->teacher?->photoUrl(),
            'message' => __(':title starts at :time - get ready to join.', [
                'title' => $this->classRoom->title,
                'time' => $this->classRoom->startsAt()->format('H:i'),
            ]),
            // The class page then offers the Join button (FR-20).
            'url' => route('classes.show', $this->classRoom),
        ];
    }
}
