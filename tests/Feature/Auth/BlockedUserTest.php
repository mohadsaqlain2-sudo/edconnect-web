<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class BlockedUserTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A blocked user may not log in, even with the correct password (FR-6).
     */
    public function test_a_blocked_user_cannot_log_in(): void
    {
        User::factory()->blocked()->create([
            'email' => 'blocked@example.com',
        ]);

        $response = $this->from('/login')->post('/login', [
            'email' => 'blocked@example.com',
            'password' => 'password',
        ]);

        $this->assertGuest();
        $response->assertRedirect('/login');
        $response->assertSessionHasErrors('email');

        $this->assertStringContainsString(
            'blocked',
            (string) session('errors')->first('email')
        );
    }

    /**
     * A user who is blocked while logged in is kicked out on the next request (FR-6).
     */
    public function test_a_logged_in_user_is_logged_out_when_blocked(): void
    {
        $user = User::factory()->create();

        // The user is already authenticated (simulates a still-open session).
        Auth::login($user);

        // The administrator blocks the account.
        $user->forceFill(['is_blocked' => true])->save();

        $response = $this->get('/profile');

        $this->assertGuest();
        $response->assertRedirect('/login');
        $response->assertSessionHas('error');
    }
}
