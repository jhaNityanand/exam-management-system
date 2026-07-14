<?php

namespace Database\Seeders;

use App\Models\Organization;
use App\Models\Question;
use App\Models\QuestionCategory;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Seeds realistic questions for every question category in demo-org.
 *
 * Each category receives 4–5 questions spanning MCQ, True/False, Short Answer,
 * Long Answer, and Fill in the Blanks, with mixed difficulties and marks.
 */
class QuestionSeeder extends Seeder
{
    public function run(): void
    {
        $org    = Organization::where('slug', 'demo-org')->first();
        $editor = User::where('email', 'editor@examms.test')->first();

        if (! $org || ! $editor) {
            $this->command->warn('QuestionSeeder: demo-org or editor user not found. Skipping.');

            return;
        }

        $categories = QuestionCategory::forOrg($org->id)->orderBy('id')->get();

        if ($categories->isEmpty()) {
            $this->command->warn('QuestionSeeder: no categories found. Run QuestionCategorySeeder first.');

            return;
        }

        $created = 0;

        foreach ($categories as $category) {
            foreach ($this->questionsFor($category) as $payload) {
                Question::updateOrCreate(
                    [
                        'organization_id' => $org->id,
                        'body'            => $payload['body'],
                    ],
                    array_merge($payload, [
                        'organization_id' => $org->id,
                        'category_id'     => $category->id,
                        'created_by'      => $editor->id,
                        'status'          => 'active',
                        'meta_title'      => Str::limit(strip_tags($payload['body']), 60),
                        'slug'            => Str::slug(Str::limit(strip_tags($payload['body']), 80, '')),
                        'ai_generated'    => false,
                        'ai_improve'      => false,
                    ])
                );
                $created++;
            }
        }

        $this->command->info("✓ QuestionSeeder: {$created} questions seeded across {$categories->count()} categories.");
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function questionsFor(QuestionCategory $category): array
    {
        $topic = $category->name;
        $hash  = abs(crc32((string) $category->id));
        $facts = $this->topicFacts($topic, $hash);

        return [
            $this->mcqQuestion($topic, $facts, $hash),
            $this->trueFalseQuestion($topic, $facts, $hash),
            $this->shortAnswerQuestion($topic, $facts, $hash),
            $this->fillBlankQuestion($topic, $facts, $hash),
            $this->longAnswerQuestion($topic, $facts, $hash),
        ];
    }

    /**
     * @return array{concept: string, related: string, distractor_a: string, distractor_b: string, distractor_c: string, blank: string, essay_prompt: string, explanation: string}
     */
    private function topicFacts(string $topic, int $hash): array
    {
        $pools = [
            'default' => [
                'concept'       => "core principles of {$topic}",
                'related'       => "practical applications in {$topic}",
                'distractor_a'  => 'unrelated historical trivia',
                'distractor_b'  => 'a contradictory definition',
                'distractor_c'  => 'an outdated methodology',
                'blank'         => $topic,
                'essay_prompt'  => "Explain the importance of {$topic} with suitable examples and evaluate common challenges.",
                'explanation'   => "This item checks foundational understanding of {$topic} and its correct application.",
            ],
            'math' => [
                'concept'       => 'algebraic manipulation and problem solving',
                'related'       => 'quantitative reasoning under exam conditions',
                'distractor_a'  => 'geometric construction only',
                'distractor_b'  => 'pure memorisation of formulas',
                'distractor_c'  => 'random trial-and-error',
                'blank'         => 'variable',
                'essay_prompt'  => "Derive and explain a standard method used in {$topic}, showing each step clearly.",
                'explanation'   => 'Correct answers rely on systematic mathematical reasoning rather than guesswork.',
            ],
            'science' => [
                'concept'       => 'cause-and-effect relationships in natural systems',
                'related'       => 'experimental observation and measurement',
                'distractor_a'  => 'pseudoscience without evidence',
                'distractor_b'  => 'a non-reproducible anecdote',
                'distractor_c'  => 'an unrelated technology trend',
                'blank'         => 'hypothesis',
                'essay_prompt'  => "Describe a key concept in {$topic}, outline how it is tested, and discuss real-world implications.",
                'explanation'   => 'Science questions reward precise terminology and evidence-based reasoning.',
            ],
            'cs' => [
                'concept'       => 'algorithms, data structures, and system design trade-offs',
                'related'       => 'scalable and maintainable software practices',
                'distractor_a'  => 'hard-coding every edge case',
                'distractor_b'  => 'ignoring complexity analysis',
                'distractor_c'  => 'skipping testing entirely',
                'blank'         => 'abstraction',
                'essay_prompt'  => "Compare two approaches relevant to {$topic} and justify which is preferable in production systems.",
                'explanation'   => 'Correct options balance correctness, performance, and maintainability.',
            ],
            'commerce' => [
                'concept'       => 'financial decision-making and organisational processes',
                'related'       => 'compliance, reporting, and market behaviour',
                'distractor_a'  => 'ignoring stakeholder impact',
                'distractor_b'  => 'treating cash and profit as identical',
                'distractor_c'  => 'skipping risk assessment',
                'blank'         => 'ledger',
                'essay_prompt'  => "Analyse how {$topic} influences business outcomes, citing frameworks and practical controls.",
                'explanation'   => 'Commerce items emphasise accuracy of definitions and applied judgement.',
            ],
            'humanities' => [
                'concept'       => 'critical interpretation of sources and contexts',
                'related'       => 'chronology, causation, and comparative analysis',
                'distractor_a'  => 'anachronistic interpretation',
                'distractor_b'  => 'unsupported generalisation',
                'distractor_c'  => 'purely speculative narrative',
                'blank'         => 'primary source',
                'essay_prompt'  => "Evaluate a major theme within {$topic}, supporting your argument with evidence and counterpoints.",
                'explanation'   => 'Strong answers use evidence, context, and balanced evaluation.',
            ],
            'aptitude' => [
                'concept'       => 'speed and accuracy under timed conditions',
                'related'       => 'pattern recognition and elimination techniques',
                'distractor_a'  => 'overcomplicating a simple ratio',
                'distractor_b'  => 'misreading units',
                'distractor_c'  => 'ignoring constraints in the stem',
                'blank'         => 'proportion',
                'essay_prompt'  => "Describe a reliable strategy for solving {$topic} problems quickly while minimising calculation errors.",
                'explanation'   => 'Aptitude scoring rewards methodical elimination and careful reading.',
            ],
        ];

        $domain = match (true) {
            (bool) preg_match('/math|algebra|geometry|calculus|statistic|trigonometr|number|discrete|linear/i', $topic) => 'math',
            (bool) preg_match('/physics|chemistry|biology|science|ecology|genetics|optics|mechanic|microbiology|botany|zoology|earth|thermo|electro/i', $topic) => 'science',
            (bool) preg_match('/computer|program|software|database|devops|cloud|network|cyber|ai|machine|frontend|backend|mobile|docker|kubernetes|sql|python|java|linux|security/i', $topic) => 'cs',
            (bool) preg_match('/commerce|account|finance|econom|market|tax|audit|banking|trade|business/i', $topic) => 'commerce',
            (bool) preg_match('/history|geography|political|english|literature|polity|culture|grammar|vocabulary/i', $topic) => 'humanities',
            (bool) preg_match('/aptitude|reasoning|general knowledge|current affairs|puzzle|coding decoding|blood relation/i', $topic) => 'aptitude',
            default => 'default',
        };

        $facts = $pools[$domain];

        // Light deterministic variation so sibling categories are not identical.
        if ($hash % 3 === 1) {
            $facts['related'] = "advanced problem sets in {$topic}";
        } elseif ($hash % 3 === 2) {
            $facts['related'] = "exam-oriented revision of {$topic}";
        }

        return $facts;
    }

    /**
     * @param  array<string, string>  $facts
     * @return array<string, mixed>
     */
    private function mcqQuestion(string $topic, array $facts, int $hash): array
    {
        $difficulties = ['easy', 'medium', 'hard', 'very_hard'];
        $difficulty   = $difficulties[$hash % 4];
        $marks        = match ($difficulty) {
            'easy' => 1,
            'medium' => 2,
            'hard' => 3,
            default => 5,
        };

        $correct = "Understanding {$facts['concept']}";

        return [
            'body'            => "<p>Which of the following best describes a central focus of <strong>{$topic}</strong>?</p>",
            'type'            => 'mcq',
                    'allows_multiple' => false,
            'options'         => [
                ['text' => $correct, 'image_path' => null],
                ['text' => ucfirst($facts['distractor_a']), 'image_path' => null],
                ['text' => ucfirst($facts['distractor_b']), 'image_path' => null],
                ['text' => ucfirst($facts['distractor_c']), 'image_path' => null],
            ],
            'correct_answer'  => $correct,
                    'correct_answers' => null,
            'marks_type'      => 'single',
            'marks_list'      => null,
            'marks'           => $marks,
            'difficulty'      => $difficulty,
            'explanation'     => "<p>{$facts['explanation']} The correct choice emphasises {$facts['concept']} rather than distractors.</p>",
            'reference'       => "{$topic} — Concept Check",
        ];
    }

    /**
     * @param  array<string, string>  $facts
     * @return array<string, mixed>
     */
    private function trueFalseQuestion(string $topic, array $facts, int $hash): array
    {
        $isTrue = ($hash % 2) === 0;
        $statement = $isTrue
            ? "{$topic} commonly involves {$facts['related']}."
            : "{$topic} never requires {$facts['concept']} in any practical setting.";

        return [
            'body'            => "<p>True or False: {$statement}</p>",
            'type'            => 'true_false',
                    'allows_multiple' => false,
            'options'         => null,
            'correct_answer'  => $isTrue ? 'True' : 'False',
                    'correct_answers' => null,
            'marks_type'      => 'single',
            'marks_list'      => null,
            'marks'           => 1,
            'difficulty'      => $isTrue ? 'easy' : 'medium',
            'explanation'     => $isTrue
                ? "<p>The statement correctly links {$topic} with {$facts['related']}.</p>"
                : "<p>The absolute claim is false because {$topic} does rely on {$facts['concept']} in many contexts.</p>",
            'reference'       => "{$topic} — True/False Drill",
        ];
    }

    /**
     * @param  array<string, string>  $facts
     * @return array<string, mixed>
     */
    private function shortAnswerQuestion(string $topic, array $facts, int $hash): array
    {
        return [
            'body'            => "<p>In one or two sentences, define <strong>{$topic}</strong> and state why it matters for learners.</p>",
            'type'            => 'short_answer',
                    'allows_multiple' => false,
            'options'         => null,
            'correct_answer'  => "<p>{$topic} focuses on {$facts['concept']}. It matters because mastery of {$facts['related']} improves exam performance and applied decision-making.</p>",
                    'correct_answers' => null,
            'marks_type'      => 'single',
            'marks_list'      => null,
            'marks'           => 3,
            'difficulty'      => ($hash % 2 === 0) ? 'medium' : 'easy',
            'explanation'     => "<p>Award marks for a clear definition plus at least one reason tied to {$facts['related']}.</p>",
            'reference'       => "{$topic} — Short Answer",
        ];
    }

    /**
     * @param  array<string, string>  $facts
     * @return array<string, mixed>
     */
    private function fillBlankQuestion(string $topic, array $facts, int $hash): array
    {
        $blank = $facts['blank'];

        return [
            'body'            => "<p>Fill in the blank: A key term frequently associated with <strong>{$topic}</strong> is ________.</p>",
            'type'            => 'fill_blank',
            'allows_multiple' => false,
            'options'         => null,
            'correct_answer'  => "<p>{$blank}</p>",
            'correct_answers' => null,
            'marks_type'      => 'single',
            'marks_list'      => null,
            'marks'           => 2,
            'difficulty'      => ($hash % 3 === 0) ? 'hard' : 'medium',
            'explanation'     => "<p>Accept \"{$blank}\" or a close synonym that correctly fits the {$topic} context.</p>",
            'reference'       => "{$topic} — Fill in the Blank",
        ];
    }

    /**
     * @param  array<string, string>  $facts
     * @return array<string, mixed>
     */
    private function longAnswerQuestion(string $topic, array $facts, int $hash): array
    {
        $difficulty = ($hash % 5 === 0) ? 'very_hard' : 'hard';

        return [
            'body'            => "<p>{$facts['essay_prompt']}</p>",
            'type'            => 'long_answer',
            'allows_multiple' => false,
            'options'         => null,
            'correct_answer'  => "<p>A strong response should: (1) define {$topic} accurately, (2) discuss {$facts['concept']}, (3) connect ideas to {$facts['related']}, and (4) conclude with limitations or best practices. Use examples and structured paragraphs.</p>",
            'correct_answers' => null,
            'marks_type'      => 'single',
            'marks_list'      => null,
            'marks'           => $difficulty === 'very_hard' ? 8 : 5,
            'difficulty'      => $difficulty,
            'explanation'     => "<p>Use a rubric covering definition, analysis, examples, and conclusion. {$facts['explanation']}</p>",
            'reference'       => "{$topic} — Descriptive / Essay",
        ];
    }
}
