<?php

namespace App\Http\Controllers;

use App\Models\ClassRoom;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * The Join Class page (FR-18, FR-20, FR-22).
 * The page embeds Jitsi Meet through the External API inside an iframe, so the
 * chat, the mute buttons and screen sharing all come from Jitsi itself.
 */
class LiveClassController extends Controller
{
    /**
     * Open the live room of a class.
     *
     * Access (FR-20): exactly the teacher who owns the class and the enrolled
     * students. Everybody else gets a 403 from ClassPolicy::join(), and a hidden
     * class cannot be joined at all (FR-28). Admins moderate from the admin
     * panel instead of entering a room with minors.
     */
    public function __invoke(Request $request, ClassRoom $classRoom): View|RedirectResponse
    {
        // 403 for an unenrolled student, an admin, a stranger or a hidden class.
        $this->authorize('join', $classRoom);

        $user = $request->user();

        // Only the teacher moderates the room; a student watches/listens. The
        // flag is passed to the view and to jitsi.js.
        $isModerator = $classRoom->teacher_id === $user->id;

        // A student can only enter while the class is live (FR-20). The teacher
        // may open the room at any time.
        if (! $isModerator && ! $classRoom->isLive()) {
            return redirect()
                ->route('classes.show', $classRoom)
                ->with('error', __('This class is not live yet. You can join as soon as the teacher starts it.'));
        }

        // The room header shows who is teaching, so eager load the teacher.
        $classRoom->load('teacher:id,name,subject');

        return view('classes.room', [
            'classRoom' => $classRoom,
            // Configurable through JITSI_DOMAIN / JITSI_SCRIPT_URL in .env,
            // so the same page works against a self hosted Jitsi (FR-18).
            'domain' => config('jitsi.domain'),
            'scriptUrl' => config('jitsi.script_url'),
            'roomName' => $classRoom->room_name,
            // Jitsi shows this name in the participant list: it is escaped in the
            // view and JSON encoded for the JavaScript configuration.
            'displayName' => $user->name,
            'email' => $user->email,
            'isModerator' => $isModerator,
        ]);
    }
}
