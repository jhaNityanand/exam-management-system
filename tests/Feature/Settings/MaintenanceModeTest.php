<?php

namespace Tests\Feature\Settings;

use App\Models\Cms\SiteSetting;
use App\Models\Organization;
use App\Models\User;
use App\Models\UserOrganization;
use App\Services\Settings\MaintenanceModeService;
use App\Support\OrganizationRoles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MaintenanceModeTest extends TestCase
{
    use RefreshDatabase;

    protected Organization $organization;

    protected function setUp(): void
    {
        parent::setUp();

        $this->organization = Organization::query()->create([
            'name' => 'Examtube',
            'slug' => 'examtube',
            'status' => 'active',
        ]);

        app(MaintenanceModeService::class)->seedDefaults($this->organization->id, [
            'contact_email' => 'hello@examtube.in',
        ]);
    }

    public function test_public_site_is_accessible_when_maintenance_is_disabled(): void
    {
        $this->get('/')->assertOk();
    }

    public function test_public_site_shows_maintenance_page_when_enabled(): void
    {
        app(MaintenanceModeService::class)->update([
            'enabled' => true,
            'title' => 'Down for maintenance',
            'message' => 'Please check back shortly.',
        ], $this->organization->id);

        $this->get('/')
            ->assertStatus(503)
            ->assertSee('Down for maintenance')
            ->assertSee('Please check back shortly.');
    }

    public function test_admin_can_access_panel_during_maintenance(): void
    {
        app(MaintenanceModeService::class)->update([
            'enabled' => true,
            'title' => 'Down for maintenance',
            'message' => 'Please check back shortly.',
        ], $this->organization->id);

        $admin = $this->makeUser(OrganizationRoles::ADMIN);

        $this->actingAs($admin)
            ->get(route('admin.settings.maintenance'))
            ->assertOk()
            ->assertSee('Maintenance Mode');
    }

    public function test_login_remains_available_during_maintenance(): void
    {
        app(MaintenanceModeService::class)->update([
            'enabled' => true,
            'title' => 'Down for maintenance',
            'message' => 'Please check back shortly.',
        ], $this->organization->id);

        $this->get(route('login'))->assertOk();
    }

    public function test_admin_can_update_maintenance_settings_via_ajax(): void
    {
        $admin = $this->makeUser(OrganizationRoles::ORG_ADMIN);

        $response = $this->actingAs($admin)
            ->putJson(route('admin.settings.maintenance.update'), [
                'enabled' => true,
                'title' => 'Scheduled upgrade',
                'message' => "We are currently performing scheduled maintenance to improve your experience.\nPlease check back shortly.",
                'contact_email' => 'hello@examtube.in',
                'contact_phone' => '+91 98765 43210',
                'social_facebook' => 'https://facebook.com/examtube',
            ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('settings.enabled', true);

        $this->assertTrue(
            (bool) SiteSetting::query()
                ->where('organization_id', $this->organization->id)
                ->where('group', 'maintenance')
                ->where('key', 'enabled')
                ->value('value')
        );
    }

    protected function makeUser(string $role): User
    {
        $user = User::factory()->create([
            'email' => $role.'@example.test',
        ]);

        UserOrganization::query()->create([
            'user_id' => $user->id,
            'organization_id' => $this->organization->id,
            'role' => $role,
            'status' => 'active',
        ]);

        return $user;
    }
}
