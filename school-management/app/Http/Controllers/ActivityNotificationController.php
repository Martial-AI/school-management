<?php

namespace App\Http\Controllers;

use App\Services\ActivityDescriptionLocalizer;
use App\Services\ActivityDestinationResolver;
use App\Services\TrashService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Spatie\Activitylog\Models\Activity;

class ActivityNotificationController extends Controller
{
    public function destroy(Request $request): JsonResponse
    {
        abort_unless($request->user()->can('activity.delete'), 403);
        $data = $request->validate(['ids' => ['required', 'array'], 'ids.*' => ['integer']]);
        $activities = Activity::whereIn('id', $data['ids'])->where('log_name', '!=', 'connexion')->get();
        $deletable = $activities;
        foreach ($deletable as $activity) TrashService::store('activity', $activity, 'Notification : '.$activity->description);
        $deletedIds = $deletable->pluck('id')->values();
        $deletable->each->delete();
        return response()->json(['ok' => true, 'deleted' => $deletable->count(), 'deleted_ids' => $deletedIds]);
    }
    public function markRead(Request $request): JsonResponse
    {
        abort_unless($request->user()->can('activity.view'), 403);
        $request->user()->update(['activity_notifications_read_at' => now()]);
        return response()->json(['ok' => true]);
    }

    public function feed(Request $request): JsonResponse
    {
        abort_unless($request->user()->can('activity.view'), 403);
        $after = $request->filled('after') ? Carbon::parse($request->string('after')->toString()) : now()->subMinute();
        $activities = Activity::with(['causer', 'subject'])->where('log_name', '!=', 'connexion')->where('created_at', '>', $after)->oldest()->get()->map(fn (Activity $activity) => [
            'id' => $activity->id,
            'description' => ActivityDescriptionLocalizer::localize($activity->description),
            'causer' => $activity->causer?->localizedFunctionLabel() ?? __('System'),
            'created_at' => $activity->created_at->toISOString(),
            'displayed_at' => $activity->created_at->format('d/m/Y H:i'),
            'url' => ActivityDestinationResolver::url($activity),
        ]);
        $lastSeen = $request->user()->activity_notifications_read_at;
        $unreadCount = Activity::where('log_name', '!=', 'connexion')->when($lastSeen, fn ($query) => $query->where('created_at', '>', $lastSeen))->count();
        return response()->json(['activities' => $activities, 'unread_count' => $unreadCount]);
    }
}
