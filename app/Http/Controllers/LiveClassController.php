<?php

namespace App\Http\Controllers;

use App\Models\ClassRoom;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * The Join Class page (FR-18, FR-20, FR-22).
 * The page hosts the application's own WebRTC room: the browser captures the
 * camera and the microphone, and resources/js/live-room.js talks to our own
 * signaling API (LiveRoomApiController) to connect the peers. No external
 * video server is loaded.
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
    public function __invoke(Request $request, ClassRoom $classRoom): View
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
            return view('classes.not-started', ['classRoom' => $classRoom]);
        }

        // The room header shows who is teaching, so eager load the teacher.
        $classRoom->load('teacher:id,name,subject');

        return view('classes.room', [
            'classRoom' => $classRoom,
            'roomName' => $classRoom->room_name,
            // The name shown on my own tile: it is escaped in the view and
            // travels in the data-* attributes the client reads.
            'displayName' => $user->name,
            'email' => $user->email,
            'subject' => $classRoom->title,
            'avatarUrl' => url($user->photoUrl()),
            'isModerator' => $isModerator,
            // The ICE servers handed to RTCPeerConnection. Only a public STUN
            // server is needed: peers inside one school network connect
            // directly (config/live.php).
            'iceServers' => [['urls' => config('live.stun')]],
        ]);
    }

    /**
     * Landing spot after the user hangs up (FR-20).
     * The live room is client side, so the redirect happens here and the
     * normal "back" page receives the flash message.
     */
    public function leave(Request $request, ClassRoom $classRoom): RedirectResponse
    {
        $this->authorize('join', $classRoom);

        return redirect()
            ->route('classes.show', $classRoom)
            ->with('success', __('You have left the class room.'));
    }
}
