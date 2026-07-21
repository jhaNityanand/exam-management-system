<?php

use App\Models\Organization;
use App\Models\User;
use App\Models\UserOrganization;
use Illuminate\Support\Facades\Storage;

test('profile page is displayed', function () {
    $user = User::factory()->create();
    $organization = Organization::create([
        'name' => 'Profile Page Organization',
        'slug' => 'profile-page-org-'.$user->id,
        'status' => 'active',
        'user_id' => $user->id,
    ]);
    UserOrganization::create([
        'user_id' => $user->id,
        'organization_id' => $organization->id,
        'role' => 'admin',
        'status' => 'active',
    ]);

    $response = $this
        ->actingAs($user)
        ->get('/admin/profile');

    $response->assertOk()
        ->assertSee('name="username"', false)
        ->assertSee('name="date_of_birth"', false)
        ->assertSee('name="gender"', false);
});

test('profile information can be updated', function () {
    $user = User::factory()->create();
    $organization = Organization::create([
        'name' => 'Profile Update Organization',
        'slug' => 'profile-update-org-'.$user->id,
        'status' => 'active',
        'user_id' => $user->id,
    ]);
    UserOrganization::create([
        'user_id' => $user->id,
        'organization_id' => $organization->id,
        'role' => 'admin',
        'status' => 'active',
    ]);
    $newEmail = 'profile-test-'.$user->id.'@example.com';

    $response = $this
        ->actingAs($user)
        ->patch('/admin/profile', [
            'name' => 'Test User',
            'username' => 'test_user_'.$user->id,
            'email' => $newEmail,
            'phone' => '+91 98765 43210',
            'date_of_birth' => '1995-05-15',
            'gender' => 'prefer_not_to_say',
            'bio' => 'Admin profile bio',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect('/admin/profile');

    $user->refresh()->load('profile');

    $this->assertSame('Test User', $user->name);
    $this->assertSame('test_user_'.$user->id, $user->username);
    $this->assertSame($newEmail, $user->email);
    $this->assertNull($user->email_verified_at);
    expect($user->profile?->phone)->toBe('+91 98765 43210');
    expect($user->profile?->gender)->toBe('prefer_not_to_say');
    expect(optional($user->profile?->date_of_birth)->format('Y-m-d'))->toBe('1995-05-15');
    expect($user->profile?->bio)->toBe('Admin profile bio');
});

test('profile rejects future date of birth', function () {
    $user = User::factory()->create();
    $organization = Organization::create([
        'name' => 'Profile Dob Organization',
        'slug' => 'profile-dob-org-'.$user->id,
        'status' => 'active',
        'user_id' => $user->id,
    ]);
    UserOrganization::create([
        'user_id' => $user->id,
        'organization_id' => $organization->id,
        'role' => 'admin',
        'status' => 'active',
    ]);

    $response = $this
        ->actingAs($user)
        ->from('/admin/profile')
        ->patch('/admin/profile', [
            'name' => $user->name,
            'email' => $user->email,
            'date_of_birth' => now()->addDay()->format('Y-m-d'),
        ]);

    $response
        ->assertSessionHasErrors('date_of_birth')
        ->assertRedirect('/admin/profile');
});

test('email verification status is unchanged when the email address is unchanged', function () {
    $user = User::factory()->create();
    $organization = Organization::create([
        'name' => 'Profile Email Organization',
        'slug' => 'profile-email-org-'.$user->id,
        'status' => 'active',
        'user_id' => $user->id,
    ]);
    UserOrganization::create([
        'user_id' => $user->id,
        'organization_id' => $organization->id,
        'role' => 'admin',
        'status' => 'active',
    ]);

    $response = $this
        ->actingAs($user)
        ->patch('/admin/profile', [
            'name' => 'Test User',
            'email' => $user->email,
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect('/admin/profile');

    $this->assertNotNull($user->refresh()->email_verified_at);
});

test('profile avatar can be uploaded from cropped image data', function () {
    Storage::fake('public');
    config()->set('gallery.disk', 'public');

    $user = User::factory()->create();
    $organization = Organization::create([
        'name' => 'Profile Test Organization',
        'slug' => 'profile-test-org-'.$user->id,
        'status' => 'active',
        'user_id' => $user->id,
    ]);
    UserOrganization::create([
        'user_id' => $user->id,
        'organization_id' => $organization->id,
        'role' => 'admin',
        'status' => 'active',
    ]);
    $jpeg = 'data:image/jpeg;base64,/9j/4AAQSkZJRgABAQAAAQABAAD/2wCEAAEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQH/wAALCAABAAEBAREA/8QAFAABAAAAAAAAAAAAAAAAAAAAA//EABQQAQAAAAAAAAAAAAAAAAAAAAD/2gAIAQEAAQUCf//EABQRAQAAAAAAAAAAAAAAAAAAAAD/2gAIAQMBAT8Bf//EABQRAQAAAAAAAAAAAAAAAAAAAAD/2gAIAQIBAT8Bf//EABQQAQAAAAAAAAAAAAAAAAAAAAD/2gAIAQEABj8Cf//EABQQAQAAAAAAAAAAAAAAAAAAAAD/2gAIAQEAAT8hf//aAAwDAQACAAMAAAAQn//EABQRAQAAAAAAAAAAAAAAAAAAAAD/2gAIAQMBAT8Qf//EABQRAQAAAAAAAAAAAAAAAAAAAAD/2gAIAQIBAT8Qf//EABQQAQAAAAAAAAAAAAAAAAAAAAD/2gAIAQEAAT8Qf//Z';

    $response = $this
        ->actingAs($user)
        ->patch('/admin/profile', [
            'name' => $user->name,
            'email' => $user->email,
            'cropped_avatar' => $jpeg,
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect('/admin/profile');

    $user->refresh()->load('profile');

    expect($user->profile)->not->toBeNull();
    expect($user->profile->avatar)->not->toBeNull();
    Storage::disk('public')->assertExists($user->profile->avatar);
});

test('user can delete their account', function () {
    $user = User::factory()->create();
    $organization = Organization::create([
        'name' => 'Profile Delete Organization',
        'slug' => 'profile-delete-org-'.$user->id,
        'status' => 'active',
        'user_id' => $user->id,
    ]);
    UserOrganization::create([
        'user_id' => $user->id,
        'organization_id' => $organization->id,
        'role' => 'admin',
        'status' => 'active',
    ]);

    $response = $this
        ->actingAs($user)
        ->delete('/admin/profile', [
            'password' => 'password',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect('/');

    $this->assertGuest();
    $this->assertSoftDeleted($user);
});

test('correct password must be provided to delete account', function () {
    $user = User::factory()->create();
    $organization = Organization::create([
        'name' => 'Profile Delete Fail Organization',
        'slug' => 'profile-delete-fail-org-'.$user->id,
        'status' => 'active',
        'user_id' => $user->id,
    ]);
    UserOrganization::create([
        'user_id' => $user->id,
        'organization_id' => $organization->id,
        'role' => 'admin',
        'status' => 'active',
    ]);

    $response = $this
        ->actingAs($user)
        ->from('/admin/profile')
        ->delete('/admin/profile', [
            'password' => 'wrong-password',
        ]);

    $response
        ->assertSessionHasErrorsIn('userDeletion', 'password')
        ->assertRedirect('/admin/profile');

    $this->assertNotNull($user->fresh());
});
