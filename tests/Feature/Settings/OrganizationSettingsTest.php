<?php

namespace Tests\Feature\Settings;

use App\Models\Cms\HeroBanner;
use App\Models\Cms\SiteSetting;
use App\Models\Cms\SocialLink;
use App\Models\Organization;
use App\Models\User;
use App\Models\UserOrganization;
use App\Support\OrganizationRoles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrganizationSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected Organization $organization;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->organization = Organization::query()->create([
            'name' => 'Examtube',
            'slug' => 'examtube',
            'status' => 'active',
        ]);

        $this->admin = User::factory()->create([
            'email' => 'org-settings@example.test',
            'status' => 'active',
        ]);

        UserOrganization::query()->create([
            'user_id' => $this->admin->id,
            'organization_id' => $this->organization->id,
            'role' => OrganizationRoles::ORG_ADMIN,
            'status' => 'active',
        ]);
    }

    public function test_admin_can_view_organization_settings(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.settings.organization'))
            ->assertOk()
            ->assertSee('Organization Settings')
            ->assertSee('Hero banners');
    }

    public function test_admin_can_update_organization_settings(): void
    {
        $response = $this->actingAs($this->admin)
            ->putJson(route('admin.settings.organization.update'), [
                'site_name' => 'Examtube Academy',
                'tagline' => 'Practice smarter',
                'description' => 'Demo description',
                'logo_text' => 'Examtube',
                'email' => 'hello@examtube.in',
                'phone' => '+91 98765 43210',
                'whatsapp' => '+91 98765 43210',
                'address' => 'Bengaluru',
                'hours' => 'Mon–Sat',
                'maps_url' => 'https://maps.google.com/?q=Bengaluru',
                'footer_about' => 'About Examtube',
                'footer_copyright' => '© {year} Examtube.in',
                'cta_title' => 'Ready?',
                'cta_subtitle' => 'Start practicing',
                'cta_primary_label' => 'Browse Exams',
                'cta_primary_url' => '/exams',
                'cta_secondary_label' => 'Register',
                'cta_secondary_url' => '/register',
                'newsletter_title' => 'Stay ready',
                'newsletter_subtitle' => 'Weekly tips',
                'newsletter_cta' => 'Subscribe',
                'seo_default_title' => 'Examtube SEO',
                'seo_default_description' => 'SEO description',
                'seo_default_keywords' => 'exams, mocks',
                'social' => [
                    'facebook' => ['url' => 'https://facebook.com/examtube', 'is_visible' => true],
                    'instagram' => ['url' => 'https://instagram.com/examtube', 'is_visible' => true],
                    'linkedin' => ['url' => '', 'is_visible' => false],
                    'x' => ['url' => 'https://x.com/examtube', 'is_visible' => true],
                    'youtube' => ['url' => 'https://youtube.com/@examtube', 'is_visible' => true],
                    'telegram' => ['url' => '', 'is_visible' => false],
                ],
            ]);

        $response->assertOk()->assertJsonPath('success', true);

        $this->assertSame(
            'Examtube Academy',
            SiteSetting::query()
                ->where('organization_id', $this->organization->id)
                ->where('group', 'brand')
                ->where('key', 'site_name')
                ->value('value')
        );

        $this->assertSame(
            '+91 98765 43210',
            SiteSetting::query()
                ->where('organization_id', $this->organization->id)
                ->where('group', 'contact')
                ->where('key', 'whatsapp')
                ->value('value')
        );

        $this->assertTrue(
            SocialLink::query()
                ->where('organization_id', $this->organization->id)
                ->where('platform', 'facebook')
                ->where('is_visible', true)
                ->exists()
        );

        $this->assertSame('Examtube Academy', $this->organization->fresh()->name);
    }

    public function test_admin_can_manage_hero_banners(): void
    {
        $create = $this->actingAs($this->admin)
            ->postJson(route('admin.settings.organization.heroes.store'), [
                'title' => 'Master every exam',
                'subtitle' => 'Mock tests',
                'description' => 'Practice with confidence',
                'primary_cta_label' => 'Explore',
                'primary_cta_url' => '/exams',
                'status' => 'active',
                'show_search' => true,
                'sort_order' => 1,
            ]);

        $create->assertOk()->assertJsonPath('success', true);
        $heroId = (int) $create->json('hero.id');
        $this->assertDatabaseHas('hero_banners', [
            'id' => $heroId,
            'title' => 'Master every exam',
            'organization_id' => $this->organization->id,
        ]);

        $this->actingAs($this->admin)
            ->putJson(route('admin.settings.organization.heroes.update', $heroId), [
                'title' => 'Updated hero',
                'status' => 'active',
                'show_search' => false,
                'sort_order' => 2,
            ])
            ->assertOk()
            ->assertJsonPath('hero.title', 'Updated hero');

        $this->actingAs($this->admin)
            ->deleteJson(route('admin.settings.organization.heroes.destroy', $heroId))
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertSoftDeleted('hero_banners', ['id' => $heroId]);
    }
}
