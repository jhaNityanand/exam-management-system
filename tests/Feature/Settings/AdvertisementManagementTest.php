<?php

namespace Tests\Feature\Settings;

use App\Models\Cms\AdPlacement;
use App\Models\Cms\Advertisement;
use App\Models\Cms\GoogleAdvertisement;
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
            ->assertSee('Ads Placement')
            ->assertSee('Custom Code')
            ->assertSee('Help &amp; documentation', false);
    }

    public function test_admin_can_create_html_advertisement(): void
    {
        $this->actingAs($this->admin)
            ->postJson(route('admin.advertisements.store'), [
                'name' => 'HTML promo',
                'title' => 'Sponsored card',
                'type' => AdvertisementCatalog::TYPE_HTML,
                'html_code' => '<div class="test-ad">Sponsored</div>',
                'css_code' => '.test-ad{padding:1rem}',
                'status' => 'active',
            ])
            ->assertCreated()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('advertisements', [
            'organization_id' => $this->organization->id,
            'name' => 'HTML promo',
            'type' => AdvertisementCatalog::TYPE_HTML,
            'status' => 'active',
        ]);
    }

    public function test_admin_can_create_google_ad_and_placement(): void
    {
        $this->actingAs($this->admin)
            ->postJson(route('admin.advertisements.google.store'), [
                'name' => 'Sidebar AdSense',
                'code' => '<ins class="adsbygoogle"></ins>',
                'ad_client' => 'ca-pub-123',
                'ad_slot' => '456',
                'status' => 'active',
            ])
            ->assertCreated();

        $google = GoogleAdvertisement::query()->forOrg($this->organization->id)->first();
        $this->assertNotNull($google);

        $this->actingAs($this->admin)
            ->postJson(route('admin.advertisements.placements.store'), [
                'page_key' => 'home',
                'position_key' => 'after_hero',
                'source_type' => AdvertisementCatalog::SOURCE_GOOGLE,
                'google_advertisement_id' => $google->id,
                'is_enabled' => true,
            ])
            ->assertCreated()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('ad_placements', [
            'organization_id' => $this->organization->id,
            'page_key' => 'home',
            'position_key' => 'after_hero',
            'source_type' => AdvertisementCatalog::SOURCE_GOOGLE,
            'google_advertisement_id' => $google->id,
            'is_enabled' => 1,
        ]);
    }

    public function test_admin_can_save_custom_code(): void
    {
        $this->actingAs($this->admin)
            ->putJson(route('admin.advertisements.custom-code'), [
                'header_code' => '<script>/* header */</script>',
                'footer_code' => '<script>/* footer */</script>',
            ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('custom_code.header_code', '<script>/* header */</script>');

        $code = app(AdvertisementService::class)->customCode($this->organization->id);
        $this->assertSame('<script>/* header */</script>', $code['header_code']);
        $this->assertSame('<script>/* footer */</script>', $code['footer_code']);
    }

    public function test_single_slot_rejects_second_placement(): void
    {
        $ad = Advertisement::query()->create([
            'organization_id' => $this->organization->id,
            'name' => 'Title banner',
            'title' => 'Title banner',
            'type' => AdvertisementCatalog::TYPE_HTML,
            'html_code' => '<div>Ad</div>',
            'status' => 'active',
            'sort_order' => 1,
        ]);

        AdPlacement::query()->create([
            'organization_id' => $this->organization->id,
            'page_key' => 'exam_detail',
            'position_key' => 'above_title',
            'source_type' => AdvertisementCatalog::SOURCE_CUSTOM,
            'advertisement_id' => $ad->id,
            'sort_order' => 1,
            'is_enabled' => true,
        ]);

        $this->actingAs($this->admin)
            ->postJson(route('admin.advertisements.placements.store'), [
                'page_key' => 'exam_detail',
                'position_key' => 'above_title',
                'source_type' => AdvertisementCatalog::SOURCE_CUSTOM,
                'advertisement_id' => $ad->id,
            ])
            ->assertStatus(422);
    }

    public function test_seed_defaults_creates_google_ads_and_placements(): void
    {
        app(AdvertisementService::class)->seedDefaults($this->organization->id, true);

        $this->assertSame(0, Advertisement::query()->forOrg($this->organization->id)->count());
        $this->assertSame(3, GoogleAdvertisement::query()->forOrg($this->organization->id)->count());
        $this->assertGreaterThan(0, AdPlacement::query()->forOrg($this->organization->id)->count());

        foreach (['exam_attempt', 'exam_result', 'exam_rules', 'exam_prepare', 'faqs', 'account', 'error_404'] as $pageKey) {
            $this->assertTrue(
                AdPlacement::query()->forOrg($this->organization->id)->where('page_key', $pageKey)->exists(),
                "Expected seeded placements for {$pageKey}"
            );
        }

        $this->assertTrue(
            AdPlacement::query()
                ->forOrg($this->organization->id)
                ->where('page_key', 'exam_attempt')
                ->whereIn('position_key', ['left_sidebar', 'right_sidebar', 'below_content'])
                ->exists()
        );

        $code = app(AdvertisementService::class)->customCode($this->organization->id);
        $this->assertStringContainsString('G-35TPDL6YPR', $code['header_code']);
        $this->assertStringContainsString('ca-pub-3495821309562824', $code['header_code']);
        $this->assertStringContainsString('a970034f682bb9bbc89a7eb02ee49cfe', $code['header_code']);
        $this->assertSame('', $code['footer_code']);

        $vertical = GoogleAdvertisement::query()->forOrg($this->organization->id)->where('ad_slot', '9013663436')->first();
        $this->assertNotNull($vertical);
        $this->assertStringContainsString('data-ad-slot="9013663436"', $vertical->code);
        $this->assertStringContainsString('ca-pub-3495821309562824', $vertical->code);
    }

    public function test_frontend_ad_slot_renders_enabled_google_placement(): void
    {
        app(AdvertisementService::class)->seedDefaults($this->organization->id, true);

        $html = app(AdvertisementService::class)->renderSlot('home', 'left_sidebar', $this->organization->id);
        $this->assertStringContainsString('et-ad', $html);
        $this->assertStringContainsString('adsbygoogle', $html);
        $this->assertStringContainsString('9013663436', $html);
        $this->assertStringContainsString('ca-pub-3495821309562824', $html);
    }
}
