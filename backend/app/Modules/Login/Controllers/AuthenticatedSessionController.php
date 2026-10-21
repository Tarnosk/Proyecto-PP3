<?php

namespace App\Modules\Login\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Login\Requests\LoginRequest;
use App\Modules\Login\Services\AuthenticationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    public function __construct(private readonly AuthenticationService $authentication) {}

    public function create(Request $request): View
    {
        return view('auth.login', ['reason' => (string) $request->query('reason', '')]);
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $user = $this->authentication->authenticate(
            $request->string('usuario')->toString(),
            (string) $request->input('password'),
        );

        $this->authentication->startSession($user, $request);

        return redirect()->intended(route('dashboard'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        $this->authentication->endSession($request);

        return redirect()->route('login');
    }
}
