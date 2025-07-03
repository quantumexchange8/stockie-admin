<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(Request $request): Response
    {
        $message = $request->session()->get('message');

        return Inertia::render('Auth/Login', [
            'canResetPassword' => Route::has('password.request'),
            'status' => session('status'),
            'message' => $message ?? [],
        ]);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        // Get the authenticated user
        $user = Auth::user();

        $isUserLoggedIn = DB::table('sessions')
            ->where('user_id', $user->id)
            ->when(session()->getId(), function ($query, $sessionId) {
                return $query->where('id', '!=', $sessionId);
            })
            ->exists();

        // If user has active session elsewhere AND no confirmation
        if ($isUserLoggedIn && !$request->confirm_login) {
            Auth::logout(); // Log out the current attempt
            return redirect()->back()
                ->withErrors(['user_has_session' => true])
                ->withInput();
        }

        // Invalidate all other sessions
        if ($isUserLoggedIn) {
            $this->logoutOtherDevices($user);
        }

        $request->session()->regenerate();
        
        $message = [ 
            'severity' => 'success', 
            'summary' => "You've logged in to Stockie."
        ];

        return redirect()->intended('dashboard')->with([
            'user' => auth()->user(),
            'message' => $message,
        ]);
    }

    protected function logoutOtherDevices($user): void
    {
        // Delete all other sessions from the database
        DB::table('sessions')
            ->where('user_id', $user->id)
            ->where('id', '!=', session()->getId())
            ->delete();
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        $message = [ 
            'severity' => 'success', 
            'summary' => "You've logged out your Stockie account."
        ];

        return redirect('login')->with(['message' => $message]);
    }
}
