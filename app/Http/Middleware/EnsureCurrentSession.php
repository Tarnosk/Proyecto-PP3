<?php

namespace App\Http\Middleware;

use App\Enums\UserStatus;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureCurrentSession
{
    public function handle(Request $request, Closure $next): Response
    {
        $lastActivity = (int) $request->session()->get('last_activity', now()->timestamp);
        $timeout = (int) config('session.lifetime', 120) * 60;

        if ((now()->timestamp - $lastActivity) > $timeout) {
            return $this->invalidate($request, 'session_expired');
        }

        $user = $request->user();
        $plainToken = (string) $request->session()->get('session_token', '');
        $storedToken = (string) ($user?->current_session_id ?? '');
        $isCurrent = $plainToken !== ''
            && $storedToken !== ''
            && hash_equals($storedToken, hash('sha256', $plainToken));

        if (! $user || $user->estado !== UserStatus::ACTIVO || ! $isCurrent) {
            return $this->invalidate($request, 'session_replaced');
        }

        $request->session()->put('last_activity', now()->timestamp);

        return $next($request);
    }

    private function invalidate(Request $request, string $reason): Response
    {
        $user = $request->user();
        $plainToken = (string) $request->session()->get('session_token', '');

        if ($user && $plainToken !== '') {
            $user->newQuery()
                ->whereKey($user->getKey())
                ->where('current_session_id', hash('sha256', $plainToken))
                ->update(['current_session_id' => null]);
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login', ['reason' => $reason]);
    }
}
