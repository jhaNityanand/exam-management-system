<?php

namespace Tests\Feature\Settings;

use App\Models\Organization;
use App\Models\User;
use App\Models\UserOrganization;
use App\Services\Settings\IntegrationsSettingsService;
use App\Services\Settings\SecuritySettingsService;
use App\Support\OrganizationRoles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class IntegrationsAndSecuritySettingsTest extends TestCase
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
            'email' => 'phase7-admin@example.test',
            'status' => 'active',
        ]);

        UserOrganization::query()->create([
            'user_id' => $this->admin->id,
            'organization_id' => $this->organization->id,
            'role' => OrganizationRoles::ADMIN,
            'status' => 'active',
        ]);

        app(IntegrationsSettingsService::class)->seedDefaults($this->organization->id);
        app(SecuritySettingsService::class)->seedDefaults($this->organization->id);
    }

    public function test_admin_can_view_integrations_settings(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.settings.integrations'))
            ->assertOk()
            ->assertSee('Integrations & Privacy')
            ->assertSee('Cookie consent')
            ->assertSee('GA4 Measurement ID');
    }

    public function test_admin_can_update_integrations_settings(): void
    {
        $this->actingAs($this->admin)
            ->putJson(route('admin.settings.integrations.update'), [
                'analytics_enabled' => true,
                'google_analytics_id' => 'G-TEST123',
                'gtm_container_id' => 'GTM-TEST',
                'facebook_pixel_id' => '999888777',
                'custom_head_scripts' => '',
                'custom_body_scripts' => '',
                'cookies_enabled' => true,
                'cookies_mode' => 'opt_in',
                'cookies_title' => 'Cookies',
                'cookies_message' => 'We use cookies.',
                'cookies_accept_label' => 'Accept',
                'cookies_reject_label' => 'Reject',
                'cookies_policy_url' => '/privacy-policy',
                'default_timezone' => 'Asia/Kolkata',
                'default_locale' => 'en',
                'registration_enabled' => false,
                'newsletter_enabled' => true,
            ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('settings.google_analytics_id', 'G-TEST123')
            ->assertJsonPath('settings.registration_enabled', false);

        $this->assertFalse(
            app(IntegrationsSettingsService::class)->isRegistrationEnabled($this->organization->id)
        );

        auth()->logout();
        $this->get(route('register'))->assertNotFound();
    }

    public function test_frontend_includes_cookie_banner_config(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('et-cookie-banner', false)
            ->assertSee('ExamtubeIntegrations', false);
    }

    public function test_admin_can_view_and_update_security_settings(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.settings.security'))
            ->assertOk()
            ->assertSee('reCAPTCHA')
            ->assertSee('Login protection');

        $this->actingAs($this->admin)
            ->putJson(route('admin.settings.security.update'), [
                'recaptcha_enabled' => true,
                'recaptcha_version' => 'v3',
                'recaptcha_site_key' => 'site-key-test',
                'recaptcha_secret_key' => 'secret-key-test',
                'recaptcha_score_threshold' => '0.4',
                'recaptcha_on_login' => true,
                'recaptcha_on_register' => true,
                'recaptcha_on_contact' => true,
                'recaptcha_on_newsletter' => false,
                'recaptcha_on_password_reset' => true,
                'login_lockout_enabled' => true,
                'login_max_attempts' => 3,
                'login_decay_minutes' => 10,
            ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('settings.login_max_attempts', 3)
            ->assertJsonPath('settings.has_recaptcha_secret', true);

        $runtime = app(SecuritySettingsService::class)->getForRuntime($this->organization->id);
        $this->assertSame('secret-key-test', $runtime['recaptcha_secret_key']);
        $this->assertTrue(app(SecuritySettingsService::class)->requiresRecaptcha('login', $this->organization->id));
        $this->assertFalse(app(SecuritySettingsService::class)->requiresRecaptcha('newsletter', $this->organization->id));
    }

    public function test_recaptcha_verification_accepts_valid_token(): void
    {
        app(SecuritySettingsService::class)->update([
            'recaptcha_enabled' => true,
            'recaptcha_version' => 'v3',
            'recaptcha_site_key' => 'site',
            'recaptcha_secret_key' => 'secret',
            'recaptcha_score_threshold' => '0.5',
            'recaptcha_on_login' => true,
            'recaptcha_on_register' => true,
            'recaptcha_on_contact' => true,
            'recaptcha_on_newsletter' => true,
            'recaptcha_on_password_reset' => true,
            'login_lockout_enabled' => true,
            'login_max_attempts' => 5,
            'login_decay_minutes' => 1,
        ], $this->organization->id);

        Http::fake([
            'www.google.com/recaptcha/api/siteverify' => Http::response([
                'success' => true,
                'score' => 0.9,
            ]),
        ]);

        $result = app(SecuritySettingsService::class)->verify('token', 'login', $this->organization->id);
        $this->assertTrue($result['ok']);
    }
}
