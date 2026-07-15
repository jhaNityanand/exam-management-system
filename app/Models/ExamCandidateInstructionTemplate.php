<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;

use App\Traits\HasAuditTrails;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ExamCandidateInstructionTemplate extends Model
{
    use BelongsToOrganization, HasAuditTrails, HasFactory, SoftDeletes;

    protected $table = 'exam_candidate_instruction_templates';

    protected $fillable = [
        'organization_id',
        'name',
        'slug',
        'description',
        'status',
        'sort_order',
        'is_default',
        'template_type',
        'version',
        'icon',
        'created_by',
        'updated_by',
        'updated_by_history',
    ];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
            'sort_order' => 'integer',
            'updated_by_history' => 'array',
        ];
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    /**
     * Payload shape used by the exam create wizard.
     *
     * @return array{id: string, label: string, content: string, template_type: ?string, is_default: bool, version: ?string, icon: ?string}
     */
    public function toFormOption(): array
    {
        return [
            'id' => $this->slug,
            'label' => $this->name,
            'content' => (string) ($this->description ?? ''),
            'template_type' => $this->template_type,
            'is_default' => (bool) $this->is_default,
            'version' => $this->version,
            'icon' => $this->icon,
        ];
    }
}
