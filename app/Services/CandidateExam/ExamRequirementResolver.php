<?php

namespace App\Services\CandidateExam;

use App\Models\Exam;
use App\Models\ExamInstructionRule;
use App\Models\ExamProctoringPolicy;
use Illuminate\Support\Collection;

class ExamRequirementResolver
{
    /**
     * @return array<string, mixed>
     */
    public function defaults(): array
    {
        return [
            'require_webcam' => false,
            'require_microphone' => false,
            'require_fullscreen' => false,
            'require_photo_verification' => false,
            'require_identity_verification' => false,
            'block_copy_paste' => false,
            'block_context_menu' => false,
            'detect_devtools' => false,
            'block_page_refresh' => false,
            'enforce_single_session' => false,
            'single_attempt_per_question' => false,
            'detect_tab_switch' => false,
            'focus_violation_limit' => 3,
            'focus_violation_action' => 'warn',
            'auto_submit_on_violation' => false,
            'enabled_rule_keys' => [],
            'meta' => [],
        ];
    }

    /**
     * Resolve selected instruction rule keys into a flat policy array.
     *
     * @param  list<string>|null  $selectedKeys
     * @return array<string, mixed>
     */
    public function resolve(Exam $exam, ?array $selectedKeys = null): array
    {
        $policy = $this->defaults();
        $selectedKeys = $selectedKeys ?? array_values(array_filter(
            array_map('strval', $exam->predefined_instruction_rules ?? [])
        ));

        $rules = $this->rulesForExam($exam, $selectedKeys);
        $enabledKeys = [];

        foreach ($rules as $rule) {
            $key = (string) ($rule->rule_key ?: $rule->slug);
            $enabledKeys[] = $key;
            $requirements = is_array($rule->requirements) ? $rule->requirements : [];

            foreach ($requirements as $reqKey => $reqValue) {
                if ($reqKey === 'meta' && is_array($reqValue)) {
                    $policy['meta'] = array_merge($policy['meta'], $reqValue);
                    continue;
                }

                if (! array_key_exists($reqKey, $policy)) {
                    $policy['meta'][$reqKey] = $reqValue;
                    continue;
                }

                if (is_bool($policy[$reqKey])) {
                    $policy[$reqKey] = (bool) $reqValue || (bool) $policy[$reqKey];
                    continue;
                }

                if (is_int($policy[$reqKey]) && is_numeric($reqValue)) {
                    $policy[$reqKey] = (int) $reqValue;
                    continue;
                }

                if (is_string($policy[$reqKey]) && is_string($reqValue) && $reqValue !== '') {
                    $policy[$reqKey] = $reqValue;
                }
            }
        }

        if ($exam->exam_mode === 'proctored') {
            $policy['require_webcam'] = true;
            $policy['require_microphone'] = true;
            $policy['require_fullscreen'] = true;
            $policy['detect_tab_switch'] = true;
        }

        $policy['enabled_rule_keys'] = array_values(array_unique($enabledKeys));

        return $policy;
    }

    public function syncPolicy(Exam $exam, ?array $selectedKeys = null): ExamProctoringPolicy
    {
        $resolved = $this->resolve($exam, $selectedKeys);

        return ExamProctoringPolicy::query()->updateOrCreate(
            ['exam_id' => $exam->id],
            $resolved
        );
    }

    /**
     * @param  list<string>  $selectedKeys
     * @return Collection<int, ExamInstructionRule>
     */
    public function rulesForExam(Exam $exam, array $selectedKeys): Collection
    {
        if ($selectedKeys === []) {
            return collect();
        }

        return ExamInstructionRule::query()
            ->when($exam->organization_id, fn ($q) => $q->where('organization_id', $exam->organization_id))
            ->where('status', 'active')
            ->where(function ($q) use ($selectedKeys) {
                $q->whereIn('rule_key', $selectedKeys)
                    ->orWhereIn('slug', $selectedKeys);
            })
            ->ordered()
            ->get();
    }

    /**
     * Checklist items for the prepare page.
     *
     * @return list<array<string, mixed>>
     */
    public function readinessChecks(array $policy): array
    {
        $checks = [];

        if (! empty($policy['require_webcam'])) {
            $checks[] = [
                'key' => 'webcam',
                'label' => 'Webcam',
                'description' => 'Allow camera access and keep the live preview active.',
                'required' => true,
            ];
        }

        if (! empty($policy['require_microphone'])) {
            $checks[] = [
                'key' => 'microphone',
                'label' => 'Microphone',
                'description' => 'Allow microphone access and confirm audio input.',
                'required' => true,
            ];
        }

        if (! empty($policy['require_fullscreen'])) {
            $checks[] = [
                'key' => 'fullscreen',
                'label' => 'Fullscreen',
                'description' => 'Enter fullscreen before starting the exam.',
                'required' => true,
            ];
        }

        if (! empty($policy['require_photo_verification']) || ! empty($policy['require_identity_verification'])) {
            $checks[] = [
                'key' => 'selfie',
                'label' => 'Identity selfie',
                'description' => 'Capture a live selfie with your webcam. Uploads are not allowed.',
                'required' => true,
            ];
        }

        if (! empty($policy['block_context_menu']) || ! empty($policy['detect_devtools'])) {
            $checks[] = [
                'key' => 'browser_lock',
                'label' => 'Browser protections',
                'description' => 'Right-click and developer tools shortcuts will be blocked during the exam.',
                'required' => false,
                'informational' => true,
            ];
        }

        return $checks;
    }
}
