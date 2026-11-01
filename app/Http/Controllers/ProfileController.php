<?php

namespace App\Http\Controllers;

use App\Http\Requests\DeleteAccountRequest;
use App\Http\Requests\ProfileUpdateRequest;
use App\Services\ImageUploadService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function __construct(private readonly ImageUploadService $images) {}

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
     * Update the user's profile information: name, email, photo, bio, subject (FR-4).
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();

        // "photo" is a file and "remove_photo" is a checkbox, never columns,
        // so they are handled separately below.
        $user->fill($request->safe()->except(['photo', 'remove_photo']));

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        if ($request->hasFile('photo')) {
            // Remember the old file so we can delete it after storing the new one.
            $oldPhoto = $user->photo;

            // Decoded, resized and re-encoded to a random WebP file (SRS 5.1/5.2).
            $user->photo = $this->images->storeProfilePhoto($request->file('photo'));

            // FR-4: the replaced photo is removed from the disk.
            if ($oldPhoto) {
                Storage::disk('public')->delete($oldPhoto);
            }
        } elseif ($request->boolean('remove_photo') && $user->photo) {
            // FR-4: "remove current photo" restores the default avatar.
            Storage::disk('public')->delete($user->photo);
            $user->photo = null;
        }

        $user->save();

        return Redirect::route('profile.edit')->with('success', __('Your profile has been updated.'));
    }

    /**
     * Delete the user's account.
     */
    public function destroy(DeleteAccountRequest $request): RedirectResponse
    {
        // DeleteAccountRequest validates the password in the "userDeletion" bag.
        $user = $request->user();

        Auth::logout();

        // The User model deletes the photo, the post images and the reports
        // about this user's content in its deleting hook.
        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
