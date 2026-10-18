<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class SessionSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_session_expires_after_two_hours_of_inactivity(): void
    {
        $user = User::factory()->create();
        $this->signInAs($user)->withSession([
            'last_activity' => now()->subHours(2)->subSecond()->timestamp,
        ]);

        $this->get(route('dashboard'))
            ->assertRedirect(route('login', ['reason' => 'session_expired']));

        $this->assertGuest();
    }

    public function test_previous_session_is_invalidated_when_another_session_replaces_it(): void
    {
        $user = User::factory()->create();
        $this->signInAs($user);

        $replacementToken = Str::random(64);
        $user->forceFill([
            'current_session_id' => hash('sha256', $replacementToken),
        ])->save();

        $this->get(route('dashboard'))
            ->assertRedirect(route('login', ['reason' => 'session_replaced']));

        $this->assertGuest();
        $this->assertSame(hash('sha256', $replacementToken), $user->fresh()->current_session_id);
    }
}
