<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;

use App\Traits\HasAuditTrails;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Question extends Model
{
    use BelongsToOrganization, HasAuditTrails, HasFactory, SoftDeletes;

    protected $fillable = [
        // Relations & Audit
        'organization_id',
        'category_id',
        'import_question_id',
        'created_by',
        'updated_by',
        'updated_by_history',

        // Content
        'body',
        'title',
        'type',
        'allows_multiple',
        'options',
        'correct_answer',
        'correct_answers',
        'explanation',
        'reference',

        // Scoring & Classification
        'marks_type',
        'marks_list',
        'marks',
        'difficulty',
        'status',
        'is_public',
        'show_explanation_publicly',
        'view_count',
        'public_tags',

        // SEO / Metadata
        'meta_title',
        'meta_description',
        'meta_keywords',
        'slug',
        'canonical_url',
        'og_title',
        'og_description',
        'og_image_id',
        'robots',
        'schema_markup',

        // AI flags
        'ai_generated',
        'ai_improve',
    ];

    protected function casts(): array
    {
        return [
            'options' => 'array',
            'correct_answers' => 'array',
            'allows_multiple' => 'boolean',
            'updated_by_history' => 'array',
            'marks_list' => 'array',
            'ai_generated' => 'boolean',
            'ai_improve' => 'boolean',
            'is_public' => 'boolean',
            'show_explanation_publicly' => 'boolean',
            'view_count' => 'integer',
            'public_tags' => 'array',
        ];
    }

    // ── Relationships ─────────────────────────────────────────────────────────

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function category()
    {
        return $this->belongsTo(QuestionCategory::class, 'category_id');
    }

    public function importQuestion(): BelongsTo
    {
        return $this->belongsTo(ImportQuestion::class, 'import_question_id');
    }

    public function ogImage(): BelongsTo
    {
        return $this->belongsTo(Gallery::class, 'og_image_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function examParts()
    {
        return $this->belongsToMany(ExamPart::class, 'exam_part_question')
            ->withPivot(['sort_order', 'marks_override', 'status'])
            ->withTimestamps();
    }

    // ── Scopes ────────────────────────────────────────────────────────────────

    public function scopeByDifficulty($query, string $difficulty)
    {
        return $query->where('difficulty', $difficulty);
    }

    public function scopePubliclyVisible($query)
    {
        return $query->where('status', 'active')->where('is_public', true)->whereNotNull('slug');
    }

    public function publicTitle(): string
    {
        if (filled($this->title)) {
            return (string) $this->title;
        }

        $plain = trim(preg_replace('/\s+/', ' ', strip_tags((string) $this->body)));

        return $plain !== '' ? \Illuminate\Support\Str::limit($plain, 120, '') : 'Question #'.$this->id;
    }

    public function isChoiceType(): bool
    {
        $type = (string) $this->type;

        return in_array($type, ['mcq', 'multi_select', 'true_false'], true)
            || ($type === 'mcq' && (bool) $this->allows_multiple);
    }

    public function allowsMultipleAnswers(): bool
    {
        return (bool) $this->allows_multiple || in_array((string) $this->type, ['multi_select'], true);
    }

    public function typeLabel(): string
    {
        return match ((string) $this->type) {
            'mcq' => $this->allowsMultipleAnswers() ? 'Multiple select' : 'MCQ',
            'multi_select' => 'Multiple select',
            'true_false' => 'True / False',
            'fill_blank' => 'Fill in the blank',
            'short_answer' => 'Short answer',
            'long_answer' => 'Long answer',
            default => ucwords(str_replace('_', ' ', (string) $this->type)),
        };
    }

    public function difficultyLabel(): string
    {
        return ucfirst((string) ($this->difficulty ?: 'medium'));
    }

    /**
     * Candidate/public-safe payload without correct answers.
     *
     * @return array<string, mixed>
     */
    public function toPublicPayload(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->publicTitle(),
            'slug' => $this->slug,
            'body' => $this->body,
            'type' => $this->type,
            'difficulty' => $this->difficulty,
            'category' => $this->category ? [
                'id' => $this->category->id,
                'name' => $this->category->name,
                'slug' => $this->category->slug,
            ] : null,
            'tags' => $this->public_tags ?? [],
            'explanation' => $this->show_explanation_publicly ? $this->explanation : null,
            'reference' => $this->reference,
            'options' => collect($this->options ?? [])->map(function ($option, $index) {
                if (is_array($option)) {
                    return [
                        'key' => (string) ($option['key'] ?? $index),
                        'text' => $option['text'] ?? $option['label'] ?? '',
                    ];
                }

                return ['key' => (string) $index, 'text' => (string) $option];
            })->values()->all(),
        ];
    }

    /**
     * Public practice detail payload with revealed correct choices (for learning pages).
     *
     * @return array{show_options: bool, multiple: bool, options: list<array{key: string, letter: string, text: string, is_correct: bool}>, explanation: ?string}
     */
    public function toPracticeDetailPayload(): array
    {
        $type = (string) $this->type;
        $multiple = $this->allowsMultipleAnswers();
        $correctKeys = $this->normalizedCorrectKeys();
        $options = [];

        if ($type === 'true_false') {
            foreach (['True', 'False'] as $index => $label) {
                $options[] = [
                    'key' => $label,
                    'letter' => $index === 0 ? 'A' : 'B',
                    'text' => $label,
                    'is_correct' => in_array(mb_strtolower($label), $correctKeys, true),
                ];
            }
        } elseif (in_array($type, ['mcq', 'multi_select'], true) || ! empty($this->options)) {
            foreach (array_values($this->options ?? []) as $index => $option) {
                $text = is_array($option)
                    ? (string) ($option['text'] ?? $option['label'] ?? '')
                    : (string) $option;
                $key = is_array($option)
                    ? (string) ($option['key'] ?? $text)
                    : $text;
                $options[] = [
                    'key' => $key,
                    'letter' => chr(65 + min($index, 25)),
                    'text' => $text,
                    'is_correct' => $this->optionIsCorrect($key, $text, $correctKeys),
                ];
            }
        }

        $showOptions = $options !== [] && (
            in_array($type, ['mcq', 'multi_select', 'true_false'], true)
            || ! empty($this->options)
        );

        $explanation = null;
        if ($this->show_explanation_publicly && filled($this->explanation)) {
            $explanation = $this->explanation;
        }

        return [
            'show_options' => $showOptions,
            'multiple' => $multiple,
            'options' => $options,
            'explanation' => $explanation,
        ];
    }

    /**
     * @return list<string>
     */
    protected function normalizedCorrectKeys(): array
    {
        $values = [];
        if (is_array($this->correct_answers) && $this->correct_answers !== []) {
            $values = $this->correct_answers;
        } elseif (filled($this->correct_answer)) {
            $values = [$this->correct_answer];
        }

        return collect($values)
            ->map(fn ($value) => mb_strtolower(trim(strip_tags((string) $value))))
            ->filter()
            ->values()
            ->all();
    }

    /**
     * @param  list<string>  $correctKeys
     */
    protected function optionIsCorrect(string $key, string $text, array $correctKeys): bool
    {
        $candidates = [
            mb_strtolower(trim($key)),
            mb_strtolower(trim($text)),
            mb_strtolower(trim(strip_tags($text))),
        ];

        foreach ($candidates as $candidate) {
            if ($candidate !== '' && in_array($candidate, $correctKeys, true)) {
                return true;
            }
        }

        return false;
    }

    public function socialImageUrl(): ?string
    {
        return $this->ogImage?->file_url;
    }

    public function seoImageUrl(): string
    {
        return seo_image($this->socialImageUrl(), 'question');
    }
}
