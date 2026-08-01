<?php

use App\Models\Organization;
use App\Models\Profile;
use App\Models\User;
use App\Models\UserOrganization;
use App\Support\OrganizationRoles;

beforeEach(function () {
    Organization::query()->create([
        'name' => 'Examtube',
        'slug' => 'demo-org',
        'status' => 'active',
    ]);
});

test('registration screen can be rendered', function () {
    $this->get('/register')->assertOk();
});

test('new users register as candidates without profile pictures', function () {
    $response = $this->post('/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('frontend.account.dashboard', absolute: false));

    $user = User::query()->where('email', 'test@example.com')->first();
    expect($user)->not->toBeNull()
        ->and($user->slug)->not->toBeEmpty();

    expect(UserOrganization::query()->where('user_id', $user->id)->value('role'))
        ->toBe(OrganizationRoles::CANDIDATE);

    $profile = Profile::query()->find($user->id);
    expect($profile)->not->toBeNull()
        ->and($profile->avatar)->toBeNull()
        ->and($profile->default_organization_id)->not->toBeNull();
});
