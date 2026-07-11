<?php

namespace App\Support;

/**
 * Single source of truth for exam create/edit select options.
 * Values must stay aligned with StoreExamRequest / UpdateExamRequest rules.
 */
class ExamFormOptions
{
    public static function all(): array
    {
        return [
            'difficultyLevels' => self::difficultyLevels(),
            'examStatus' => self::examStatus(),
            'examModes' => self::examModes(),
            'visibilityOptions' => self::visibilityOptions(),
            'examFormats' => self::examFormats(),
            'discountRules' => self::discountRules(),
            'questionMarks' => self::questionMarks(),
            'pricingOptions' => self::pricingOptions(),
            'distributionTypes' => self::distributionTypes(),
            'instructionTemplates' => self::instructionTemplates(),
            'currencies' => self::currencies(),
        ];
    }

    public static function difficultyLevels(): array
    {
        return [
            ['id' => 'easy', 'label' => 'Easy', 'description' => 'Basic recall and simple application'],
            ['id' => 'medium', 'label' => 'Medium', 'description' => 'Scenario-based intermediate reasoning'],
            ['id' => 'hard', 'label' => 'Hard', 'description' => 'Advanced conceptual and applied challenges'],
        ];
    }

    public static function examStatus(): array
    {
        return [
            ['id' => 'draft', 'label' => 'Draft', 'description' => 'Visible only to administrators'],
            ['id' => 'published', 'label' => 'Published', 'description' => 'Released and ready for candidates'],
            ['id' => 'active', 'label' => 'Active', 'description' => 'Open for candidates'],
            ['id' => 'inactive', 'label' => 'Inactive', 'description' => 'Temporarily disabled'],
            ['id' => 'suspended', 'label' => 'Suspended', 'description' => 'Blocked pending review'],
        ];
    }

    public static function examModes(): array
    {
        return [
            ['id' => 'standard', 'label' => 'Standard', 'description' => 'Timed evaluation with standard rules'],
            ['id' => 'practice', 'label' => 'Practice', 'description' => 'Practice mode with no pricing section'],
            ['id' => 'proctored', 'label' => 'Proctored', 'description' => 'Strict supervision and compliance mode'],
        ];
    }

    public static function visibilityOptions(): array
    {
        return [
            ['id' => 'public', 'label' => 'Public', 'description' => 'Anyone can discover and attempt'],
            ['id' => 'private', 'label' => 'Private', 'description' => 'Invite-only with candidate access management'],
            ['id' => 'invite_only', 'label' => 'Invite Only', 'description' => 'Available only to invited candidates'],
        ];
    }

    public static function examFormats(): array
    {
        return [
            ['id' => 'mcq', 'label' => 'MCQ'],
            ['id' => 'written', 'label' => 'Written'],
            ['id' => 'multi_select', 'label' => 'Multi Select'],
        ];
    }

    public static function discountRules(): array
    {
        return [
            ['id' => 'first_time', 'label' => 'First Time User', 'summary' => 'Apply a discount to first exam purchase.', 'default_percentage' => 10],
            ['id' => 'returning', 'label' => 'Returning User', 'summary' => 'Reward repeat candidates with a discount.', 'default_percentage' => 20],
            ['id' => 'coupon_code', 'label' => 'Coupon Code Discount', 'summary' => 'Allow promo-code based reductions.', 'default_percentage' => 5],
            ['id' => 'referral', 'label' => 'Referral Discount', 'summary' => 'Discount when referred by existing candidates.', 'default_percentage' => 10],
        ];
    }

    public static function questionMarks(): array
    {
        return array_map(
            fn (int $value) => [
                'value' => $value,
                'label' => $value === 1 ? '1 mark' : "{$value} marks",
            ],
            range(1, 10)
        );
    }

    public static function pricingOptions(): array
    {
        return [
            ['id' => 'paid', 'label' => 'Paid', 'description' => 'Candidates pay before attempting'],
            ['id' => 'free', 'label' => 'Free', 'description' => 'Open access without payment'],
            ['id' => 'free_for_imported', 'label' => 'Free for Imported Candidates', 'description' => 'Only imported candidates get free access'],
        ];
    }

    public static function distributionTypes(): array
    {
        return [
            ['id' => 'mixed', 'label' => 'Mixed Questions', 'description' => 'Random mix from selected categories'],
            ['id' => 'category_wise', 'label' => 'Category-wise Questions', 'description' => 'Distribute questions per category strategy'],
        ];
    }

    public static function instructionTemplates(): array
    {
        return [
            [
                'id' => 'default_general',
                'label' => 'General Assessment Rules',
                'content' => '<ul><li>Read each question carefully before answering.</li><li>Use the provided time effectively and avoid leaving questions unanswered.</li><li>Review your answers before final submission.</li></ul>',
            ],
            [
                'id' => 'proctored_policy',
                'label' => 'Proctored Compliance Rules',
                'content' => '<ul><li>Keep camera enabled throughout the attempt.</li><li>Switching browser tabs may trigger warnings.</li><li>Any misconduct can lead to exam cancellation.</li></ul>',
            ],
            [
                'id' => 'coding_guidelines',
                'label' => 'Coding Round Guidelines',
                'content' => '<ul><li>Explain assumptions in concise comments.</li><li>Write clear and testable logic.</li><li>Prefer readability over unnecessary optimization.</li></ul>',
            ],
        ];
    }

    public static function currencies(): array
    {
        return [
            ['id' => 'USD', 'label' => 'USD ($)'],
            ['id' => 'EUR', 'label' => 'EUR (€)'],
            ['id' => 'GBP', 'label' => 'GBP (£)'],
            ['id' => 'INR', 'label' => 'INR (₹)'],
            ['id' => 'AUD', 'label' => 'AUD (A$)'],
            ['id' => 'CAD', 'label' => 'CAD (C$)'],
        ];
    }

    /** Simple id => label maps for Blade @foreach selects (edit form). */
    public static function statusLabels(): array
    {
        return collect(self::examStatus())->mapWithKeys(fn ($o) => [$o['id'] => $o['label']])->all();
    }

    public static function modeLabels(): array
    {
        return collect(self::examModes())->mapWithKeys(fn ($o) => [$o['id'] => $o['label']])->all();
    }

    public static function visibilityLabels(): array
    {
        return collect(self::visibilityOptions())->mapWithKeys(fn ($o) => [$o['id'] => $o['label']])->all();
    }

    public static function difficultyLabels(): array
    {
        return collect(self::difficultyLevels())->mapWithKeys(fn ($o) => [$o['id'] => $o['label']])->all();
    }

    public static function formatLabels(): array
    {
        return collect(self::examFormats())->mapWithKeys(fn ($o) => [$o['id'] => $o['label']])->all();
    }
}
