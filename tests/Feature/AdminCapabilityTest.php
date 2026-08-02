<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\User;
use App\Models\UserOrganization;
use App\Support\OrganizationRoles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminCapabilityTest extends TestCase
{
    use RefreshDatabase;

    protected Organization $organization;

    protected function setUp(): void
    {
        parent::setUp();

        $this->organization = Organization::query()->create([
            'name' => 'Capability Org',
            'slug' => 'capability-org',
            'status' => 'active',
        ]);
    }

    protected function userWithRole(string $role): User
    {
        $user = User::factory()->create(['status' => 'active']);

        UserOrganization::query()->create([
            'user_id' => $user->id,
            'organization_id' => $this->organization->id,
            'role' => $role,
            'status' => 'active',
        ]);

        return $user;
    }

    public function test_org_admin_and_admin_share_full_panel_access(): void
    {
        foreach ([OrganizationRoles::ORG_ADMIN, OrganizationRoles::ADMIN] as $role) {
            $user = $this->userWithRole($role);

            $this->actingAs($user)
                ->get(route('admin.settings.security'))
                ->assertOk();

            $this->actingAs($user)
                ->get(route('admin.settings.organization'))
                ->assertOk();

            $this->actingAs($user)
                ->get(route('admin.advertisements.index'))
                ->assertOk();
        }
    }

    public function test_candidate_cannot_access_admin_dashboard(): void
    {
        $user = $this->userWithRole(OrganizationRoles::CANDIDATE);

        $this->actingAs($user)
            ->get(route('admin.dashboard'))
            ->assertForbidden();
    }
}
