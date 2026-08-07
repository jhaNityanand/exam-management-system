<?php

use App\Models\LlmAccount;
use App\Models\Organization;
use App\Models\User;
use App\Models\UserOrganization;
use App\Support\OrganizationRoles;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create(['status' => 'active']);
    $this->organization = Organization::create([
        'name' => 'Test Org',
        'slug' => 'test-org-' . $this->user->id,
        'status' => 'active',
    ]);
    UserOrganization::create([
        'user_id' => $this->user->id,
        'organization_id' => $this->organization->id,
        'role' => OrganizationRoles::ADMIN,
        'status' => 'active',
    ]);
});

test('admin can view llm management page', function () {
    $response = $this->actingAs($this->user)
        ->get(route('admin.settings.llm.index'));

    $response->assertStatus(200);
    $response->assertViewIs('backend.settings.llm.index');
    $response->assertSee('LLM Accounts');
});

test('admin can create a new llm account', function () {
    $response = $this->actingAs($this->user)
        ->postJson(route('admin.settings.llm.accounts.store'), [
            'provider' => 'mistral',
            'account_name' => 'Test Mistral Account',
            'api_key' => 'x1mRLZU52wWzCQDez4UjzkQ8hgynwVLl',
            'model' => 'mistral-small-latest',
            'priority' => 1,
            'is_active' => 1,
        ]);

    $response->assertStatus(200);
    $response->assertJson(['success' => true]);

    $this->assertDatabaseHas('llm_accounts', [
        'account_name' => 'Test Mistral Account',
        'provider' => 'mistral',
        'model' => 'mistral-small-latest',
    ]);
});

test('admin can update an existing llm account', function () {
    $account = LlmAccount::create([
        'provider' => 'groq',
        'account_name' => 'Old Groq Account',
        'api_key' => 'gsk_test12345',
        'model' => 'llama-3.3-70b-versatile',
        'priority' => 2,
        'is_active' => true,
    ]);

    $response = $this->actingAs($this->user)
        ->putJson(route('admin.settings.llm.accounts.update', $account->id), [
            'provider' => 'groq',
            'account_name' => 'Updated Groq Account',
            'model' => 'llama-3.3-70b-versatile',
            'priority' => 1,
            'is_active' => 1,
        ]);

    $response->assertStatus(200);
    $response->assertJson(['success' => true]);

    $this->assertDatabaseHas('llm_accounts', [
        'id' => $account->id,
        'account_name' => 'Updated Groq Account',
        'priority' => 1,
    ]);
});

test('admin can reset cooldown for an account', function () {
    $account = LlmAccount::create([
        'provider' => 'gemini',
        'account_name' => 'Cooldown Gemini Account',
        'api_key' => 'gemini_key_123',
        'model' => 'gemini-2.0-flash',
        'cooldown_until' => now()->addHours(12),
        'error_count' => 3,
        'is_active' => true,
    ]);

    $response = $this->actingAs($this->user)
        ->patchJson(route('admin.settings.llm.accounts.reset-cooldown', $account->id));

    $response->assertStatus(200);
    $response->assertJson(['success' => true]);

    $account->refresh();
    expect($account->cooldown_until)->toBeNull();
    expect($account->error_count)->toBe(0);
});

test('admin can delete an llm account', function () {
    $account = LlmAccount::create([
        'provider' => 'openrouter',
        'account_name' => 'To Delete OpenRouter Account',
        'api_key' => 'sk-or-v1-test123',
        'model' => 'meta-llama/llama-3.2-1b-instruct:free',
        'priority' => 3,
        'is_active' => true,
    ]);

    $response = $this->actingAs($this->user)
        ->deleteJson(route('admin.settings.llm.accounts.destroy', $account->id));

    $response->assertStatus(200);
    $response->assertJson(['success' => true]);

    $this->assertDatabaseMissing('llm_accounts', [
        'id' => $account->id,
    ]);
});
