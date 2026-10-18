<?php

namespace Tests\Feature;

use App\Mail\PasswordRecoveryCode;
use App\Models\PasswordResetCode;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class PasswordRecoveryTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_displays_password_recovery_link(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('¿Olvidó su contraseña?')
            ->assertSee(route('password.request'));
    }

    public function test_active_user_receives_a_hashed_six_digit_code(): void
    {
        Mail::fake();
        $user = User::factory()->create(['email' => 'usuario@example.com']);

        $this->post(route('password.email'), ['email' => 'usuario@example.com'])
            ->assertRedirect(route('password.code'))
            ->assertSessionHas('success');

        $reset = PasswordResetCode::query()->where('usuario_id', $user->id)->firstOrFail();

        $this->assertTrue($reset->expires_at->isFuture());
        Mail::assertSent(PasswordRecoveryCode::class, function (PasswordRecoveryCode $mail) use ($reset, $user): bool {
            return $mail->hasTo($user->email)
                && preg_match('/^\d{6}$/', $mail->recoveryCode) === 1
                && Hash::check($mail->recoveryCode, $reset->code_hash);
        });
    }

    public function test_unknown_email_receives_generic_response_without_sending_mail(): void
    {
        Mail::fake();

        $this->post(route('password.email'), ['email' => 'desconocido@example.com'])
            ->assertRedirect(route('password.code'))
            ->assertSessionHas('success');

        Mail::assertNothingSent();
        $this->assertDatabaseCount('password_reset_codes', 0);
    }

    public function test_wrong_code_increments_attempts(): void
    {
        $user = User::factory()->create(['email' => 'usuario@example.com']);
        PasswordResetCode::query()->create([
            'usuario_id' => $user->id,
            'code_hash' => Hash::make('123456'),
            'expires_at' => now()->addMinutes(15),
        ]);

        $this->withSession(['password_recovery_email' => $user->email])
            ->post(route('password.code.verify'), ['code' => '654321'])
            ->assertSessionHasErrors('code');

        $this->assertDatabaseHas('password_reset_codes', [
            'usuario_id' => $user->id,
            'intentos' => 1,
        ]);
    }

    public function test_verified_code_allows_password_change_and_clears_security_state(): void
    {
        Mail::fake();
        $user = User::factory()->create([
            'email' => 'usuario@example.com',
            'intentos_fallidos' => 3,
            'bloqueado_hasta' => now()->addMinutes(10),
            'current_session_id' => hash('sha256', 'old-session'),
        ]);
        $code = null;

        $this->post(route('password.email'), ['email' => $user->email]);

        Mail::assertSent(PasswordRecoveryCode::class, function (PasswordRecoveryCode $mail) use (&$code): bool {
            $code = $mail->recoveryCode;

            return true;
        });

        $this->post(route('password.code.verify'), ['code' => $code])
            ->assertRedirect(route('password.reset'));

        $this->post(route('password.update'), [
            'password' => 'NuevaClave1234',
            'password_confirmation' => 'NuevaClave1234',
        ])->assertRedirect(route('login'))
            ->assertSessionHas('success');

        $user->refresh();
        $this->assertTrue(Hash::check('NuevaClave1234', $user->password_hash));
        $this->assertSame(0, $user->intentos_fallidos);
        $this->assertNull($user->bloqueado_hasta);
        $this->assertNull($user->current_session_id);
        $this->assertDatabaseMissing('password_reset_codes', ['usuario_id' => $user->id]);
    }

    public function test_expired_code_is_rejected(): void
    {
        $user = User::factory()->create(['email' => 'usuario@example.com']);
        PasswordResetCode::query()->create([
            'usuario_id' => $user->id,
            'code_hash' => Hash::make('123456'),
            'expires_at' => now()->subMinute(),
        ]);

        $this->withSession(['password_recovery_email' => $user->email])
            ->post(route('password.code.verify'), ['code' => '123456'])
            ->assertSessionHasErrors('code');

        $this->assertDatabaseMissing('password_reset_codes', ['usuario_id' => $user->id]);
    }
}
