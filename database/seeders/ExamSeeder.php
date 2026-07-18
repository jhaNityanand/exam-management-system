<?php

namespace Database\Seeders;

use App\Models\Exam;
use App\Models\ExamCategory;
use App\Models\Organization;
use App\Models\Question;
use App\Models\QuestionCategory;
use App\Models\User;
use App\Support\UniqueOrgSlug;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Seeds a production-style interview assessment catalogue.
 *
 * Every record follows the same three question modes as the Exam Create form:
 * fixed (exact persisted set), pool (curated persisted pool), or dynamic
 * (criteria only, no exam_question rows).
 */
class ExamSeeder extends Seeder
{
    private Collection $questions;

    private Collection $questionCategories;

    private Collection $examCategories;

    public function run(): void
    {
        $organization = Organization::query()->where('slug', 'demo-org')->first();
        $admin = User::query()->where('email', 'orgadmin@examms.test')->first();

        if (! $organization || ! $admin) {
            $this->command?->warn('ExamSeeder: demo-org or orgadmin missing. Skipping.');

            return;
        }

        $this->questionCategories = QuestionCategory::query()
            ->forOrg($organization->id)
            ->where('status', 'active')
            ->get()
            ->keyBy('slug');
        $this->examCategories = ExamCategory::query()
            ->forOrg($organization->id)
            ->where('status', 'active')
            ->get()
            ->keyBy('slug');
        $this->questions = Question::query()
            ->where('organization_id', $organization->id)
            ->where('status', 'active')
            ->with('category:id,slug')
            ->orderBy('id')
            ->get();

        if ($this->questionCategories->isEmpty() || $this->examCategories->isEmpty() || $this->questions->isEmpty()) {
            $this->command?->warn('ExamSeeder: categories or questions missing. Run category/question seeders first.');

            return;
        }

        DB::transaction(function () use ($organization, $admin) {
            Exam::query()
                ->withTrashed()
                ->where('organization_id', $organization->id)
                ->forceDelete();

            foreach ($this->scenarios() as $index => $scenario) {
                $this->createExam(
                    $scenario,
                    $organization->id,
                    $admin->id,
                    $index + 1
                );
            }
        });

        $count = Exam::query()->where('organization_id', $organization->id)->count();
        $this->command?->info("ExamSeeder: {$count} production interview exams seeded.");
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function scenarios(): array
    {
        $allFormats = ['mcq', 'multi_select', 'true_false', 'written', 'fill_blank'];
        $technical = ['programming-fundamentals', 'data-structures', 'database'];
        $backend = ['php', 'laravel', 'database'];
        $frontend = ['javascript', 'html-css', 'programming-fundamentals'];
        $bca = ['bca', 'programming-fundamentals', 'database', 'aptitude'];
        $mca = ['mca', 'data-structures', 'database', 'programming-fundamentals'];

        return [
            [
                'title' => 'Primary Interview',
                'exam_category' => 'primary-interview',
                'question_categories' => ['interview-preparation', 'aptitude', 'programming-fundamentals', 'database'],
                'selection_mode' => 'dynamic',
                'total_questions' => 30,
                'total_marks' => 50,
                'passing_marks' => 30,
                'formats' => $allFormats,
                'difficulty' => 'medium',
                'visibility' => 'private',
                'manual_candidate_emails' => ['candidate.one@example.com', 'candidate.two@example.com'],
                'tags' => ['primary interview', 'full assessment', 'hiring'],
                'instructions' => '<p>Complete every section independently. Explain your reasoning for written responses and submit before the timer expires.</p>',
            ],
            [
                'title' => 'Primary Interview — Graduate Software Engineer',
                'exam_category' => 'primary-cs-fundamentals',
                'question_categories' => $technical,
                'selection_mode' => 'fixed',
                'total_questions' => 24,
                'total_marks' => 40,
                'passing_marks' => 24,
                'formats' => ['mcq', 'true_false', 'written'],
                'difficulty' => 'medium',
                'visibility' => 'invite_only',
                'shuffle_questions' => false,
                'shuffle_options' => true,
                'fixed_paper_set' => true,
                'paper_sets' => 2,
            ],
            [
                'title' => 'Laravel Developer Primary Interview',
                'exam_category' => 'role-laravel-developer',
                'question_categories' => ['laravel', 'php', 'database'],
                'selection_mode' => 'pool',
                'total_questions' => 25,
                'pool_size' => 45,
                'total_marks' => 45,
                'passing_marks' => 30,
                'formats' => ['mcq', 'multi_select', 'true_false'],
                'difficulty' => 'hard',
                'visibility' => 'private',
                'exam_mode' => 'proctored',
                'negative_marking_type' => '25',
                'shuffle_questions' => true,
                'shuffle_categories' => true,
                'shuffle_options' => true,
            ],
            [
                'title' => 'PHP Backend Technical Screening',
                'exam_category' => 'role-php-developer',
                'question_categories' => ['php', 'database'],
                'selection_mode' => 'fixed',
                'total_questions' => 20,
                'total_marks' => 32,
                'passing_marks' => 20,
                'formats' => ['mcq', 'multi_select'],
                'difficulty' => 'medium',
                'visibility' => 'public',
                'duration' => 45,
                'fixed_paper_set' => true,
                'paper_sets' => 4,
            ],
            [
                'title' => 'Frontend JavaScript Interview Assessment',
                'exam_category' => 'primary-frontend-engineering',
                'question_categories' => $frontend,
                'selection_mode' => 'pool',
                'total_questions' => 24,
                'pool_size' => 40,
                'total_marks' => 40,
                'passing_marks' => 24,
                'formats' => ['mcq', 'multi_select', 'true_false'],
                'difficulty' => 'medium',
                'visibility' => 'public',
                'shuffle_questions' => true,
                'shuffle_options' => true,
            ],
            [
                'title' => 'Full Stack Engineering Interview',
                'exam_category' => 'primary-full-stack-engineering',
                'question_categories' => ['laravel', 'php', 'javascript', 'html-css', 'database'],
                'selection_mode' => 'dynamic',
                'total_questions' => 35,
                'total_marks' => 60,
                'passing_marks' => 39,
                'formats' => $allFormats,
                'difficulty' => null,
                'visibility' => 'invite_only',
                'duration' => 105,
                'attempt_limit_type' => 'fixed',
                'max_attempts' => 2,
                'shuffle_questions' => true,
                'shuffle_categories' => true,
                'negative_marking_type' => '33.33',
            ],
            [
                'title' => 'SQL and Database Interview Round',
                'exam_category' => 'round-sql-database',
                'question_categories' => ['database', 'mca'],
                'selection_mode' => 'fixed',
                'total_questions' => 22,
                'total_marks' => 38,
                'passing_marks' => 25,
                'formats' => ['mcq', 'multi_select', 'written', 'fill_blank'],
                'difficulty' => 'hard',
                'visibility' => 'private',
                'negative_marking_type' => '25',
            ],
            [
                'title' => 'Data Structures Interview Round',
                'exam_category' => 'round-data-structures',
                'question_categories' => ['data-structures', 'programming-fundamentals'],
                'selection_mode' => 'dynamic',
                'total_questions' => 24,
                'total_marks' => 40,
                'passing_marks' => 26,
                'formats' => ['mcq', 'multi_select', 'true_false', 'written'],
                'difficulty' => null,
                'visibility' => 'public',
                'fix_category_questions' => true,
                'category_question_allocations' => [12, 12],
                'distribution_type' => 'category_wise',
                'shuffle_categories' => true,
            ],
            [
                'title' => 'Programming Fundamentals Screening',
                'exam_category' => 'round-coding-fundamentals',
                'question_categories' => ['programming-fundamentals', 'bca'],
                'selection_mode' => 'pool',
                'total_questions' => 20,
                'pool_size' => 34,
                'total_marks' => 30,
                'passing_marks' => 18,
                'formats' => ['mcq', 'multi_select', 'true_false'],
                'difficulty' => 'easy',
                'visibility' => 'public',
                'duration' => 40,
            ],
            [
                'title' => 'Aptitude and Reasoning Pre-Screen',
                'exam_category' => 'round-aptitude-reasoning',
                'question_categories' => ['aptitude', 'general-knowledge'],
                'selection_mode' => 'dynamic',
                'total_questions' => 25,
                'total_marks' => 35,
                'passing_marks' => 18,
                'formats' => ['mcq', 'true_false', 'fill_blank'],
                'difficulty' => null,
                'visibility' => 'public',
                'duration' => 35,
                'fix_marks_each_question' => true,
                'question_marks_filter' => [1, 2],
                'shuffle_questions' => true,
            ],
            [
                'title' => 'BCA Campus Placement Interview',
                'exam_category' => 'campus-bca-graduate',
                'question_categories' => $bca,
                'selection_mode' => 'fixed',
                'total_questions' => 28,
                'total_marks' => 45,
                'passing_marks' => 27,
                'formats' => $allFormats,
                'difficulty' => 'medium',
                'visibility' => 'invite_only',
                'fixed_paper_set' => true,
                'paper_sets' => 3,
                'shuffle_categories' => true,
            ],
            [
                'title' => 'MCA Graduate Technical Interview',
                'exam_category' => 'campus-mca-graduate',
                'question_categories' => $mca,
                'selection_mode' => 'pool',
                'total_questions' => 30,
                'pool_size' => 50,
                'total_marks' => 55,
                'passing_marks' => 36,
                'formats' => $allFormats,
                'difficulty' => 'hard',
                'visibility' => 'private',
                'duration' => 90,
                'attempt_limit_type' => 'fixed',
                'max_attempts' => 2,
                'negative_marking_type' => '50',
                'shuffle_questions' => true,
                'shuffle_options' => true,
            ],
            [
                'title' => 'Campus Technical Round — Balanced Sections',
                'exam_category' => 'campus-technical-round',
                'question_categories' => ['programming-fundamentals', 'database'],
                'selection_mode' => 'dynamic',
                'total_questions' => 30,
                'total_marks' => 30,
                'passing_marks' => 18,
                'formats' => ['mcq', 'multi_select', 'true_false'],
                'difficulty' => null,
                'visibility' => 'invite_only',
                'fix_category_marks' => true,
                'category_marks_allocations' => [15, 15],
                'distribution_type' => 'category_wise',
            ],
            [
                'title' => 'Behavioral and HR Interview Assessment',
                'exam_category' => 'primary-behavioral-interview',
                'question_categories' => ['interview-preparation', 'general-knowledge'],
                'selection_mode' => 'fixed',
                'total_questions' => 20,
                'total_marks' => 32,
                'passing_marks' => 19,
                'formats' => $allFormats,
                'difficulty' => 'medium',
                'visibility' => 'private',
                'enable_exam_timer' => false,
                'auto_submit_on_timer_end' => false,
                'duration' => 60,
            ],
            [
                'title' => 'Communication and Interview Readiness Pool',
                'exam_category' => 'round-communication-hr',
                'question_categories' => ['interview-preparation', 'general-knowledge'],
                'selection_mode' => 'pool',
                'total_questions' => 20,
                'pool_size' => 35,
                'total_marks' => 30,
                'passing_marks' => 18,
                'formats' => ['mcq', 'multi_select', 'written'],
                'difficulty' => 'easy',
                'visibility' => 'public',
                'exam_mode' => 'practice',
                'attempt_limit_type' => 'unlimited',
                'max_attempts' => 0,
                'enable_exam_timer' => false,
                'auto_submit_on_timer_end' => false,
            ],
            [
                'title' => 'Remote Proctored Senior Backend Interview',
                'exam_category' => 'proctored-remote-interview',
                'question_categories' => $backend,
                'selection_mode' => 'dynamic',
                'total_questions' => 30,
                'total_marks' => 55,
                'passing_marks' => 39,
                'formats' => $allFormats,
                'difficulty' => null,
                'visibility' => 'invite_only',
                'exam_mode' => 'proctored',
                'attempt_limit_type' => 'once',
                'negative_marking_type' => '25',
                'shuffle_questions' => true,
                'shuffle_categories' => true,
                'shuffle_options' => true,
                'instruction_rules' => ['fullscreen_required', 'webcam_monitoring_enabled', 'id_verification_required', 'tab_switch_autosubmit', 'no_screenshots'],
            ],
            [
                'title' => 'Rapid 20-Minute Developer Screening',
                'exam_category' => 'primary-initial-screening',
                'question_categories' => ['programming-fundamentals', 'aptitude'],
                'selection_mode' => 'fixed',
                'total_questions' => 20,
                'total_marks' => 25,
                'passing_marks' => 15,
                'formats' => ['mcq', 'true_false'],
                'difficulty' => 'easy',
                'visibility' => 'public',
                'duration' => 20,
                'question_marks_filter' => [1, 2],
                'shuffle_questions' => true,
            ],
            [
                'title' => 'Invite-Only Final Engineering Panel',
                'exam_category' => 'primary-final-panel',
                'question_categories' => ['interview-preparation', 'programming-fundamentals', 'data-structures', 'database'],
                'selection_mode' => 'pool',
                'total_questions' => 25,
                'pool_size' => 48,
                'total_marks' => 50,
                'passing_marks' => 35,
                'formats' => $allFormats,
                'difficulty' => 'hard',
                'visibility' => 'invite_only',
                'manual_candidate_emails' => ['finalist.alpha@example.com', 'finalist.beta@example.com', 'finalist.gamma@example.com'],
                'shuffle_questions' => true,
                'shuffle_categories' => true,
            ],
            [
                'title' => 'Paid Full Stack Skill Certification',
                'exam_category' => 'proctored-certification',
                'question_categories' => ['php', 'laravel', 'javascript', 'html-css', 'database'],
                'selection_mode' => 'dynamic',
                'total_questions' => 40,
                'total_marks' => 75,
                'passing_marks' => 53,
                'formats' => $allFormats,
                'difficulty' => null,
                'visibility' => 'public',
                'exam_mode' => 'proctored',
                'pricing_option' => 'paid',
                'exam_currency' => 'USD',
                'exam_amount' => 49.00,
                'selected_discounts' => [
                    ['id' => 'first_time', 'percentage' => 10],
                    ['id' => 'referral', 'percentage' => 15],
                ],
                'custom_discounts' => [
                    ['name' => 'Campus Partner', 'percentage' => 20],
                ],
                'negative_marking_type' => '25',
                'attempt_limit_type' => 'fixed',
                'max_attempts' => 2,
            ],
            [
                'title' => 'Imported Candidates Backend Evaluation',
                'exam_category' => 'primary-backend-engineering',
                'question_categories' => $backend,
                'selection_mode' => 'fixed',
                'total_questions' => 25,
                'total_marks' => 45,
                'passing_marks' => 27,
                'formats' => $allFormats,
                'difficulty' => 'medium',
                'visibility' => 'private',
                'pricing_option' => 'free_for_imported',
                'imported_candidates' => [
                    ['name' => 'Aarav Sharma', 'email' => 'aarav.sharma@example.com'],
                    ['name' => 'Meera Patel', 'email' => 'meera.patel@example.com'],
                    ['name' => 'Daniel Thomas', 'email' => 'daniel.thomas@example.com'],
                ],
                'free_imported_candidates' => [
                    ['name' => 'Aarav Sharma', 'email' => 'aarav.sharma@example.com'],
                    ['name' => 'Meera Patel', 'email' => 'meera.patel@example.com'],
                ],
            ],
            [
                'title' => 'Scheduled Campus Hiring Drive',
                'exam_category' => 'campus-graduate-hiring',
                'question_categories' => ['aptitude', 'programming-fundamentals', 'database', 'general-knowledge'],
                'selection_mode' => 'pool',
                'total_questions' => 30,
                'pool_size' => 52,
                'total_marks' => 50,
                'passing_marks' => 30,
                'formats' => ['mcq', 'multi_select', 'true_false'],
                'difficulty' => 'medium',
                'visibility' => 'invite_only',
                'schedule_type' => 'fixed_window',
                'scheduled_start' => now()->addDays(7)->setTime(9, 0),
                'scheduled_end' => now()->addDays(8)->setTime(18, 0),
                'fixed_paper_set' => true,
                'paper_sets' => 5,
                'shuffle_questions' => true,
                'shuffle_categories' => true,
            ],
            [
                'title' => 'Unlimited Technical Mock Interview',
                'exam_category' => 'practice-technical-mock',
                'question_categories' => $technical,
                'selection_mode' => 'dynamic',
                'total_questions' => 25,
                'total_marks' => 40,
                'passing_marks' => 20,
                'formats' => $allFormats,
                'difficulty' => 'medium',
                'visibility' => 'public',
                'exam_mode' => 'practice',
                'attempt_limit_type' => 'unlimited',
                'max_attempts' => 0,
                'status' => 'active',
                'shuffle_questions' => true,
            ],
            [
                'title' => 'Multi-Select Engineering Knowledge Check',
                'exam_category' => 'proctored-internal-verification',
                'question_categories' => ['php', 'laravel', 'javascript', 'database', 'data-structures'],
                'selection_mode' => 'fixed',
                'total_questions' => 20,
                'total_marks' => 45,
                'passing_marks' => 32,
                'formats' => ['multi_select'],
                'difficulty' => 'hard',
                'visibility' => 'private',
                'exam_mode' => 'proctored',
                'status' => 'inactive',
                'negative_marking_type' => '100',
                'shuffle_options' => true,
            ],
            [
                'title' => 'True or False Technical Speed Round',
                'exam_category' => 'specialized-assessment-rounds',
                'question_categories' => ['php', 'laravel', 'javascript', 'database', 'html-css', 'data-structures', 'programming-fundamentals', 'bca', 'mca', 'aptitude'],
                'selection_mode' => 'fixed',
                'total_questions' => 20,
                'total_marks' => 40,
                'passing_marks' => 24,
                'formats' => ['true_false'],
                'difficulty' => 'medium',
                'visibility' => 'public',
                'status' => 'suspended',
                'duration' => 25,
                'fixed_paper_set' => true,
                'paper_sets' => 2,
            ],
            [
                'title' => 'Written Technical Reasoning Assessment',
                'exam_category' => 'primary-managerial-interview',
                'question_categories' => ['interview-preparation', 'programming-fundamentals', 'data-structures', 'database', 'php', 'laravel', 'javascript', 'mca'],
                'selection_mode' => 'dynamic',
                'total_questions' => 20,
                'total_marks' => 80,
                'passing_marks' => 52,
                'formats' => ['written'],
                'difficulty' => null,
                'visibility' => 'invite_only',
                'status' => 'draft',
                'duration' => 120,
                'enable_exam_timer' => true,
                'auto_submit_on_timer_end' => false,
                'question_marks_filter' => [2, 3, 5, 7, 8],
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $scenario
     */
    private function createExam(array $scenario, int $organizationId, int $adminId, int $position): void
    {
        $questionCategoryIds = collect($scenario['question_categories'])
            ->map(function (string $slug) {
                $category = $this->questionCategories->get($slug);
                if (! $category) {
                    throw new RuntimeException("ExamSeeder: question category [{$slug}] not found.");
                }

                return (int) $category->id;
            })
            ->values()
            ->all();

        $examCategory = $this->examCategories->get($scenario['exam_category']);
        if (! $examCategory) {
            throw new RuntimeException("ExamSeeder: exam category [{$scenario['exam_category']}] not found.");
        }

        $mode = $scenario['selection_mode'];
        $totalQuestions = (int) $scenario['total_questions'];
        $poolSize = $mode === 'pool' ? (int) ($scenario['pool_size'] ?? 0) : null;
        $questionIds = [];

        if ($mode === 'fixed') {
            $questionIds = $this->selectQuestionIds($scenario, $totalQuestions);
        } elseif ($mode === 'pool') {
            if ($poolSize <= $totalQuestions) {
                throw new RuntimeException("ExamSeeder: pool must exceed total questions for [{$scenario['title']}].");
            }
            $questionIds = $this->selectQuestionIds($scenario, $poolSize);
        } elseif ($mode !== 'dynamic') {
            throw new RuntimeException("ExamSeeder: unsupported selection mode [{$mode}].");
        }

        $extraQuestionAllocations = $this->allocationMap(
            $questionCategoryIds,
            $scenario['category_question_allocations'] ?? []
        );
        $extraMarksAllocations = $this->allocationMap(
            $questionCategoryIds,
            $scenario['category_marks_allocations'] ?? []
        );

        $defaults = [
            'status' => 'published',
            'exam_mode' => 'standard',
            'visibility' => 'public',
            'difficulty' => 'medium',
            'duration' => 60,
            'enable_exam_timer' => true,
            'auto_submit_on_timer_end' => true,
            'schedule_type' => 'any_time',
            'scheduled_start' => null,
            'scheduled_end' => null,
            'attempt_limit_type' => 'once',
            'max_attempts' => 1,
            'pricing_option' => 'free',
            'exam_currency' => null,
            'exam_amount' => null,
            'selected_discounts' => [],
            'custom_discounts' => [],
            'fixed_paper_set' => false,
            'paper_sets' => 1,
            'fix_category_questions' => false,
            'fix_category_marks' => false,
            'distribution_type' => 'mixed',
            'fix_marks_each_question' => false,
            'question_marks_filter' => [1, 2, 3],
            'shuffle_questions' => false,
            'shuffle_categories' => false,
            'shuffle_options' => false,
            'negative_marking_type' => null,
            'manual_candidate_emails' => [],
            'imported_candidates' => [],
            'free_imported_candidates' => [],
            'free_manual_candidate_emails' => [],
            'tags' => ['interview', 'assessment'],
            'instruction_rules' => [
                'do-not-use-unfair-means-during-the-examination',
                'the-exam-will-automatically-end-when-the-allotted-time-expires',
                'no_revert_after_submit',
            ],
            'instructions' => '<p>Read each question carefully. Use only permitted resources and submit the assessment before the allotted time ends.</p>',
        ];
        $config = array_merge($defaults, $scenario);

        $negativeType = $config['negative_marking_type'];
        $negativePerQuestion = match ((string) $negativeType) {
            '25' => 0.25,
            '33.33' => 0.3333,
            '50' => 0.50,
            '100' => 1.00,
            default => 0,
        };
        $slug = UniqueOrgSlug::forModel(Exam::class, $scenario['title'], $organizationId);
        $passPercentage = round(((int) $scenario['passing_marks'] / (int) $scenario['total_marks']) * 100, 2);

        $exam = Exam::query()->create([
            'organization_id' => $organizationId,
            'category_id' => $examCategory->id,
            'created_by' => $adminId,
            'updated_by' => $adminId,
            'title' => $scenario['title'],
            'description' => '<p>'.$this->descriptionFor($scenario).'</p>',
            'status' => $config['status'],
            'exam_mode' => $config['exam_mode'],
            'exam_format' => $scenario['formats'],
            'difficulty_level' => $config['difficulty'],
            'visibility' => $config['visibility'],
            'tags' => $config['tags'],
            'pricing_option' => $config['pricing_option'],
            'exam_currency' => $config['exam_currency'],
            'exam_amount' => $config['exam_amount'],
            'selected_discounts' => $config['selected_discounts'],
            'custom_discounts' => $config['custom_discounts'],
            'duration' => $config['duration'],
            'enable_exam_timer' => (bool) $config['enable_exam_timer'],
            'auto_submit_on_timer_end' => (bool) $config['auto_submit_on_timer_end'],
            'schedule_type' => $config['schedule_type'],
            'scheduled_start' => $config['scheduled_start'],
            'scheduled_end' => $config['scheduled_end'],
            'attempt_limit_type' => $config['attempt_limit_type'],
            'max_attempts' => (int) $config['max_attempts'],
            'pass_percentage' => $passPercentage,
            'total_marks' => (int) $scenario['total_marks'],
            'passing_marks' => (int) $scenario['passing_marks'],
            'enable_negative_marking' => $negativeType !== null,
            'negative_marking_type' => $negativeType,
            'negative_mark_per_question' => $negativePerQuestion,
            'fix_marks_each_question' => (bool) $config['fix_marks_each_question'],
            'total_questions' => $totalQuestions,
            'use_question_pool' => $mode === 'pool',
            'maximum_questions' => $poolSize,
            'fixed_questions' => $mode === 'fixed',
            'fixed_paper_set' => (bool) $config['fixed_paper_set'],
            'paper_sets' => (bool) $config['fixed_paper_set'] ? (int) $config['paper_sets'] : 1,
            'fix_category_questions' => (bool) $config['fix_category_questions'],
            'fix_category_marks' => (bool) $config['fix_category_marks'],
            'distribution_type' => $config['distribution_type'],
            'selected_categories' => $questionCategoryIds,
            'extra_questions_categories' => (bool) $config['fix_category_questions'] ? $questionCategoryIds : [],
            'extra_questions_allocations' => $extraQuestionAllocations,
            'extra_marks_allocations' => $extraMarksAllocations,
            'question_marks_filter' => $config['question_marks_filter'],
            'category_question_rules' => [],
            'shuffle_questions' => (bool) $config['shuffle_questions'],
            'shuffle_categories' => (bool) $config['shuffle_categories'],
            'shuffle_options' => (bool) $config['shuffle_options'],
            'imported_candidates' => $config['imported_candidates'],
            'manual_candidate_emails' => $config['manual_candidate_emails'],
            'free_imported_candidates' => $config['free_imported_candidates'],
            'free_manual_candidate_emails' => $config['free_manual_candidate_emails'],
            'instructions' => $config['instructions'],
            'predefined_instruction_rules' => $config['instruction_rules'],
            'slug' => $slug,
            'meta_title' => Str::limit($scenario['title'].' | Interview Assessment', 255, ''),
            'meta_description' => Str::limit($this->descriptionFor($scenario), 500, ''),
            'meta_keywords' => implode(', ', array_unique(array_merge(
                ['interview assessment', 'candidate screening'],
                $config['tags']
            ))),
            'canonical_url' => rtrim((string) config('app.url'), '/').'/exams/'.$slug,
            'og_title' => $scenario['title'],
            'og_description' => Str::limit($this->descriptionFor($scenario), 500, ''),
            'ai_generated' => false,
            'ai_improve' => false,
        ]);

        $exam->selectedQuestionCategories()->sync($questionCategoryIds);

        $questionSync = [];
        foreach ($questionIds as $index => $questionId) {
            $questionSync[$questionId] = [
                'sort_order' => $index,
                'status' => 'active',
                'created_by' => $adminId,
                'updated_by' => $adminId,
            ];
        }
        $exam->questions()->sync($questionSync);

        $this->assertExamContract($exam, $mode, count($questionIds), $position);
    }

    /**
     * @param  array<string, mixed>  $scenario
     * @return list<int>
     */
    private function selectQuestionIds(array $scenario, int $required): array
    {
        $categorySlugs = $scenario['question_categories'];
        $formats = $scenario['formats'];
        $marks = $scenario['question_marks_filter'] ?? [1, 2, 3];

        $matches = $this->questions
            ->filter(fn (Question $question) => in_array($question->category?->slug, $categorySlugs, true))
            ->filter(fn (Question $question) => in_array((int) $question->marks, $marks, true))
            ->filter(fn (Question $question) => $this->questionMatchesFormats($question, $formats))
            ->values();

        if ($matches->count() < $required) {
            throw new RuntimeException(
                "ExamSeeder: [{$scenario['title']}] requires {$required} questions, ".
                "but only {$matches->count()} match its categories/formats/marks."
            );
        }

        return $matches->take($required)->pluck('id')->map(fn ($id) => (int) $id)->all();
    }

    /**
     * @param  list<string>  $formats
     */
    private function questionMatchesFormats(Question $question, array $formats): bool
    {
        foreach ($formats as $format) {
            if ($format === 'mcq' && $question->type === 'mcq' && ! $question->allows_multiple) {
                return true;
            }
            if ($format === 'multi_select' && $question->type === 'mcq' && $question->allows_multiple) {
                return true;
            }
            if ($format === 'true_false' && $question->type === 'true_false') {
                return true;
            }
            if ($format === 'written' && in_array($question->type, ['short_answer', 'long_answer'], true)) {
                return true;
            }
            if ($format === 'fill_blank' && $question->type === 'fill_blank') {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  list<int>  $categoryIds
     * @param  list<int>  $values
     * @return array<int, int>
     */
    private function allocationMap(array $categoryIds, array $values): array
    {
        if ($values === []) {
            return [];
        }
        if (count($categoryIds) !== count($values)) {
            throw new RuntimeException('ExamSeeder: allocation count must match selected question categories.');
        }

        return collect($categoryIds)
            ->mapWithKeys(fn (int $categoryId, int $index) => [$categoryId => (int) $values[$index]])
            ->all();
    }

    /**
     * @param  array<string, mixed>  $scenario
     */
    private function descriptionFor(array $scenario): string
    {
        return sprintf(
            '%s is a %s-level %s workflow with %d questions across %d question categories. It is configured for realistic candidate screening, scoring, and reporting.',
            $scenario['title'],
            $scenario['difficulty'] ?? 'medium',
            str_replace('_', ' ', $scenario['selection_mode']),
            (int) $scenario['total_questions'],
            count($scenario['question_categories'])
        );
    }

    private function assertExamContract(Exam $exam, string $mode, int $persistedCount, int $position): void
    {
        if ($exam->passing_marks > $exam->total_marks) {
            throw new RuntimeException("ExamSeeder: passing marks exceed total marks at scenario {$position}.");
        }
        if (empty($exam->selected_categories) || empty($exam->question_marks_filter)) {
            throw new RuntimeException("ExamSeeder: categories/marks missing at scenario {$position}.");
        }
        if ($mode === 'fixed' && $persistedCount !== (int) $exam->total_questions) {
            throw new RuntimeException("ExamSeeder: fixed question count mismatch at scenario {$position}.");
        }
        if ($mode === 'pool' && (
            $persistedCount < (int) $exam->total_questions
            || $persistedCount > (int) $exam->maximum_questions
        )) {
            throw new RuntimeException("ExamSeeder: question pool count mismatch at scenario {$position}.");
        }
        if ($mode === 'dynamic' && $persistedCount !== 0) {
            throw new RuntimeException("ExamSeeder: dynamic scenario {$position} persisted question ids.");
        }
        if ($exam->fix_category_questions) {
            $allocated = collect($exam->extra_questions_allocations)->sum();
            if ($allocated !== (int) $exam->total_questions) {
                throw new RuntimeException("ExamSeeder: question allocation mismatch at scenario {$position}.");
            }
        }
        if ($exam->fix_category_marks) {
            $allocated = collect($exam->extra_marks_allocations)->sum();
            if ($allocated !== (int) $exam->total_marks) {
                throw new RuntimeException("ExamSeeder: marks allocation mismatch at scenario {$position}.");
            }
        }
    }
}
