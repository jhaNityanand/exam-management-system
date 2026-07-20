<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExamProctoringPolicy extends Model
{
    protected $fillable = [
        'exam_id',
        'require_webcam',
        'require_microphone',
        'require_fullscreen',
        'require_photo_verification',
        'require_identity_verification',
        'block_copy_paste',
        'block_context_menu',
        'detect_devtools',
        'block_page_refresh',
        'enforce_single_session',
        'single_attempt_per_question',
        'detect_tab_switch',
        'focus_violation_limit',
        'focus_violation_action',
        'auto_submit_on_violation',
        'enabled_rule_keys',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'require_webcam' => 'boolean',
            'require_microphone' => 'boolean',
            'require_fullscreen' => 'boolean',
            'require_photo_verification' => 'boolean',
            'require_identity_verification' => 'boolean',
            'block_copy_paste' => 'boolean',
            'block_context_menu' => 'boolean',
            'detect_devtools' => 'boolean',
            'block_page_refresh' => 'boolean',
            'enforce_single_session' => 'boolean',
            'single_attempt_per_question' => 'boolean',
            'detect_tab_switch' => 'boolean',
            'auto_submit_on_violation' => 'boolean',
            'focus_violation_limit' => 'integer',
            'enabled_rule_keys' => 'array',
            'meta' => 'array',
        ];
    }

    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function toRuntimeArray(): array
    {
        return [
            'require_webcam' => (bool) $this->require_webcam,
            'require_microphone' => (bool) $this->require_microphone,
            'require_fullscreen' => (bool) $this->require_fullscreen,
            'require_photo_verification' => (bool) $this->require_photo_verification,
            'require_identity_verification' => (bool) $this->require_identity_verification,
            'block_copy_paste' => (bool) $this->block_copy_paste,
            'block_context_menu' => (bool) $this->block_context_menu,
            'detect_devtools' => (bool) $this->detect_devtools,
            'block_page_refresh' => (bool) $this->block_page_refresh,
            'enforce_single_session' => (bool) $this->enforce_single_session,
            'single_attempt_per_question' => (bool) $this->single_attempt_per_question,
            'detect_tab_switch' => (bool) $this->detect_tab_switch,
            'focus_violation_limit' => (int) $this->focus_violation_limit,
            'focus_violation_action' => (string) $this->focus_violation_action,
            'auto_submit_on_violation' => (bool) $this->auto_submit_on_violation,
            'enabled_rule_keys' => $this->enabled_rule_keys ?? [],
            'meta' => $this->meta ?? [],
        ];
    }
}
