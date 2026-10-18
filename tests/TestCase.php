<?php

namespace Tests;

use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Str;

abstract class TestCase extends BaseTestCase
{
    protected function signInAs(User $user): static
    {
        $token = Str::random(64);
        $user->forceFill(['current_session_id' => hash('sha256', $token)])->save();

        $this->actingAs($user);
        $this->withSession([
            'session_token' => $token,
            'last_activity' => now()->timestamp,
        ]);

        return $this;
    }
}
