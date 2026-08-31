<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\LoginApproval;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginApprovalController extends Controller
{
    public function active(Request $request): JsonResponse
    {
        $approval = LoginApproval::with('user')->where('user_id', $request->user()->id)->where('status', 'pending')->where('expires_at', '>', now())->latest()->first();
        return response()->json(['pending' => (bool) $approval, 'approval_id' => $approval?->id, 'requester' => $approval?->user?->email, 'requester_device' => $approval?->requester_device_name, 'requester_platform' => $approval?->requester_device_platform, 'requester_ip' => $approval?->requester_ip_address]);
    }

    public function decide(Request $request, LoginApproval $approval): JsonResponse
    {
        abort_unless($approval->user_id === $request->user()->id && $approval->status === 'pending' && $approval->expires_at->isFuture(), 403);
        $data = $request->validate(['decision' => ['required', 'in:approved,declined']]);
        $approval->update(['status' => $data['decision'], 'resolved_at' => now()]);
        if ($data['decision'] === 'approved') {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }
        return response()->json(['status' => $data['decision'], 'redirect' => $data['decision'] === 'approved' ? route('login') : null]);
    }

    public function status(Request $request): JsonResponse
    {
        $approval = LoginApproval::with('user')->where('requester_session_id', $request->session()->getId())->latest()->first();
        if (! $approval || $approval->expires_at->isPast()) { $request->session()->forget('pending_login_approval'); return response()->json(['status' => 'expired']); }
        if ($approval->status === 'approved') {
            Auth::login($approval->user, $approval->remember);
            $request->session()->regenerate();
            $approval->update(['status' => 'completed']);
            return response()->json(['status' => 'approved', 'redirect' => route('dashboard')]);
        }
        if ($approval->status === 'declined') {
            $request->session()->forget('pending_login_approval');
            $approval->update(['status' => 'completed']);
            return response()->json(['status' => 'declined']);
        }
        if ($approval->status === 'cancelled') { $request->session()->forget('pending_login_approval'); return response()->json(['status' => 'cancelled']); }
        return response()->json(['status' => $approval->status]);
    }

    public function cancel(Request $request, LoginApproval $approval): JsonResponse
    {
        abort_unless($approval->requester_session_id === $request->session()->getId() && $approval->status === 'pending', 403);
        $approval->update(['status' => 'cancelled', 'resolved_at' => now()]);
        $request->session()->forget('pending_login_approval');
        return response()->json(['status' => 'cancelled']);
    }
}
