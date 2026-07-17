<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Models\Profile;
use App\Services\ProfileAvatarService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function __construct(
        private ProfileAvatarService $avatarService,
    ) {}

    public function edit(Request $request): View
    {
        $user = $request->user();
        $user->loadMissing('profile');

        return view('profile.edit', [
            'user' => $user,
            'avatarUrl' => $this->avatarService->url($user->profile?->avatar),
        ]);
    }

    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();
        $user->fill($request->safe()->only(['name', 'email']));

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        $profileData = $request->safe()->only([
            'phone', 'bio', 'address_line1', 'address_line2', 'city', 'state_region', 'postal_code', 'country',
        ]);

        if ($request->boolean('remove_avatar')) {
            $existing = $user->profile?->avatar;
            $this->avatarService->delete($existing);
            $profileData['avatar'] = null;
        } elseif ($request->filled('cropped_avatar')) {
            $existing = $user->profile?->avatar;
            $profileData['avatar'] = $this->avatarService->storeFromBase64(
                $request->string('cropped_avatar')->toString(),
                $user->id,
            );
            $this->avatarService->delete($existing);
        }

        if ($request->has('social_links')) {
            $profileData['social_links'] = collect($request->input('social_links', []))
                ->map(fn ($value) => filled($value) ? $value : null)
                ->filter()
                ->all();
        }

        Profile::updateOrCreate(
            ['id' => $user->id],
            array_merge(['id' => $user->id, 'status' => 'active'], $profileData)
        );

        return Redirect::route('admin.profile.edit')->with('status', 'profile-updated');
    }

    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
