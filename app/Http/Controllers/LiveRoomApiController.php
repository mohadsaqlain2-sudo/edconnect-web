<?php

namespace App\Http\Controllers;

use App\Models\ClassRoom;
use App\Models\LiveSignal;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\Rule;

/**
 * The signaling API of the app's own WebRTC room (FR-18, FR-20, FR-22).
 *
 * The room runs entirely inside the application: the browser captures the
 * camera/microphone, peers exchange offers, answers and ICE candidates here,
 * and this controller keeps a small presence roster in the cache so every
 * participant knows who else is in the class. Nothing is embedded from an
 * external video server.
 *
 * Every action starts with $this->authorize('join', $classRoom), so the exact
 * same rule as the room page applies: the owning teacher, or an enrolled
 * student, and never a hidden class (ClassPolicy::join, FR-20, FR-28).
 */
class LiveRoomApiController extends Controller
{
    /**
     * Enter the room: register the presence and hand the client everything it
     * needs to build the peer connections.
     */
    public function join(Request $request, ClassRoom $classRoom): JsonResponse
    {
        $this->authorize('join', $classRoom);

        $user = $request->user();
        $isModerator = $classRoom->teacher_id === $user->id;

        // A student may only enter while the class is live; the teacher may
        // open the room at any time (FR-20). Same rule as the room page.
        if (! $isModerator && ! $classRoom->isLive()) {
            return response()->json(['message' => __('The class is not live yet.')], 409);
        }

        $this->pruneSignals($classRoom);

        // Read the cursor BEFORE announcing my presence. A peer discovers me by
        // seeing me in the roster, so every signal it sends afterwards gets a
        // higher id and is therefore delivered to my first poll. Reading the
        // cursor after joining would silently skip that first offer.
        $lastSignalId = (int) LiveSignal::query()
            ->where('class_id', $classRoom->id)
            ->max('id');

        $this->touchPresence($classRoom, $user);

        return response()->json([
            'self' => [
                'id' => $user->id,
                'name' => $user->name,
                'photo_url' => $user->photoUrl(),
                'is_moderator' => $isModerator,
            ],
            'participants' => $this->rosterFor($classRoom),
            'ice_servers' => [['urls' => config('live.stun')]],
            'last_signal_id' => $lastSignalId,
        ]);
    }

    /**
     * Refresh the presence and drop the signals nobody needs any more.
     */
    public function heartbeat(Request $request, ClassRoom $classRoom): JsonResponse
    {
        $this->authorize('join', $classRoom);

        $this->touchPresence($classRoom, $request->user());
        $this->pruneSignals($classRoom);

        return response()->json(['participants' => $this->rosterFor($classRoom)]);
    }

    /**
     * The polling endpoint: every message of this class newer than "after"
     * that is addressed to me, sent by me, or broadcast to the whole room.
     */
    public function signals(Request $request, ClassRoom $classRoom): JsonResponse
    {
        $this->authorize('join', $classRoom);

        $data = $request->validate([
            'after' => ['nullable', 'integer'],
        ]);

        $user = $request->user();
        $after = (int) ($data['after'] ?? 0);

        $this->touchPresence($classRoom, $user);
        $this->pruneSignals($classRoom);

        $rows = LiveSignal::query()
            ->where('class_id', $classRoom->id)
            ->where('id', '>', $after)
            // Broadcast (chat / bye / ICE), messages addressed to me, my own
            // offers and answers. Other people's signaling never leaks into my
            // poll.
            ->where(function ($query) use ($user) {
                $query->whereNull('target_id')
                    ->orWhere('target_id', $user->id)
                    ->orWhere('sender_id', $user->id);
            })
            ->orderBy('id')
            ->get();

        $messages = $rows
            ->map(fn (LiveSignal $signal) => [
                'id' => $signal->id,
                'from' => $signal->sender_id,
                'to' => $signal->target_id,
                'type' => $signal->type,
                'payload' => $signal->payload,
            ])
            ->all();

        // The cursor must never jump past a row that is invisible to me: it is
        // the highest id of the rows I am actually allowed to see, and it stays
        // where it was when there is nothing new.
        $lastId = $rows->max('id');
        $lastId = $lastId === null ? $after : (int) $lastId;

        return response()->json([
            'messages' => $messages,
            'participants' => $this->rosterFor($classRoom),
            'last_id' => $lastId,
        ]);
    }

    /**
     * One WebRTC message (offer / answer / ice) addressed to another peer.
     */
    public function signal(Request $request, ClassRoom $classRoom): JsonResponse
    {
        $this->authorize('join', $classRoom);

        $user = $request->user();
        $isModerator = $classRoom->teacher_id === $user->id;

        if (! $isModerator && ! $classRoom->isLive()) {
            return response()->json(['message' => __('The class is not live yet.')], 409);
        }

        $data = $request->validate([
            'type' => ['required', Rule::in(['offer', 'answer', 'ice'])],
            'to' => ['nullable', 'integer', 'exists:users,id'],
            'payload' => ['required', 'array'],
        ]);

        $signal = LiveSignal::create([
            'class_id' => $classRoom->id,
            'sender_id' => $user->id,
            'target_id' => $data['to'] ?? null,
            'type' => $data['type'],
            'payload' => $data['payload'],
        ]);

        return response()->json(['id' => $signal->id], 201);
    }

    /**
     * One chat line for everybody in the room (FR-22).
     */
    public function chat(Request $request, ClassRoom $classRoom): JsonResponse
    {
        $this->authorize('join', $classRoom);

        $user = $request->user();
        $isModerator = $classRoom->teacher_id === $user->id;

        if (! $isModerator && ! $classRoom->isLive()) {
            return response()->json(['message' => __('The class is not live yet.')], 409);
        }

        $data = $request->validate([
            'text' => ['required', 'string', 'max:500'],
        ]);

        $signal = LiveSignal::create([
            'class_id' => $classRoom->id,
            'sender_id' => $user->id,
            'target_id' => null,
            'type' => 'chat',
            'payload' => [
                'user_id' => $user->id,
                'name' => $user->name,
                'text' => $data['text'],
            ],
        ]);

        return response()->json(['id' => $signal->id], 201);
    }

    /**
     * Leave the room: drop my presence so the others stop looking for me, and
     * broadcast a "bye" so they also drop my tile right away instead of waiting
     * for the presence TTL to expire.
     */
    public function leave(Request $request, ClassRoom $classRoom): JsonResponse
    {
        $this->authorize('join', $classRoom);

        $user = $request->user();

        $this->forgetPresence($classRoom, $user);

        LiveSignal::create([
            'class_id' => $classRoom->id,
            'sender_id' => $user->id,
            'target_id' => null,
            'type' => 'bye',
            'payload' => [
                'user_id' => $user->id,
                'name' => $user->name,
            ],
        ]);

        return response()->json(['ok' => true]);
    }

    /* ---------------------------------------------------------------
     | Presence roster (cache backed)
     * --------------------------------------------------------------- */

    /**
     * Cache key of one class roster.
     */
    private function presenceKey(ClassRoom $classRoom): string
    {
        return "live:room:{$classRoom->id}:roster";
    }

    /**
     * Register (or refresh) my presence in the room.
     */
    private function touchPresence(ClassRoom $classRoom, User $user): void
    {
        $key = $this->presenceKey($classRoom);

        $roster = $this->readRoster($key);

        $roster[$user->id] = [
            'id' => $user->id,
            'name' => $user->name,
            // The profile photo the others show when this camera is off.
            'photo_url' => $user->photoUrl(),
            'moderator' => $classRoom->teacher_id === $user->id,
            'seen' => now()->getTimestamp(),
        ];

        Cache::put($key, $roster, now()->addMinutes(10));
    }

    /**
     * Remove my presence (I left for good).
     */
    private function forgetPresence(ClassRoom $classRoom, User $user): void
    {
        $key = $this->presenceKey($classRoom);

        $roster = $this->readRoster($key);
        unset($roster[$user->id]);

        Cache::put($key, $roster, now()->addMinutes(10));
    }

    /**
     * The current participants as a plain list, without the internal "seen".
     *
     * @return list<array{id: int, name: string, photo_url: string, moderator: bool}>
     */
    private function rosterFor(ClassRoom $classRoom): array
    {
        return array_values(array_map(
            static fn (array $entry) => [
                'id' => (int) $entry['id'],
                'name' => (string) $entry['name'],
                // A roster entry written before photos existed has none yet.
                'photo_url' => (string) ($entry['photo_url'] ?? ''),
                'moderator' => (bool) $entry['moderator'],
            ],
            $this->readRoster($this->presenceKey($classRoom))
        ));
    }

    /**
     * Read the roster and forget everybody who has not sent a heartbeat for
     * longer than config('live.presence_ttl') seconds.
     *
     * @return array<int, array{id: int, name: string, moderator: bool, seen: int}>
     */
    private function readRoster(string $key): array
    {
        $roster = Cache::get($key, []);

        if (! is_array($roster)) {
            return [];
        }

        $cutoff = now()->getTimestamp() - (int) config('live.presence_ttl');

        return array_filter(
            $roster,
            static fn ($entry) => is_array($entry) && ($entry['seen'] ?? 0) >= $cutoff
        );
    }

    /**
     * Delete the signaling rows of a class that are older than the configured
     * lifetime so the table does not grow forever.
     */
    private function pruneSignals(ClassRoom $classRoom): void
    {
        LiveSignal::query()
            ->where('class_id', $classRoom->id)
            ->where('created_at', '<', now()->subSeconds((int) config('live.signal_ttl')))
            ->delete();
    }
}
