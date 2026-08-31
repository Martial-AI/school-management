<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();
        $user->fill($request->validated());
        $changed = array_keys($user->getDirty());

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();
        if ($changed) {
            $labels = ['name' => 'nom', 'email' => 'e-mail', 'phone' => 'téléphone', 'address' => 'adresse', 'first_name' => 'prénom', 'last_name' => 'nom'];
            $fields = collect($changed)->map(fn (string $field) => $labels[$field] ?? $field)->join(', ');
            activity('profil')->causedBy($user)->performedOn($user)->log('a modifié ses informations de profil : '.$fields);
        }

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        abort(403, __('Account deletion is reserved for the administrator.'));
    }
}
