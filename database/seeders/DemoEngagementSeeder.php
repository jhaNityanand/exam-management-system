<?php

namespace Database\Seeders;

use App\Models\Cms\ContactMessage;
use App\Models\Cms\NewsletterSubscriber;
use App\Models\User;
use Database\Seeders\Concerns\ResolvesDemoContext;
use Database\Seeders\Support\SeederContact;
use Illuminate\Database\Seeder;

/**
 * Seeds newsletter subscribers and contact inbox messages for a complete admin demo.
 */
class DemoEngagementSeeder extends Seeder
{
    use ResolvesDemoContext;

    public function run(): void
    {
        $org = $this->demoOrganization();
        if (! $org) {
            $this->command?->warn('DemoEngagementSeeder: demo-org missing. Skipping.');

            return;
        }

        NewsletterSubscriber::query()->where('organization_id', $org->id)->delete();
        ContactMessage::query()->where('organization_id', $org->id)->delete();

        $subscribers = [
            ['email' => SeederContact::EMAIL_SUPPORT, 'name' => 'Support Desk'],
            ['email' => SeederContact::EMAIL_INFO, 'name' => 'Info Desk'],
            ['email' => SeederContact::EMAIL_CONTACT, 'name' => 'Contact Desk'],
            ['email' => 'candidate@examtube.in', 'name' => 'Demo Candidate'],
        ];

        foreach ($subscribers as $index => $row) {
            NewsletterSubscriber::query()->create([
                'organization_id' => $org->id,
                'email' => $row['email'],
                'name' => $row['name'],
                'status' => 'subscribed',
                'source' => 'seeder',
                'subscribed_at' => now()->subDays(30 - $index),
                'unsubscribed_at' => null,
                'ip_address' => '127.0.0.1',
            ]);
        }

        $messages = [
            [
                'name' => 'Priya Mehta',
                'email' => SeederContact::EMAIL_CONTACT,
                'phone' => SeederContact::PHONE,
                'subject' => 'Institute onboarding for 200 candidates',
                'message' => 'We run campus placements for BCA/MCA students and want branded mock interviews on Examtube. Can you share onboarding steps?',
                'status' => 'new',
            ],
            [
                'name' => 'Arjun Desai',
                'email' => SeederContact::EMAIL_INFO,
                'phone' => SeederContact::PHONE,
                'subject' => 'Question about attempt history',
                'message' => 'I attempted the aptitude screening twice. Where can I compare both result summaries side by side?',
                'status' => 'read',
            ],
            [
                'name' => 'Neha Kapoor',
                'email' => SeederContact::EMAIL_SUPPORT,
                'phone' => SeederContact::PHONE,
                'subject' => 'Partnership / sponsored prep series',
                'message' => 'Interested in sponsoring a banking exam prep series with banner placements on blog and exam list pages.',
                'status' => 'new',
            ],
        ];

        foreach ($messages as $message) {
            ContactMessage::query()->create(array_merge($message, [
                'organization_id' => $org->id,
                'ip_address' => '127.0.0.1',
            ]));
        }

        foreach (User::query()->whereIn('email', [SeederContact::EMAIL_INFO, 'candidate@examtube.in'])->get() as $user) {
            NewsletterSubscriber::query()->firstOrCreate(
                [
                    'organization_id' => $org->id,
                    'email' => $user->email,
                ],
                [
                    'name' => $user->name,
                    'status' => 'subscribed',
                    'source' => 'seeder',
                    'subscribed_at' => now()->subDays(10),
                    'ip_address' => '127.0.0.1',
                ]
            );
        }

        $this->command?->info('DemoEngagementSeeder: newsletter subscribers and contact messages ready.');
    }
}
