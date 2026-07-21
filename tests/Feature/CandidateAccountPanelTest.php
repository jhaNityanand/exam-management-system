<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

test('candidate account pages render redesigned shell', function () {
    $user = User::factory()->create([
        'name' => 'Account Tester',
        'email' => 'account.tester.'.uniqid().'@example.test',
        'password' => Hash::make('password'),
    ]);
    $user->ensureCandidateMembership();

    $this->actingAs($user)
        ->get(route('frontend.account.dashboard'))
        ->assertOk()
        ->assertSee('ca-sidebar', false)
        ->assertSee('Dashboard');

    $this->actingAs($user)
        ->get(route('frontend.account.profile'))
        ->assertOk()
        ->assertSee('id="ca-profile"', false)
        ->assertSee('account-profile.js');

    $this->actingAs($user)
        ->getJson(route('frontend.account.profile.data'))
        ->assertOk()
        ->assertJsonPath('ok', true)
        ->assertJsonStructure(['user', 'profile', 'completion', 'stats']);

    $this->actingAs($user)
        ->get(route('frontend.account.settings'))
        ->assertOk()
        ->assertSee('id="ca-settings"', false);

    $this->actingAs($user)
        ->getJson(route('frontend.account.settings.data'))
        ->assertOk()
        ->assertJsonPath('ok', true)
        ->assertJsonStructure(['account', 'notifications', 'privacy', 'sessions', 'security']);

    $this->actingAs($user)
        ->get(route('frontend.account.activity'))
        ->assertOk()
        ->assertSee('Coming soon');

    $this->actingAs($user)
        ->get(route('frontend.account.invoices'))
        ->assertOk()
        ->assertSee('Invoices')
        ->assertSee('Coming soon');

    $this->actingAs($user)
        ->get(route('frontend.account.dashboard'))
        ->assertOk()
        ->assertSee('Result breakdown')
        ->assertSee('Score trend');

    $this->actingAs($user)
        ->postJson(route('frontend.account.profile.update'), [
            'name' => 'Account Tester Updated',
            'email' => $user->email,
            'username' => 'tester_'.substr(uniqid(), -6),
            'bio' => 'Hello from tests',
            'gender' => 'prefer_not_to_say',
        ])
        ->assertOk()
        ->assertJsonPath('ok', true);

    expect($user->fresh()->name)->toBe('Account Tester Updated');
});
