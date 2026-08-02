<?php

namespace Tests\Feature\Settings;

use App\Models\Cms\SiteSetting;
use App\Models\Organization;
use App\Models\User;
use App\Models\UserOrganization;
use App\Services\Settings\MaintenanceModeService;
use App\Support\OrganizationRoles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
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
            'social_facebook' => 'https://facebook.com/examtube',
        ]);
    }

    public function test_public_site_is_accessible_when_maintenance_is_disabled(): void
    {
        $this->get('/')->assertOk();
    }

    public function test_public_site_shows_maintenance_page_when_enabled(): void
    {
        $restoreAt = Carbon::now()->addHours(2);

        app(MaintenanceModeService::class)->update([
            'enabled' => true,
            'title' => 'Down for maintenance',
            'message' => '<p>Please check back shortly.</p>',
            'estimated_at' => $restoreAt->format('Y-m-d H:i'),
            'social_facebook' => 'https://facebook.com/examtube',
        ], $this->organization->id);

        $response = $this->get('/');

        $response->assertStatus(503)
            ->assertSee('Down for maintenance')
            ->assertSee('Please check back shortly.', false)
            ->assertSee('Back online in')
            ->assertSee('Expected back')
            ->assertSee('Facebook')
            ->assertSee('data-maintenance-countdown', false)
            ->assertSee('et-header', false)
            ->assertSee('et-footer', false)
            ->assertDontSee('Branding')
            ->assertHeader('Retry-After');
    }

    public function test_admin_previewing_frontend_also_sees_maintenance(): void
    {
        app(MaintenanceModeService::class)->update([
            'enabled' => true,
            'title' => 'Down for maintenance',
            'message' => '<p>Please check back shortly.</p>',
        ], $this->organization->id);

        $admin = $this->makeUser(OrganizationRoles::ADMIN);

        $this->actingAs($admin)
            ->get('/')
            ->assertStatus(503)
            ->assertSee('Down for maintenance')
            ->assertSee('et-header', false);
    }

    public function test_admin_can_access_panel_during_maintenance(): void
    {
        app(MaintenanceModeService::class)->update([
            'enabled' => true,
            'title' => 'Down for maintenance',
            'message' => '<p>Please check back shortly.</p>',
        ], $this->organization->id);

        $admin = $this->makeUser(OrganizationRoles::ADMIN);

        $this->actingAs($admin)
            ->get(route('admin.settings.maintenance'))
            ->assertOk()
            ->assertSee('Maintenance Mode')
            ->assertSee('Page content')
            ->assertSee('Restore date')
            ->assertDontSee('Branding')
            ->assertDontSee('Contact information');
    }

    public function test_login_remains_available_during_maintenance(): void
    {
        app(MaintenanceModeService::class)->update([
            'enabled' => true,
            'title' => 'Down for maintenance',
            'message' => '<p>Please check back shortly.</p>',
        ], $this->organization->id);

        $this->get(route('login'))->assertOk();
    }

    public function test_register_is_blocked_during_maintenance(): void
    {
        app(MaintenanceModeService::class)->update([
            'enabled' => true,
            'title' => 'Down for maintenance',
            'message' => '<p>Please check back shortly.</p>',
        ], $this->organization->id);

        $this->get(route('register'))
            ->assertStatus(503)
            ->assertSee('Down for maintenance');
    }

    public function test_admin_can_update_maintenance_settings_via_ajax(): void
    {
        $admin = $this->makeUser(OrganizationRoles::ORG_ADMIN);
        $restoreAt = Carbon::now()->addDay()->format('Y-m-d H:i');

        $response = $this->actingAs($admin)
            ->putJson(route('admin.settings.maintenance.update'), [
                'enabled' => true,
                'title' => 'Scheduled upgrade',
                'message' => '<p>We are currently performing scheduled maintenance to improve your experience.</p><p>Please check back shortly.</p>',
                'estimated_at' => $restoreAt,
                'social_facebook' => 'https://facebook.com/examtube',
            ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('settings.enabled', true)
            ->assertJsonPath('settings.estimated_at', $restoreAt);

        $this->assertNotEmpty($response->json('settings.estimated_at_iso'));

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
