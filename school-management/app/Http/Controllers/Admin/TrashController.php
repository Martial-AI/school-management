<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DeletedItem;
use App\Models\LoginHistory;
use App\Models\ProgramLesson;
use App\Models\TeachingProgram;
use App\Models\Student;
use App\Models\SchoolClass;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;
use Spatie\Activitylog\Models\Activity;

class TrashController extends Controller
{
    public function index(): RedirectResponse
    {
        abort_unless(auth()->user()?->can('roles.manage'), 403);
        return to_route('dashboard');
    }

    public function restore(DeletedItem $deletedItem): RedirectResponse
    {
        abort_unless(auth()->user()?->can('roles.manage'), 403);
        $attributes = $deletedItem->payload['attributes'] ?? [];
        match ($deletedItem->type) {
            'activity' => Activity::query()->insert($attributes),
            'login_history' => LoginHistory::query()->insert($attributes),
            'program' => $this->restoreProgram($attributes, $deletedItem->payload['lessons'] ?? []),
            'student' => Student::query()->insert($attributes),
            'school_class' => SchoolClass::query()->insert($attributes),
            'user' => $this->restoreUser($attributes, $deletedItem->payload),
            default => abort(422, __('This item cannot be restored yet.')),
        };
        activity('corbeille')->causedBy(auth()->user())->log('a restauré : '.$deletedItem->label);
        $deletedItem->delete();
        return back()->with('success', __('Item restored successfully.'));
    }

    public function destroy(Request $request, DeletedItem $deletedItem): RedirectResponse
    {
        abort_unless(auth()->user()?->can('roles.manage'), 403);
        $request->validate(['admin_password' => ['required', 'string']]);
        abort_unless(Hash::check($request->input('admin_password'), auth()->user()->password), 422, __('The administrator password is incorrect.'));
        $label = $deletedItem->label;
        $deletedItem->delete();
        activity('corbeille')->causedBy(auth()->user())->log('a supprimé définitivement : '.$label);
        return back()->with('success', __('Permanent deletion completed.'));
    }

    public function destroyAll(Request $request): RedirectResponse
    {
        abort_unless(auth()->user()?->can('roles.manage'), 403);
        $request->validate(['admin_password' => ['required', 'string']]);
        abort_unless(Hash::check($request->input('admin_password'), auth()->user()->password), 422, __('The administrator password is incorrect.'));
        DeletedItem::query()->delete();
        return back()->with('success', __('The trash has been permanently emptied.'));
    }

    private function restoreProgram(array $attributes, array $lessons): void
    {
        $oldId = $attributes['id']; unset($attributes['id']);
        $program = TeachingProgram::create($attributes);
        foreach ($lessons as $lesson) { unset($lesson['id']); $lesson['teaching_program_id'] = $program->id; ProgramLesson::create($lesson); }
    }

    private function restoreUser(array $attributes, array $payload): void
    {
        User::query()->insert($attributes);
        $user = User::findOrFail($attributes['id']);
        $user->syncRoles($payload['roles'] ?? []);
        $user->syncPermissions($payload['permissions'] ?? []);
    }
}
