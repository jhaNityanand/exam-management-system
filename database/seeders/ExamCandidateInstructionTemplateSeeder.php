<?php

namespace Database\Seeders;

use App\Models\ExamCandidateInstructionTemplate;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Seeder;

class ExamCandidateInstructionTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $org = Organization::where('slug', 'demo-org')->first();
        $admin = User::where('email', 'orgadmin@examms.test')->first();

        if (! $org) {
            $this->command?->warn('ExamCandidateInstructionTemplateSeeder: demo-org not found. Skipping.');

            return;
        }

        $templates = [
            [
                'name' => 'General Assessment Instructions',
                'slug' => 'general-assessment',
                'template_type' => 'general',
                'is_default' => true,
                'version' => '1.0',
                'icon' => 'clipboard-list',
                'sort_order' => 10,
                'description' => $this->generalTemplate(),
            ],
            [
                'name' => 'Proctored Compliance Instructions',
                'slug' => 'proctored-compliance',
                'template_type' => 'proctored',
                'is_default' => false,
                'version' => '1.0',
                'icon' => 'shield-check',
                'sort_order' => 20,
                'description' => $this->proctoredTemplate(),
            ],
            [
                'name' => 'Coding Round Instructions',
                'slug' => 'coding-round',
                'template_type' => 'coding',
                'is_default' => false,
                'version' => '1.0',
                'icon' => 'code',
                'sort_order' => 30,
                'description' => $this->codingTemplate(),
            ],
            [
                'name' => 'Certification Exam Instructions',
                'slug' => 'certification-exam',
                'template_type' => 'certification',
                'is_default' => false,
                'version' => '1.0',
                'icon' => 'award',
                'sort_order' => 40,
                'description' => $this->certificationTemplate(),
            ],
        ];

        foreach ($templates as $template) {
            ExamCandidateInstructionTemplate::updateOrCreate(
                [
                    'organization_id' => $org->id,
                    'slug' => $template['slug'],
                ],
                [
                    'name' => $template['name'],
                    'description' => $template['description'],
                    'status' => 'active',
                    'sort_order' => $template['sort_order'],
                    'is_default' => $template['is_default'],
                    'template_type' => $template['template_type'],
                    'version' => $template['version'],
                    'icon' => $template['icon'],
                    'created_by' => $admin?->id,
                    'updated_by' => $admin?->id,
                ]
            );
        }
    }

    private function section(string $title, string $bodyHtml): string
    {
        return '<h3>'.$title.'</h3>'.$bodyHtml;
    }

    private function generalTemplate(): string
    {
        return implode('', [
            '<h2>Welcome</h2>',
            '<p>Welcome to this assessment. Please read the instructions carefully before you begin. Your honest and independent effort helps us evaluate your skills fairly.</p>',
            $this->section('Examination Guidelines', '<ul><li>Read every question completely before answering.</li><li>Manage your time across all sections.</li><li>Review answers before final submission when permitted.</li><li>Follow all on-screen prompts and warnings.</li></ul>'),
            $this->section('Eligibility', '<ul><li>Only authorized candidates may attempt this exam.</li><li>Use your registered credentials; sharing login details is prohibited.</li></ul>'),
            $this->section('Time Limits', '<ul><li>The exam duration is shown on the timer.</li><li>The session ends automatically when time expires.</li><li>Plan buffer time for review and submission.</li></ul>'),
            $this->section('Navigation', '<ul><li>Use Next / Previous controls where available.</li><li>Flag questions for review if the interface supports it.</li><li>Do not refresh, close, or navigate away from the exam window.</li></ul>'),
            $this->section('Marking Scheme', '<ul><li>Marks for each question are displayed with the question.</li><li>Total and passing marks are defined by the exam administrator.</li></ul>'),
            $this->section('Negative Marking Policy', '<ul><li>Negative marking applies only when enabled for this exam.</li><li>If enabled, incorrect answers may reduce your score as published in the exam rules.</li><li>Unanswered questions typically carry no penalty unless stated otherwise.</li></ul>'),
            $this->section('Technical Requirements', '<ul><li>Use a supported desktop or laptop browser.</li><li>Disable pop-up blockers that may interfere with the exam.</li><li>Close unnecessary applications to free system resources.</li></ul>'),
            $this->section('Browser Requirements', '<ul><li>Recommended: latest Chrome, Edge, or Firefox.</li><li>Enable JavaScript and cookies for this site.</li><li>Avoid using private/incognito mode if warned by the system.</li></ul>'),
            $this->section('Internet Connectivity', '<ul><li>Use a stable wired or strong Wi-Fi connection.</li><li>Avoid switching networks during the attempt.</li><li>If disconnected briefly, reconnect quickly and continue if the session remains active.</li></ul>'),
            $this->section('Prohibited Activities', '<ul><li>No unfair means, collusion, or impersonation.</li><li>No unauthorized devices, notes, or external help.</li><li>No capturing or distributing exam content.</li></ul>'),
            $this->section('Submission Guidelines', '<ul><li>Submit only when you have completed all required sections.</li><li>After final submission, answers cannot be changed.</li><li>Confirm you see a successful submission message before leaving.</li></ul>'),
            $this->section('Support & Contact', '<p>If you face a technical issue that blocks your attempt, contact your exam administrator or support desk immediately with your candidate ID, exam name, and a short description of the problem.</p>'),
        ]);
    }

    private function proctoredTemplate(): string
    {
        return implode('', [
            '<h2>Welcome to Your Proctored Examination</h2>',
            '<p>This assessment is conducted under supervised (proctored) conditions. Please review all compliance requirements before starting.</p>',
            $this->section('Examination Guidelines', '<ul><li>Remain alone in a quiet, well-lit room.</li><li>Keep your face clearly visible to the camera at all times.</li><li>Follow all proctor prompts without delay.</li></ul>'),
            $this->section('Eligibility & Identity', '<ul><li>Complete identity verification before launch.</li><li>Keep a valid photo ID ready if requested.</li><li>Do not allow anyone else to enter the exam area.</li></ul>'),
            $this->section('Time Limits', '<ul><li>The timer starts when the exam launches.</li><li>Breaks are not permitted unless explicitly allowed.</li><li>The attempt ends automatically when time expires.</li></ul>'),
            $this->section('Navigation', '<ul><li>Stay in full-screen mode for the entire session.</li><li>Do not switch tabs, open other applications, or use a second screen.</li><li>Tab switches or focus loss may trigger warnings or auto-submit.</li></ul>'),
            $this->section('Marking & Negative Marking', '<ul><li>Scoring follows the published marking scheme.</li><li>Where negative marking is enabled, incorrect responses may reduce your score.</li></ul>'),
            $this->section('Technical & Browser Requirements', '<ul><li>Allow camera and microphone access when prompted.</li><li>Use the latest Chrome or Edge on a desktop/laptop.</li><li>Close all unused browser tabs and background apps.</li></ul>'),
            $this->section('Internet Connectivity', '<ul><li>Maintain a continuous, stable connection.</li><li>Disconnections may interrupt monitoring and can invalidate the session.</li></ul>'),
            $this->section('Prohibited Activities', '<ul><li>No phones, smartwatches, earbuds, or secondary devices.</li><li>No talking, reading answers aloud, or receiving assistance.</li><li>No screen sharing, virtual machines, or remote desktop tools.</li></ul>'),
            $this->section('Submission Guidelines', '<ul><li>Submit before the timer ends whenever possible.</li><li>After submission, remain seated until the proctor confirms completion if required.</li></ul>'),
            $this->section('Support & Contact', '<p>For proctoring or access issues, contact the support channel provided by your administrator and mention that this is a proctored session.</p>'),
        ]);
    }

    private function codingTemplate(): string
    {
        return implode('', [
            '<h2>Welcome to the Coding Assessment</h2>',
            '<p>This round evaluates problem-solving, coding quality, and clarity. Read the problem statements carefully before writing code.</p>',
            $this->section('Examination Guidelines', '<ul><li>Understand constraints, inputs, and expected outputs first.</li><li>Write readable, testable code with clear naming.</li><li>Document assumptions briefly in comments when helpful.</li></ul>'),
            $this->section('Eligibility', '<ul><li>Only the registered candidate may attempt this coding round.</li><li>Do not share editor access or collaborate with others during the attempt.</li></ul>'),
            $this->section('Time Limits', '<ul><li>Allocate time for reading, coding, testing, and final checks.</li><li>Unsubmitted work is not considered once the timer ends.</li></ul>'),
            $this->section('Navigation & Editor Use', '<ul><li>Use the in-platform editor and run tools provided.</li><li>Do not leave the exam window for external IDEs unless expressly allowed.</li><li>Save or run tests frequently within the allowed tools.</li></ul>'),
            $this->section('Marking Scheme', '<ul><li>Scoring may consider correctness, efficiency, edge cases, and code quality.</li><li>Partial credit may apply depending on the exam configuration.</li></ul>'),
            $this->section('Negative Marking Policy', '<ul><li>Negative marking is uncommon for coding rounds but follows the exam configuration when enabled.</li></ul>'),
            $this->section('Technical Requirements', '<ul><li>Stable keyboard/mouse and adequate screen size recommended.</li><li>Ensure the language runtime/environment shown in the platform is available.</li></ul>'),
            $this->section('Browser & Connectivity', '<ul><li>Use a modern desktop browser (Chrome/Edge preferred).</li><li>Keep a reliable internet connection; auto-save may depend on connectivity.</li></ul>'),
            $this->section('Prohibited Activities', '<ul><li>No plagiarism, AI-assisted solutions (unless allowed), or third-party code drop-ins when forbidden.</li><li>No unauthorized online searches if the exam is closed-book.</li></ul>'),
            $this->section('Submission Guidelines', '<ul><li>Submit each problem within the platform before time expires.</li><li>Verify that your final code is the version you intend to be graded.</li></ul>'),
            $this->section('Support & Contact', '<p>If the coding environment fails to load or run, notify support immediately with screenshots and your candidate ID.</p>'),
        ]);
    }

    private function certificationTemplate(): string
    {
        return implode('', [
            '<h2>Welcome to Your Certification Examination</h2>',
            '<p>You are about to attempt a formal certification assessment. Please follow all academic and professional integrity requirements.</p>',
            $this->section('Examination Guidelines', '<ul><li>Answer independently based on your own knowledge and preparation.</li><li>Read scoring and pass criteria carefully before starting.</li><li>Maintain professional conduct throughout the session.</li></ul>'),
            $this->section('Eligibility Instructions', '<ul><li>Confirm you meet the stated eligibility criteria for this certification.</li><li>Use only your official registration details.</li></ul>'),
            $this->section('Time Limits', '<ul><li>The official duration and section timings (if any) are binding.</li><li>Late submissions after expiry are not accepted.</li></ul>'),
            $this->section('Navigation Instructions', '<ul><li>Follow the exam flow defined for this certification.</li><li>Do not attempt to reopen reviewed sections if navigation is locked.</li></ul>'),
            $this->section('Marking Scheme', '<ul><li>Each item carries the marks shown in the interface.</li><li>Overall pass marks / percentage are defined by the certifying body.</li></ul>'),
            $this->section('Negative Marking Policy', '<ul><li>Where negative marking is part of the certification policy, incorrect answers may reduce your score.</li><li>Review each selection carefully before confirming.</li></ul>'),
            $this->section('Technical, Browser & Connectivity Requirements', '<ul><li>Use an approved browser on a reliable device.</li><li>Ensure uninterrupted power and internet for the full duration.</li><li>Disable VPN/proxy tools if instructed by the exam policy.</li></ul>'),
            $this->section('Prohibited Activities', '<ul><li>Any unfair practice may result in failure and disciplinary action.</li><li>Recording, copying, or redistributing certification content is strictly forbidden.</li></ul>'),
            $this->section('Submission Guidelines', '<ul><li>Complete all mandatory sections before final submit.</li><li>Retain your confirmation / receipt after successful submission.</li></ul>'),
            $this->section('Support & Contact', '<p>For certification logistics or technical issues, contact the designated exam office with your registration ID and exam title.</p>'),
        ]);
    }
}
