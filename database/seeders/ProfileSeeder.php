<?php

namespace Database\Seeders;

use App\Models\Profile;
use App\Models\User;
use Database\Seeders\Concerns\ResolvesDemoContext;
use Database\Seeders\Support\SeederContact;
use Illuminate\Database\Seeder;

class ProfileSeeder extends Seeder
{
    use ResolvesDemoContext;

    public function run(): void
    {
        $org = $this->demoOrganization();

        $profiles = [
            SeederContact::EMAIL_ADMIN => [
                'bio' => 'Application administrator for Examtube.',
                'phone' => SeederContact::PHONE,
                'city' => SeederContact::CITY,
                'state_region' => SeederContact::STATE,
                'country' => SeederContact::COUNTRY,
                'gender' => 'male',
            ],
            SeederContact::EMAIL_INFO => [
                'bio' => 'Organization administrator managing exams, content, and candidates.',
                'phone' => SeederContact::PHONE,
                'city' => SeederContact::CITY,
                'state_region' => SeederContact::STATE,
                'country' => SeederContact::COUNTRY,
                'gender' => 'female',
            ],
            'candidate@examtube.in' => [
                'bio' => 'Demo candidate account for exam practice and results.',
                'phone' => SeederContact::PHONE,
                'city' => SeederContact::CITY,
                'state_region' => SeederContact::STATE,
                'country' => SeederContact::COUNTRY,
                'gender' => 'male',
            ],
        ];

        foreach (User::query()->get() as $user) {
            $extra = $profiles[$user->email] ?? [];

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
                    'avatar' => null,
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

        $this->command?->info('ProfileSeeder: profiles seeded without default avatars.');
    }
}
