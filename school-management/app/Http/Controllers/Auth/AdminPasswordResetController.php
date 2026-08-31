<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\AdminPasswordResetCode;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rules;
use Illuminate\View\View;
use Throwable;

class AdminPasswordResetController extends Controller
{
    public function create(): View
    {
        return view('auth.forgot-password');
    }

    public function sendCode(Request $request): RedirectResponse
    {
        $data = $request->validate(['email' => ['required', 'email']]);
        $user = User::where('email', $data['email'])->first();

        if (! $user || ! $user->is_active) {
            return back()->withInput()->withErrors(['email' => __('This account is unknown or suspended. Please contact the administrator.')]);
        }

        $code = (string) random_int(100000, 999999);
        AdminPasswordResetCode::where('user_id', $user->id)->delete();
        AdminPasswordResetCode::create(['user_id' => $user->id, 'code' => Hash::make($code), 'expires_at' => now()->addMinutes(15)]);

        try {
            Mail::raw(__('Your GUT Center administrator password confirmation code is: :code. It is valid for 15 minutes.', ['code' => $code]), function ($message) use ($user): void {
                $message->to($user->email)->subject(__('GUT Center password reset code'));
            });
        } catch (Throwable) {
            AdminPasswordResetCode::where('user_id', $user->id)->delete();
            return back()->withInput()->withErrors(['email' => __('Unable to send the email. Check the Gmail configuration and the application password.')]);
        }

        return to_route('password.code.form', ['email' => $user->email])->with('status', __('A confirmation code has been sent to your email address.'));
    }

    public function showCodeForm(Request $request): View
    {
        return view('auth.reset-password-code', ['email' => $request->string('email')->toString()]);
    }

    public function reset(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'code' => ['required', 'digits:6'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);
        $user = User::where('email', $data['email'])->first();
        $resetCode = $user ? AdminPasswordResetCode::where('user_id', $user->id)->latest()->first() : null;

        if (! $user || ! $user->is_active || ! $resetCode || $resetCode->expires_at->isPast() || ! Hash::check($data['code'], $resetCode->code)) {
            return back()->withInput($request->only('email'))->withErrors(['code' => __('The confirmation code is invalid or has expired.')]);
        }

        $user->update(['password' => Hash::make($data['password']), 'remember_token' => null]);
        AdminPasswordResetCode::where('user_id', $user->id)->delete();

        return to_route('login')->with('status', __('Password reset successfully.'));
    }
}
