<?php

namespace App\Http\Controllers\Auth;

use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Http\Requests\Auth\VerifyPasswordResetCodeRequest;
use App\Mail\PasswordRecoveryCode;
use App\Models\PasswordResetCode;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PasswordRecoveryController extends Controller
{
    private const CODE_EXPIRATION_MINUTES = 15;

    private const VERIFICATION_EXPIRATION_MINUTES = 10;

    private const MAX_CODE_ATTEMPTS = 5;

    public function request(): View
    {
        return view('auth.forgot-password');
    }

    public function sendCode(ForgotPasswordRequest $request): RedirectResponse
    {
        $email = $request->validated('email');
        $user = User::query()
            ->where('email', $email)
            ->where('estado', UserStatus::ACTIVO)
            ->first();

        if ($user) {
            $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

            PasswordResetCode::query()->updateOrCreate(
                ['usuario_id' => $user->id],
                [
                    'code_hash' => Hash::make($code),
                    'intentos' => 0,
                    'expires_at' => now()->addMinutes(self::CODE_EXPIRATION_MINUTES),
                ],
            );

            Mail::to($user->email)->send(new PasswordRecoveryCode($code, $user->nombre));
        }

        $request->session()->forget([
            'password_recovery_user_id',
            'password_recovery_verified_at',
        ]);
        $request->session()->put('password_recovery_email', $email);

        return redirect()->route('password.code')
            ->with('success', 'Si el email pertenece a una cuenta activa, recibirá un código de recuperación.');
    }

    public function code(Request $request): View|RedirectResponse
    {
        $email = $request->session()->get('password_recovery_email');

        if (! is_string($email) || $email === '') {
            return redirect()->route('password.request');
        }

        return view('auth.verify-reset-code', compact('email'));
    }

    public function verifyCode(VerifyPasswordResetCodeRequest $request): RedirectResponse
    {
        $email = (string) $request->session()->get('password_recovery_email', '');
        $user = User::query()
            ->where('email', $email)
            ->where('estado', UserStatus::ACTIVO)
            ->first();
        $reset = $user
            ? PasswordResetCode::query()->where('usuario_id', $user->id)->first()
            : null;

        if (! $reset || $reset->expires_at->isPast() || $reset->intentos >= self::MAX_CODE_ATTEMPTS) {
            $reset?->delete();
            throw ValidationException::withMessages([
                'code' => 'El código es incorrecto o venció. Solicite uno nuevo.',
            ]);
        }

        if (! Hash::check($request->validated('code'), $reset->code_hash)) {
            $reset->increment('intentos');

            if ($reset->fresh()->intentos >= self::MAX_CODE_ATTEMPTS) {
                $reset->delete();
            }

            throw ValidationException::withMessages([
                'code' => 'El código es incorrecto o venció. Solicite uno nuevo.',
            ]);
        }

        $request->session()->put([
            'password_recovery_user_id' => $user->id,
            'password_recovery_verified_at' => now()->timestamp,
        ]);

        return redirect()->route('password.reset');
    }

    public function reset(Request $request): View|RedirectResponse
    {
        if (! $this->verifiedUser($request)) {
            return redirect()->route('password.request')
                ->withErrors(['email' => 'La verificación venció. Solicite un código nuevo.']);
        }

        return view('auth.reset-password');
    }

    public function update(ResetPasswordRequest $request): RedirectResponse
    {
        $user = $this->verifiedUser($request);

        if (! $user) {
            return redirect()->route('password.request')
                ->withErrors(['email' => 'La verificación venció. Solicite un código nuevo.']);
        }

        DB::transaction(function () use ($request, $user): void {
            $user->forceFill([
                'password_hash' => Hash::make($request->validated('password')),
                'intentos_fallidos' => 0,
                'bloqueado_hasta' => null,
                'ultimo_intento_fallido' => null,
                'current_session_id' => null,
            ])->save();

            PasswordResetCode::query()->where('usuario_id', $user->id)->delete();
        });

        $request->session()->forget([
            'password_recovery_email',
            'password_recovery_user_id',
            'password_recovery_verified_at',
        ]);

        return redirect()->route('login')
            ->with('success', 'La contraseña se actualizó correctamente. Ya puede iniciar sesión.');
    }

    private function verifiedUser(Request $request): ?User
    {
        $userId = $request->session()->get('password_recovery_user_id');
        $verifiedAt = (int) $request->session()->get('password_recovery_verified_at', 0);

        if (! $userId || $verifiedAt < now()->subMinutes(self::VERIFICATION_EXPIRATION_MINUTES)->timestamp) {
            return null;
        }

        return User::query()
            ->whereKey($userId)
            ->where('estado', UserStatus::ACTIVO)
            ->first();
    }
}
