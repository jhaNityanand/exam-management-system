<?php

namespace Database\Seeders;

use App\Models\Cms\ContactMessage;
use App\Models\Cms\NewsletterSubscriber;
use App\Models\User;
use Database\Seeders\Concerns\ResolvesDemoContext;
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
            ['email' => 'ananya.sharma@example.com', 'name' => 'Ananya Sharma'],
            ['email' => 'rahul.nair@example.com', 'name' => 'Rahul Nair'],
            ['email' => 'fatima.khan@example.com', 'name' => 'Fatima Khan'],
            ['email' => 'vikram.joshi@example.com', 'name' => 'Vikram Joshi'],
            ['email' => 'candidate@examtube.in', 'name' => 'Demo Candidate'],
            ['email' => 'mentor.hub@example.com', 'name' => 'Campus Mentor Hub'],
        ];

        foreach ($subscribers as $index => $row) {
            NewsletterSubscriber::query()->create([
                'organization_id' => $org->id,
                'email' => $row['email'],
                'name' => $row['name'],
                'status' => $index === 5 ? 'unsubscribed' : 'subscribed',
                'source' => 'seeder',
                'subscribed_at' => now()->subDays(30 - $index),
                'unsubscribed_at' => $index === 5 ? now()->subDays(2) : null,
                'ip_address' => '127.0.0.1',
            ]);
        }

        $messages = [
            [
                'name' => 'Priya Mehta',
                'email' => 'priya.mehta@example.com',
                'phone' => '+91 98765 11111',
                'subject' => 'Institute onboarding for 200 candidates',
                'message' => 'We run campus placements for BCA/MCA students and want branded mock interviews on Examtube. Can you share onboarding steps?',
                'status' => 'new',
            ],
            [
                'name' => 'Arjun Desai',
                'email' => 'arjun.desai@example.com',
                'phone' => '+91 98765 22222',
                'subject' => 'Question about attempt history',
                'message' => 'I attempted the aptitude screening twice. Where can I compare both result summaries side by side?',
                'status' => 'read',
            ],
            [
                'name' => 'Neha Kapoor',
                'email' => 'neha.kapoor@example.com',
                'phone' => null,
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

        // Ensure demo users appear as newsletter subscribers too when missing.
        foreach (User::query()->whereIn('email', ['admin@examtube.in', 'candidate@examtube.in'])->get() as $user) {
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
