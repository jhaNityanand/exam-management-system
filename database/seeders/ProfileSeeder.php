<?php

namespace Database\Seeders;

use App\Models\Profile;
use App\Models\User;
use Database\Seeders\Concerns\ResolvesDemoContext;
use Database\Seeders\Support\SeederContact;
use Database\Seeders\Support\SeedImageLibrary;
use Illuminate\Database\Seeder;
use Throwable;

class ProfileSeeder extends Seeder
{
    use ResolvesDemoContext;

    public function run(): void
    {
        $org = $this->demoOrganization();
        $images = new SeedImageLibrary;

        if ($org) {
            $purged = $images->purge($org->id, 'profile');
            $this->command?->info("ProfileSeeder: purged {$purged} previously seeded profile image(s).");
        }

        $profiles = [
            'admin@examtube.in' => [
                'bio' => 'Application administrator for the Examtube platform demo workspace.',
                'phone' => SeederContact::PHONE,
                'city' => SeederContact::CITY,
                'state_region' => SeederContact::STATE,
                'country' => SeederContact::COUNTRY,
                'gender' => 'male',
            ],
            'info@examtube.in' => [
                'bio' => 'Organization admin managing exams, CMS, galleries, and candidate results for Demo Organization.',
                'phone' => SeederContact::PHONE,
                'city' => SeederContact::CITY,
                'state_region' => SeederContact::STATE,
                'country' => SeederContact::COUNTRY,
                'gender' => 'female',
            ],
            'candidate@examtube.in' => [
                'bio' => 'Demo candidate preparing for campus interviews with timed aptitude and technical mocks.',
                'phone' => SeederContact::PHONE,
                'city' => SeederContact::CITY,
                'state_region' => SeederContact::STATE,
                'country' => SeederContact::COUNTRY,
                'gender' => 'male',
            ],
        ];

        foreach (User::query()->get() as $user) {
            $extra = $profiles[$user->email] ?? [];
            $avatarPath = null;

            if ($org && isset($profiles[$user->email])) {
                try {
                    $gallery = $images->storeSeoDefault(
                        $org->id,
                        'profile',
                        $user->id,
                        'profile',
                        [
                            'alt_text' => $user->name.' avatar',
                            'description' => 'Seeded profile avatar',
                            'slug_suffix' => $user->username ?: (string) $user->id,
                        ]
                    );
                    $avatarPath = $gallery->file_path;
                } catch (Throwable $e) {
                    $this->command?->warn("ProfileSeeder: avatar failed for {$user->email}: {$e->getMessage()}");
                }
            }

            Profile::query()->updateOrCreate(
                ['id' => $user->id],
                [
                    'status' => 'active',
                    'bio' => $extra['bio'] ?? null,
                    'phone' => $extra['phone'] ?? null,
                    'city' => $extra['city'] ?? null,
                    'state_region' => $extra['state_region'] ?? null,
                    'country' => $extra['country'] ?? SeederContact::COUNTRY,
                    'gender' => $extra['gender'] ?? null,
                    'avatar' => $avatarPath,
                    'default_organization_id' => $org?->id,
                    'notification_preferences' => [
                        'exam_results' => true,
                        'newsletter' => true,
                        'announcements' => true,
                    ],
                    'privacy_settings' => [
                        'show_profile' => true,
                        'show_results' => false,
                    ],
                ]
            );
        }

        $this->command?->info('ProfileSeeder: profiles enriched with bios, locations, and avatars.');
    }
}
