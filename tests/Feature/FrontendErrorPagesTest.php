<?php

use App\Models\Organization;
use App\Models\User;
use App\Models\UserOrganization;
use App\Support\OrganizationRoles;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpKernel\Exception\HttpException;

beforeEach(function () {
    Organization::query()->create([
        'name' => 'Examtube',
        'slug' => 'error-pages-'.uniqid(),
        'status' => 'active',
    ]);
});

test('unknown frontend routes render the branded 404 page', function () {
    $this->get('/this-page-definitely-does-not-exist-'.uniqid())
        ->assertNotFound()
        ->assertSee('Page not found', false)
        ->assertSee('Home', false)
        ->assertSee('Search', false)
        ->assertDontSee('Whoops', false)
        ->assertDontSee('NotFoundHttpException', false);
});

test('frontend http exceptions render branded pages', function (int $status, string $title) {
    Route::get('/__error-probe-'.$status, function () use ($status) {
        throw new HttpException($status);
    });

    $this->get('/__error-probe-'.$status)
        ->assertStatus($status)
        ->assertSee($title, false)
        ->assertSee('Home', false)
        ->assertDontSee('Whoops', false);
})->with([
    [419, 'Page expired'],
    [429, 'Too many requests'],
    [500, 'Something went wrong'],
    [503, 'Service unavailable'],
]);

test('frontend 403 page includes home and auth actions', function () {
    $candidate = User::factory()->create();
    $org = Organization::query()->first();
    UserOrganization::query()->create([
        'user_id' => $candidate->id,
        'organization_id' => $org->id,
        'role' => OrganizationRoles::CANDIDATE,
        'status' => 'active',
    ]);

    $this->actingAs($candidate)
        ->get('/admin')
        ->assertForbidden()
        ->assertSee('You do not have permission to access this page.', false)
        ->assertSee('Home', false)
        ->assertSee('My account', false);
});
