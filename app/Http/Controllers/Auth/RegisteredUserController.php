<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        // Only allow registration if user is admin or no users exist yet
        if (User::count() > 0 && (!auth()->check() || !auth()->user()->isAdmin())) {
            abort(403, 'Registration is restricted to administrators only.');
        }

        return view('auth.register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        // Only allow registration if user is admin or no users exist yet
        if (User::count() > 0 && (!auth()->check() || !auth()->user()->isAdmin())) {
            abort(403, 'Registration is restricted to administrators only.');
        }

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'is_admin' => ['boolean'],
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'is_admin' => $request->boolean('is_admin', false),
        ]);

        event(new Registered($user));

        // Only auto-login if this is the first user (initial admin)
        if (User::count() === 1) {
            Auth::login($user);
            return redirect(route('dashboard', absolute: false));
        }

        // If admin is creating another user, redirect back to admin area
        return redirect()->route('dashboard')->with('success', 'User created successfully.');
    }
}
