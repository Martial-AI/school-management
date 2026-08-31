<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LoginHistory;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Services\TrashService;
use Illuminate\View\View;
use Spatie\Activitylog\Models\Activity;

class HistoryController extends Controller
{
    public function destroyLogins(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('login_history.delete'), 403);
        $data = $request->validate(['ids' => ['required', 'array'], 'ids.*' => ['integer']]);
        $histories = LoginHistory::whereIn('id', $data['ids'])->get();
        foreach ($histories as $history) TrashService::store('login_history', $history, 'Historique de connexion : '.$history->logged_in_at);
        $deleted = $histories->each->delete()->count();
        return response()->json(['ok' => true, 'deleted' => $deleted]);
    }
    public function index(Request $request): View
    {
        abort_unless(auth()->user()?->can('login_history.view'), 403);

        $date = $request->string('date')->toString();
        $loginQuery = LoginHistory::with('user')->latest('logged_in_at');
        $activityQuery = Activity::with('causer')->where('log_name', '!=', 'connexion')->latest();
        if ($date !== '') {
            $loginQuery->whereDate('logged_in_at', $date);
            $activityQuery->whereDate('created_at', $date);
        }

        $allLogins = $loginQuery->limit(500)->get();
        $loginUsers = $allLogins->groupBy('user_id')->map(function ($histories) {
            $latest = $histories->first();
            $minutes = $latest->logged_out_at ? (int) $latest->logged_out_at->diffInMinutes(now()) : null;
            $offlineLabel = __('Offline');
            if ($minutes !== null && $minutes < 1440) {
                $offlineLabel .= $minutes < 60
                    ? ' · '.$minutes.' min'
                    : ' · '.intdiv($minutes, 60).' h'.($minutes % 60 ? ' '.($minutes % 60).' min' : '');
            }
            return [
                'user' => $latest->user,
                'latest' => $latest,
                'histories' => $histories,
                'is_online' => is_null($latest->logged_out_at),
                'offline_label' => $offlineLabel,
            ];
        })->sortByDesc(fn ($item) => $item['latest']->logged_in_at)->values();

        return view('admin.history.index', [
            'loginUsers' => $loginUsers,
            'activities' => $activityQuery->limit(100)->get(),
            'selectedDate' => $date,
        ]);
    }
}
