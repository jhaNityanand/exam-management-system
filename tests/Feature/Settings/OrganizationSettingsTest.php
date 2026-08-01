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
            ->assertSee('Hero banners')
            ->assertSee('FAQs')
            ->assertSee('Frequently Asked Questions')
            ->assertSee('Members')
            ->assertSee('Organization members');
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

    public function test_admin_can_update_existing_hero_banners_only(): void
    {
        $hero = HeroBanner::query()->create([
            'organization_id' => $this->organization->id,
            'title' => 'Master every exam',
            'subtitle' => 'Mock tests',
            'description' => 'Practice with confidence',
            'primary_cta_label' => 'Explore',
            'primary_cta_url' => '/exams',
            'status' => 'active',
            'show_search' => true,
            'sort_order' => 1,
        ]);

        $this->actingAs($this->admin)
            ->postJson(route('admin.settings.organization.heroes.store'), [
                'title' => 'Should not create',
                'status' => 'active',
                'show_search' => true,
                'sort_order' => 9,
            ])
            ->assertForbidden()
            ->assertJsonPath('success', false);

        $this->actingAs($this->admin)
            ->putJson(route('admin.settings.organization.heroes.update', $hero->id), [
                'title' => 'Updated hero',
                'status' => 'active',
                'show_search' => false,
                'sort_order' => 2,
            ])
            ->assertOk()
            ->assertJsonPath('hero.title', 'Updated hero');

        $this->actingAs($this->admin)
            ->deleteJson(route('admin.settings.organization.heroes.destroy', $hero->id))
            ->assertForbidden()
            ->assertJsonPath('success', false);

        $this->assertDatabaseHas('hero_banners', [
            'id' => $hero->id,
            'title' => 'Updated hero',
        ]);
        $this->assertNull($hero->fresh()->deleted_at);
    }

    public function test_admin_can_manage_organization_members(): void
    {
        $create = $this->actingAs($this->admin)
            ->postJson(route('admin.settings.organization.members.store'), [
                'name' => 'Org Member',
                'email' => 'org-member@example.test',
                'password' => 'Password1!',
                'status' => 'active',
            ]);

        $create->assertCreated()->assertJsonPath('success', true);
        $memberId = (int) $create->json('member.id');

        $this->assertDatabaseHas('user_organizations', [
            'id' => $memberId,
            'organization_id' => $this->organization->id,
            'role' => OrganizationRoles::ORG_ADMIN,
            'status' => 'active',
        ]);

        $this->assertSame(
            OrganizationRoles::ORG_ADMIN,
            $create->json('member.role')
        );

        $this->actingAs($this->admin)
            ->getJson(route('admin.settings.organization.members.index', [
                'search' => 'org-member',
                'status' => 'active',
            ]))
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data');

        $this->actingAs($this->admin)
            ->putJson(route('admin.settings.organization.members.update', $memberId), [
                'name' => 'Org Member Updated',
                'email' => 'org-member@example.test',
                'status' => 'inactive',
            ])
            ->assertOk()
            ->assertJsonPath('member.status', 'inactive')
            ->assertJsonPath('member.role', OrganizationRoles::ORG_ADMIN);

        $this->actingAs($this->admin)
            ->deleteJson(route('admin.settings.organization.members.destroy', $memberId))
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertSoftDeleted('user_organizations', ['id' => $memberId]);
    }

    public function test_admin_can_invite_existing_user_without_resetting_password(): void
    {
        $existing = User::factory()->create([
            'name' => 'Existing User',
            'email' => 'existing-member@example.test',
            'password' => 'OriginalPass1!',
        ]);

        $response = $this->actingAs($this->admin)
            ->postJson(route('admin.settings.organization.members.store'), [
                'name' => 'Should Not Overwrite',
                'email' => 'existing-member@example.test',
                'status' => 'active',
            ]);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('member.role', OrganizationRoles::ORG_ADMIN)
            ->assertJsonPath('member.email', 'existing-member@example.test');

        $existing->refresh();
        $this->assertSame('Existing User', $existing->name);
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('OriginalPass1!', $existing->password));

        $this->assertDatabaseHas('user_organizations', [
            'user_id' => $existing->id,
            'organization_id' => $this->organization->id,
            'role' => OrganizationRoles::ORG_ADMIN,
        ]);
    }

    public function test_new_member_still_requires_password(): void
    {
        $this->actingAs($this->admin)
            ->postJson(route('admin.settings.organization.members.store'), [
                'name' => 'New Person',
                'email' => 'brand-new@example.test',
                'status' => 'active',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    public function test_admin_can_reinvite_soft_deleted_member_and_promote_candidate(): void
    {
        $user = User::factory()->create([
            'email' => 'promote-me@example.test',
            'password' => 'OriginalPass1!',
        ]);

        $candidate = UserOrganization::query()->create([
            'user_id' => $user->id,
            'organization_id' => $this->organization->id,
            'role' => OrganizationRoles::CANDIDATE,
            'status' => 'active',
        ]);

        $this->actingAs($this->admin)
            ->postJson(route('admin.settings.organization.members.store'), [
                'name' => 'Promote Me',
                'email' => 'promote-me@example.test',
                'status' => 'active',
            ])
            ->assertCreated()
            ->assertJsonPath('member.role', OrganizationRoles::ORG_ADMIN)
            ->assertJsonPath('member.id', $candidate->id);

        $this->assertSame(OrganizationRoles::ORG_ADMIN, $candidate->fresh()->role);

        $this->actingAs($this->admin)
            ->deleteJson(route('admin.settings.organization.members.destroy', $candidate->id))
            ->assertOk();

        $this->assertSoftDeleted('user_organizations', ['id' => $candidate->id]);

        $this->actingAs($this->admin)
            ->postJson(route('admin.settings.organization.members.store'), [
                'name' => 'Promote Me',
                'email' => 'promote-me@example.test',
                'status' => 'inactive',
            ])
            ->assertCreated()
            ->assertJsonPath('member.id', $candidate->id)
            ->assertJsonPath('member.status', 'inactive')
            ->assertJsonPath('member.role', OrganizationRoles::ORG_ADMIN);

        $this->assertNull($candidate->fresh()->deleted_at);
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('OriginalPass1!', $user->fresh()->password));
    }

    public function test_organization_settings_page_embeds_parseable_hero_payload(): void
    {
        HeroBanner::query()->create([
            'organization_id' => $this->organization->id,
            'title' => 'Hero "Quoted" Title',
            'subtitle' => "Line with 'apostrophe'",
            'status' => 'active',
            'show_search' => true,
            'sort_order' => 1,
        ]);

        $html = $this->actingAs($this->admin)
            ->get(route('admin.settings.organization'))
            ->assertOk()
            ->assertSee('data-hero=', false)
            ->getContent();

        $this->assertMatchesRegularExpression('/data-hero="[^"]+"/', $html);
    }

    public function test_admin_can_manage_faqs_via_modal_api(): void
    {        $category = \App\Models\Cms\FaqCategory::query()->create([
            'organization_id' => $this->organization->id,
            'name' => 'General',
            'slug' => 'general',
            'status' => 'active',
            'sort_order' => 1,
        ]);

        $create = $this->actingAs($this->admin)
            ->postJson(route('admin.settings.organization.faqs.store'), [
                'question' => 'How do I start?',
                'answer' => 'Create an account and browse exams.',
                'faq_category_id' => $category->id,
                'status' => 'active',
                'sort_order' => 1,
                'is_featured' => true,
            ]);

        $create->assertCreated()->assertJsonPath('success', true);
        $faqId = (int) $create->json('faq.id');

        $this->assertDatabaseHas('faqs', [
            'id' => $faqId,
            'question' => 'How do I start?',
            'status' => 'active',
            'organization_id' => $this->organization->id,
        ]);

        $this->actingAs($this->admin)
            ->getJson(route('admin.settings.organization.faqs.index', [
                'search' => 'start',
                'status' => 'active',
                'category_id' => $category->id,
            ]))
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data');

        $this->actingAs($this->admin)
            ->putJson(route('admin.settings.organization.faqs.update', $faqId), [
                'question' => 'How do I begin?',
                'answer' => 'Updated answer.',
                'faq_category_id' => $category->id,
                'status' => 'inactive',
                'sort_order' => 2,
                'is_featured' => false,
            ])
            ->assertOk()
            ->assertJsonPath('faq.status', 'inactive');

        $this->actingAs($this->admin)
            ->deleteJson(route('admin.settings.organization.faqs.destroy', $faqId))
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertSoftDeleted('faqs', ['id' => $faqId]);
    }
}
