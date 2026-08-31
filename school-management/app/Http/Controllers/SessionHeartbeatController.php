<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SessionHeartbeatController extends Controller
{
    public function ping(Request $request): JsonResponse
    {
        DB::table('active_sessions')->updateOrInsert(['session_id' => $request->session()->getId()], ['user_id' => $request->user()->id, 'last_seen_at' => now(), 'ended_at' => null, 'updated_at' => now(), 'created_at' => now()]);
        return response()->json(['ok' => true]);
    }

    public function close(Request $request): JsonResponse
    {
        DB::table('active_sessions')->where('session_id', $request->session()->getId())->update(['ended_at' => now(), 'updated_at' => now()]);
        return response()->json(['ok' => true]);
    }
}
