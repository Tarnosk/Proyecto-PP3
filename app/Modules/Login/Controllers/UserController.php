<?php

namespace App\Modules\Login\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Login\Models\User;
use App\Modules\Login\Requests\UpdateUserEmailRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(): View
    {
        $users = User::query()
            ->orderBy('nombre')
            ->get();

        return view('users.index', compact('users'));
    }

    public function updateEmail(UpdateUserEmailRequest $request, User $user): RedirectResponse
    {
        $user->update(['email' => $request->validated('email')]);

        return redirect()->route('users.index')
            ->with('success', "Email de {$user->usuario} actualizado correctamente.");
    }
}
