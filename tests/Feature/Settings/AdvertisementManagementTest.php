<?php

namespace Tests\Feature\Settings;

use App\Models\Cms\Advertisement;
use App\Models\Organization;
use App\Models\User;
use App\Models\UserOrganization;
use App\Services\Advertisement\AdvertisementService;
use App\Support\AdvertisementCatalog;
use App\Support\OrganizationRoles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdvertisementManagementTest extends TestCase
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
            'email' => 'ads-admin@example.test',
            'status' => 'active',
        ]);

        UserOrganization::query()->create([
            'user_id' => $this->admin->id,
            'organization_id' => $this->organization->id,
            'role' => OrganizationRoles::ADMIN,
            'status' => 'active',
        ]);
    }

    public function test_admin_can_view_advertisements_index(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.advertisements.index'))
            ->assertOk()
            ->assertSee('Advertisement management')
            ->assertSee('Visual placement map')
            ->assertSee('Insert ad every N questions');
    }

    public function test_admin_can_create_custom_html_advertisement(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.advertisements.store'), [
                'name' => 'Footer HTML ad',
                'type' => AdvertisementCatalog::TYPE_CUSTOM_HTML,
                'placement' => 'footer',
                'code' => '<div class="test-ad">Sponsored</div>',
                'status' => 'active',
                'sort_order' => 1,
            ])
            ->assertRedirect(route('admin.advertisements.index'));

        $this->assertDatabaseHas('advertisements', [
            'organization_id' => $this->organization->id,
            'name' => 'Footer HTML ad',
            'placement' => 'footer',
            'type' => AdvertisementCatalog::TYPE_CUSTOM_HTML,
            'status' => 'active',
        ]);
    }

    public function test_admin_can_update_question_list_every_n(): void
    {
        $this->actingAs($this->admin)
            ->putJson(route('admin.advertisements.settings'), [
                'question_list_every_n' => 3,
            ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('question_list_every_n', 3);

        $this->assertSame(3, app(AdvertisementService::class)->questionListEveryN($this->organization->id));
    }

    public function test_frontend_renders_active_ad_slot(): void
    {
        Advertisement::query()->create([
            'organization_id' => $this->organization->id,
            'name' => 'Blog list promo',
            'type' => AdvertisementCatalog::TYPE_CUSTOM_HTML,
            'placement' => 'blog_list',
            'code' => '<div class="phase5-ad-marker">Phase5 Ad Live</div>',
            'status' => 'active',
            'sort_order' => 1,
        ]);

        $this->get(route('frontend.blogs.index'))
            ->assertOk()
            ->assertSee('Phase5 Ad Live', false)
            ->assertSee('data-ad-placement="blog_list"', false);
    }

    public function test_inactive_ads_are_not_rendered(): void
    {
        Advertisement::query()->create([
            'organization_id' => $this->organization->id,
            'name' => 'Hidden footer',
            'type' => AdvertisementCatalog::TYPE_CUSTOM_HTML,
            'placement' => 'footer',
            'code' => '<div class="should-not-show">Hidden Ad</div>',
            'status' => 'inactive',
            'sort_order' => 1,
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertDontSee('Hidden Ad', false);
    }

    public function test_seed_defaults_creates_settings_and_sample_ads(): void
    {
        app(AdvertisementService::class)->seedDefaults($this->organization->id);

        $this->assertSame(2, app(AdvertisementService::class)->questionListEveryN($this->organization->id));
        $this->assertGreaterThanOrEqual(1, Advertisement::query()->forOrg($this->organization->id)->count());
    }
}
