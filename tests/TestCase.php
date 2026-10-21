<?php

namespace Tests;

use App\Modules\Login\Models\LoginLog;
use App\Modules\Login\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        RefreshDatabaseState::$migrated = true;

        parent::setUp();

        if (Schema::hasTable('login_logs')) {
            LoginLog::query()->delete();
        }
        if (Schema::hasTable('usuarios')) {
            User::whereIn('usuario', ['admin', 'repartidor', 'contador', 'inactivo'])->delete();
        }
    }

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
