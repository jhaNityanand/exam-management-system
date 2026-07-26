<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\Profile;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    public function create(Request $request): View
    {
        abort_unless(
            app(\App\Services\Settings\IntegrationsSettingsService::class)->isRegistrationEnabled(),
            404
        );

        $redirect = $request->query('redirect');
        if (is_string($redirect) && str_starts_with($redirect, url('/'))) {
            $request->session()->put('url.intended', $redirect);
        }

        return view('auth.register');
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless(
            app(\App\Services\Settings\IntegrationsSettingsService::class)->isRegistrationEnabled(),
            404
        );

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ];
        if (app(\App\Services\Settings\SecuritySettingsService::class)->requiresRecaptcha('register')) {
            $rules['g-recaptcha-response'] = [new \App\Rules\RecaptchaToken('register')];
        }
        $request->validate($rules);

        $user = DB::transaction(function () use ($request) {
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'status' => 'active',
            ]);

            Profile::create([
                'id' => $user->id,
                'status' => 'active',
            ]);

            $orgId = Organization::query()->where('slug', 'demo-org')->value('id')
                ?: Organization::query()->value('id');
            if ($orgId) {
                $user->ensureCandidateMembership((int) $orgId);
            }

            return $user;
        });

        event(new Registered($user));

        Auth::login($user);

        $default = route('frontend.account.dashboard', absolute: false);

        return redirect()->intended($default);
    }
}
