<?php

namespace App\Http\Controllers;

use App\Http\Requests\Users\UpdateUserEmailRequest;
use App\Models\User;
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
