<?php

namespace Database\Seeders;

use App\Models\Profile;
use App\Models\User;
use Database\Seeders\Concerns\ResolvesDemoContext;
use Database\Seeders\Support\SeedAssetGenerator;
use Database\Seeders\Support\SeedImageLibrary;
use Illuminate\Database\Seeder;
use Throwable;

class ProfileSeeder extends Seeder
{
    use ResolvesDemoContext;

    public function run(): void
    {
        $org = $this->demoOrganization();
        (new SeedAssetGenerator)->ensure();
        $images = new SeedImageLibrary;

        if ($org) {
            $purged = $images->purge($org->id, 'profile');
            $this->command?->info("ProfileSeeder: purged {$purged} previously seeded profile image(s).");
        }

        $profiles = [
            'admin@example.in' => [
                'bio' => 'Application administrator for the Examtube platform demo workspace.',
                'phone' => '+91 90000 00001',
                'city' => 'Bengaluru',
                'state_region' => 'Karnataka',
                'country' => 'IN',
                'gender' => 'male',
                'avatar_seed' => 'avatars/avatar-user-appadmin.jpg',
            ],
            'admin@examtube.in' => [
                'bio' => 'Organization admin managing exams, CMS, galleries, and candidate results for Demo Organization.',
                'phone' => '+91 90000 00002',
                'city' => 'Bengaluru',
                'state_region' => 'Karnataka',
                'country' => 'IN',
                'gender' => 'female',
                'avatar_seed' => 'avatars/avatar-user-orgadmin.jpg',
            ],
            'candidate@examtube.in' => [
                'bio' => 'Demo candidate preparing for campus interviews with timed aptitude and technical mocks.',
                'phone' => '+91 90000 00003',
                'city' => 'Pune',
                'state_region' => 'Maharashtra',
                'country' => 'IN',
                'gender' => 'male',
                'avatar_seed' => 'avatars/avatar-user-candidate.jpg',
            ],
        ];

        foreach (User::query()->get() as $user) {
            $extra = $profiles[$user->email] ?? [];
            $avatarPath = null;

            if ($org && ! empty($extra['avatar_seed'])) {
                try {
                    $gallery = $images->storeFromPublicSeed(
                        $org->id,
                        $extra['avatar_seed'],
                        $user->id,
                        'profile',
                        [
                            'alt_text' => $user->name.' avatar',
                            'description' => 'Seeded profile avatar',
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
                    'country' => $extra['country'] ?? 'India',
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
