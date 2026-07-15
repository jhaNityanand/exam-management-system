<?php

namespace App\Support;

use App\Models\ExamCandidateInstructionTemplate;
use App\Models\ExamInstructionRule;

/**
 * Single source of truth for exam create/edit select options.
 * Values must stay aligned with StoreExamRequest / UpdateExamRequest rules.
 */
class ExamFormOptions
{
    public static function all(?int $organizationId = null): array
    {
        return [
            'difficultyLevels' => self::difficultyLevels(),
            'examStatus' => self::examStatus(),
            'examModes' => self::examModes(),
            'visibilityOptions' => self::visibilityOptions(),
            'scheduleTypes' => self::scheduleTypes(),
            'attemptLimitTypes' => self::attemptLimitTypes(),
            'examFormats' => self::examFormats(),
            'discountRules' => self::discountRules(),
            'questionMarks' => self::questionMarks(),
            'pricingOptions' => self::pricingOptions(),
            'distributionTypes' => self::distributionTypes(),
            'instructionTemplates' => self::instructionTemplates($organizationId),
            'instructionRules' => self::instructionRules($organizationId),
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

    public static function scheduleTypes(): array
    {
        return [
            ['id' => 'any_time', 'label' => 'Any Time', 'description' => 'Candidates may start whenever the exam is available'],
            ['id' => 'fixed_window', 'label' => 'Fixed Window', 'description' => 'Only available between scheduled start and end'],
        ];
    }

    public static function attemptLimitTypes(): array
    {
        return [
            ['id' => 'once', 'label' => 'Once', 'description' => 'Single attempt only'],
            ['id' => 'fixed', 'label' => 'Fixed Count', 'description' => 'Limited to a maximum number of attempts'],
            ['id' => 'unlimited', 'label' => 'Unlimited', 'description' => 'No attempt limit'],
        ];
    }

    public static function examFormats(): array
    {
        return ExamFormats::all();
    }

    /** @return list<string> */
    public static function examFormatIds(): array
    {
        return ExamFormats::ids();
    }

    /**
     * @return array<string, list<array{type: string, allows_multiple: bool|null}>>
     */
    public static function examFormatQuestionConstraints(): array
    {
        return ExamFormats::questionConstraints();
    }

    public static function distributionTypes(): array
    {
        return [
            ['id' => 'mixed', 'label' => 'Mixed Questions', 'description' => 'Random mix from selected categories'],
            ['id' => 'category_wise', 'label' => 'Category-wise Questions', 'description' => 'Distribute questions per category strategy'],
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

    /**
     * Active candidate instruction templates for an organization.
     *
     * @return list<array{id: string, label: string, content: string, template_type: ?string, is_default: bool, version: ?string, icon: ?string}>
     */
    public static function instructionTemplates(?int $organizationId = null): array
    {
        $orgId = $organizationId ?? current_organization_id();
        if (! $orgId) {
            return [];
        }

        return ExamCandidateInstructionTemplate::query()
            ->forOrg((int) $orgId)
            ->active()
            ->ordered()
            ->get()
            ->map(fn (ExamCandidateInstructionTemplate $template) => $template->toFormOption())
            ->values()
            ->all();
    }

    /**
     * Active exam instruction/rules for an organization.
     *
     * @return list<array{id: string, label: string, description: string, category: ?string, icon: ?string, is_default: bool, is_required: bool}>
     */
    public static function instructionRules(?int $organizationId = null): array
    {
        $orgId = $organizationId ?? current_organization_id();
        if (! $orgId) {
            return [];
        }

        return ExamInstructionRule::query()
            ->forOrg((int) $orgId)
            ->active()
            ->ordered()
            ->get()
            ->map(fn (ExamInstructionRule $rule) => $rule->toFormOption())
            ->values()
            ->all();
    }

    /** @return list<string> */
    public static function instructionRuleIds(?int $organizationId = null): array
    {
        return collect(self::instructionRules($organizationId))->pluck('id')->all();
    }

    /** @return list<string> */
    public static function defaultInstructionRuleIds(?int $organizationId = null): array
    {
        return collect(self::instructionRules($organizationId))
            ->filter(fn (array $rule) => ! empty($rule['is_default']) || ! empty($rule['is_required']))
            ->pluck('id')
            ->values()
            ->all();
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

    public static function scheduleTypeLabels(): array
    {
        return collect(self::scheduleTypes())->mapWithKeys(fn ($o) => [$o['id'] => $o['label']])->all();
    }

    public static function attemptLimitLabels(): array
    {
        return collect(self::attemptLimitTypes())->mapWithKeys(fn ($o) => [$o['id'] => $o['label']])->all();
    }

    public static function formatLabels(): array
    {
        return ExamFormats::labels();
    }
}
