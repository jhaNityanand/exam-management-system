<?php

use App\Models\Organization;
use App\Models\User;
use App\Models\UserOrganization;
use App\Support\OrganizationRoles;

beforeEach(function () {
    $this->organization = Organization::query()->create([
        'name' => 'Examtube',
        'slug' => 'permission-org-'.uniqid(),
        'status' => 'active',
    ]);
});

test('candidate sees friendly permission page on admin urls without redirect', function () {
    $candidate = User::factory()->create();
    UserOrganization::query()->create([
        'user_id' => $candidate->id,
        'organization_id' => $this->organization->id,
        'role' => OrganizationRoles::CANDIDATE,
        'status' => 'active',
    ]);

    $response = $this->actingAs($candidate)->get('/admin');

    $response->assertForbidden()
        ->assertSee('You do not have permission to access this page.', false)
        ->assertSee('Access denied', false)
        ->assertSee('Home', false)
        ->assertSee('My account', false)
        ->assertDontSee('Symfony', false)
        ->assertDontSee('Whoops', false);

    // URL stays on the admin path (no redirect away).
    expect($response->headers->get('Location'))->toBeNull();
});

test('json admin requests receive a json 403 payload', function () {
    $candidate = User::factory()->create();
    UserOrganization::query()->create([
        'user_id' => $candidate->id,
        'organization_id' => $this->organization->id,
        'role' => OrganizationRoles::CANDIDATE,
        'status' => 'active',
    ]);

    $this->actingAs($candidate)
        ->getJson('/admin')
        ->assertForbidden()
        ->assertJsonPath('message', 'You do not have permission to access this page.');
});

test('org admin can still open the admin panel', function () {
    $admin = User::factory()->create();
    UserOrganization::query()->create([
        'user_id' => $admin->id,
        'organization_id' => $this->organization->id,
        'role' => OrganizationRoles::ORG_ADMIN,
        'status' => 'active',
    ]);

    $this->actingAs($admin)
        ->get(route('admin.dashboard'))
        ->assertOk();
});

test('abort 403 uses the friendly permission page', function () {
    \Illuminate\Support\Facades\Route::get('/__permission-probe', function () {
        abort(403, 'You do not have permission to access this page.');
    });

    $this->get('/__permission-probe')
        ->assertForbidden()
        ->assertSee('You do not have permission to access this page.', false)
        ->assertSee('Home', false)
        ->assertSee('Login', false);
});
