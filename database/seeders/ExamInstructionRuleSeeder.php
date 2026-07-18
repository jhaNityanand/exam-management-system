<?php

namespace Database\Seeders;

use App\Models\ExamInstructionRule;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ExamInstructionRuleSeeder extends Seeder
{
    public function run(): void
    {
        $org = Organization::where('slug', 'demo-org')->first();
        $admin = User::where('email', 'orgadmin@examms.test')->first();

        if (! $org) {
            $this->command?->warn('ExamInstructionRuleSeeder: demo-org not found. Skipping.');

            return;
        }

        $rules = [
            [
                'title' => 'Do not use unfair means during the examination.',
                'description' => 'Any form of cheating, collusion, or impersonation will lead to cancellation of the attempt.',
                'category' => 'integrity',
                'icon' => 'shield',
                'is_default' => true,
                'is_required' => true,
            ],
            [
                'title' => 'Mobile phones and electronic devices are prohibited.',
                'description' => 'Candidates must keep phones, smartwatches, and unauthorized devices away during the session.',
                'category' => 'integrity',
                'icon' => 'phone-off',
                'is_default' => true,
                'is_required' => false,
            ],
            [
                'title' => 'The exam will automatically end when the allotted time expires.',
                'description' => 'Answers are submitted automatically when the timer reaches zero.',
                'category' => 'submission',
                'icon' => 'clock',
                'is_default' => true,
                'is_required' => false,
            ],
            [
                'title' => 'Ensure a stable internet connection.',
                'description' => 'Unstable connectivity can interrupt timing, answer sync, or final submission.',
                'category' => 'environment',
                'icon' => 'wifi',
                'is_default' => true,
                'is_required' => false,
            ],
            [
                'title' => 'Do not refresh or close the browser window.',
                'description' => 'Page reload or closing the window may end the attempt or lock further answers.',
                'category' => 'environment',
                'icon' => 'refresh',
                'is_default' => true,
                'is_required' => false,
            ],
            [
                'title' => 'Multiple login sessions are not allowed.',
                'description' => 'Logging in from another device or browser may invalidate the active attempt.',
                'category' => 'integrity',
                'icon' => 'users',
                'is_default' => false,
                'is_required' => false,
            ],
            [
                'title' => 'Read all questions carefully before answering.',
                'description' => 'Review the full question prompt and options before recording a response.',
                'category' => 'other',
                'icon' => 'book',
                'is_default' => false,
                'is_required' => false,
            ],
            [
                'title' => 'Negative marking rules apply where configured.',
                'description' => 'Incorrect answers may reduce the total score according to the published marking scheme.',
                'category' => 'submission',
                'icon' => 'minus-circle',
                'is_default' => false,
                'is_required' => false,
            ],
            [
                'title' => 'Once the exam is submitted, it cannot be reverted.',
                'description' => 'Candidates cannot edit or reopen the submission after final submit.',
                'category' => 'submission',
                'icon' => 'lock',
                'is_default' => true,
                'is_required' => true,
                'slug' => 'no_revert_after_submit',
            ],
            [
                'title' => 'Users cannot retake the exam after submission.',
                'description' => 'The same candidate cannot attempt this exam again once completed.',
                'category' => 'submission',
                'icon' => 'ban',
                'is_default' => true,
                'is_required' => false,
                'slug' => 'no_retake_after_submit',
            ],
            [
                'title' => 'Switching tabs may auto-submit the exam.',
                'description' => 'Leaving the active exam window can immediately end the attempt.',
                'category' => 'monitoring',
                'icon' => 'tabs',
                'is_default' => true,
                'is_required' => false,
                'slug' => 'tab_switch_autosubmit',
            ],
            [
                'title' => 'Full-screen mode is required during the exam.',
                'description' => 'Candidates must remain in full-screen mode for the full session.',
                'category' => 'monitoring',
                'icon' => 'maximize',
                'is_default' => false,
                'is_required' => false,
                'slug' => 'fullscreen_required',
            ],
            [
                'title' => 'Refreshing the exam page may end the attempt.',
                'description' => 'Page reload can cause automatic submission or attempt lock.',
                'category' => 'environment',
                'icon' => 'refresh',
                'is_default' => false,
                'is_required' => false,
                'slug' => 'no_page_refresh',
            ],
            [
                'title' => 'Disable copy/paste during the exam.',
                'description' => 'Clipboard actions can be blocked while the exam is active.',
                'category' => 'integrity',
                'icon' => 'clipboard',
                'is_default' => false,
                'is_required' => false,
                'slug' => 'disable_copy_paste',
            ],
            [
                'title' => 'Webcam monitoring is enabled.',
                'description' => 'Candidates may be monitored by camera during the attempt.',
                'category' => 'monitoring',
                'icon' => 'camera',
                'is_default' => false,
                'is_required' => false,
                'slug' => 'webcam_monitoring_enabled',
            ],
            [
                'title' => 'Internet disconnection may affect the exam session.',
                'description' => 'Unstable connectivity can interrupt timing or answer sync.',
                'category' => 'environment',
                'icon' => 'wifi-off',
                'is_default' => false,
                'is_required' => false,
                'slug' => 'disconnection_may_affect_session',
            ],
            [
                'title' => 'Each question can be attempted only once.',
                'description' => 'Candidates cannot return to revise an already attempted question.',
                'category' => 'submission',
                'icon' => 'one',
                'is_default' => false,
                'is_required' => false,
                'slug' => 'single_attempt_per_question',
            ],
            [
                'title' => 'Identity verification is required before exam start.',
                'description' => 'Candidates must complete required identity checks before launching the test.',
                'category' => 'monitoring',
                'icon' => 'id',
                'is_default' => false,
                'is_required' => false,
                'slug' => 'id_verification_required',
            ],
            [
                'title' => 'Suspicious activity may be flagged for review.',
                'description' => 'The platform may log unusual behavior for proctor/admin review.',
                'category' => 'monitoring',
                'icon' => 'alert',
                'is_default' => false,
                'is_required' => false,
                'slug' => 'suspicious_activity_flagged',
            ],
            [
                'title' => 'Taking screenshots is not allowed during the exam.',
                'description' => 'Candidates must not capture screenshots or screen recordings while the exam is in progress.',
                'category' => 'integrity',
                'icon' => 'camera-off',
                'is_default' => false,
                'is_required' => false,
                'slug' => 'no_screenshots',
            ],
        ];

        foreach ($rules as $index => $rule) {
            $slug = $rule['slug'] ?? Str::slug($rule['title']);

            ExamInstructionRule::updateOrCreate(
                [
                    'organization_id' => $org->id,
                    'slug' => $slug,
                ],
                [
                    'title' => $rule['title'],
                    'description' => $rule['description'],
                    'status' => 'active',
                    'sort_order' => ($index + 1) * 10,
                    'icon' => $rule['icon'] ?? null,
                    'category' => $rule['category'] ?? 'other',
                    'is_default' => (bool) ($rule['is_default'] ?? false),
                    'is_required' => (bool) ($rule['is_required'] ?? false),
                    'created_by' => $admin?->id,
                    'updated_by' => $admin?->id,
                ]
            );
        }
    }
}
