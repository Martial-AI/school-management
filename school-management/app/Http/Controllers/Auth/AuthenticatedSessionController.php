<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use App\Models\LoginApproval;
use App\Models\User;
use App\Services\DeviceNameResolver;
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
        $request->ensureIsNotRateLimited();
        $user = User::where('email', $request->string('email')->toString())->first();
        if (! $user || ! Hash::check($request->string('password')->toString(), $user->password)) {
            RateLimiter::hit($request->throttleKey());
            return back()->withInput($request->only('email', 'remember'))->withErrors(['email' => trans('auth.failed')]);
        }
        if (! $user->is_active) return back()->withInput()->withErrors(['email' => __('Your account has been suspended. Contact the Manager.')]);

        // Mobile browsers throttle JavaScript while in the background; keep a reasonable window for approval requests.
        $activeSession = DB::table('active_sessions')->where('user_id', $user->id)->whereNull('ended_at')->where('last_seen_at', '>=', now()->subMinutes(3))->orderByDesc('last_seen_at')->first();
        if ($activeSession) {
            LoginApproval::where('user_id', $user->id)->where('requester_session_id', $request->session()->getId())->where('status', 'pending')->update(['status' => 'expired', 'resolved_at' => now()]);
            $approval = LoginApproval::create(['user_id' => $user->id, 'current_session_id' => $activeSession->session_id, 'requester_session_id' => $request->session()->getId(), 'requester_ip_address' => $request->ip(), 'requester_user_agent' => $request->userAgent(), 'requester_device_model' => $request->input('device_model'), 'requester_device_platform' => DeviceNameResolver::platformFromUserAgent($request->userAgent()), 'requester_device_browser' => $request->input('device_browser') ?: DeviceNameResolver::browserFromUserAgent($request->userAgent()), 'remember' => $request->boolean('remember'), 'status' => 'pending', 'expires_at' => now()->addMinutes(3)]);
            $request->session()->put('pending_login_approval', $approval->id);
            return back()->withInput($request->only('email'))->with('login_pending', true);
        }

        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();
        DB::table('active_sessions')->updateOrInsert(['session_id' => $request->session()->getId()], ['user_id' => $user->id, 'last_seen_at' => now(), 'ended_at' => null, 'updated_at' => now(), 'created_at' => now()]);
        RateLimiter::clear($request->throttleKey());

        return redirect()->intended(route('dashboard', absolute: false));
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
