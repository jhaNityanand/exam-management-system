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
        'detect_tab_switch',
        'focus_violation_limit',
        'focus_violation_action',
        'auto_submit_on_violation',
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
            'detect_tab_switch' => 'boolean',
            'auto_submit_on_violation' => 'boolean',
            'focus_violation_limit' => 'integer',
            'meta' => 'array',
        ];
    }

    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }
}
