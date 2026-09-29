<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        $redirectUrl = route('dashboard', absolute: false);

        // Discard any AJAX/API endpoint stored as "intended URL" so
        // the user lands on their dashboard instead of seeing raw JSON.
        $ajaxEndpoints = [
            'notifications/',
            'help-agent/',
            'agent/',
            'work-log/user-tasks',
            'org-chart/',
        ];
        $intended = $request->session()->get('url.intended', '');
        foreach ($ajaxEndpoints as $endpoint) {
            if (str_contains($intended, $endpoint)) {
                $request->session()->forget('url.intended');
                break;
            }
        }

        return redirect()->intended($redirectUrl);
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
