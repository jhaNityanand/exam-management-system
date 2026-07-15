<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;

use App\Traits\HasAuditTrails;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ExamInstructionRule extends Model
{
    use BelongsToOrganization, HasAuditTrails, HasFactory, SoftDeletes;

    protected $table = 'exam_instruction_rules';

    protected $fillable = [
        'organization_id',
        'title',
        'slug',
        'description',
        'status',
        'sort_order',
        'icon',
        'category',
        'is_default',
        'is_required',
        'created_by',
        'updated_by',
        'updated_by_history',
    ];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
            'is_required' => 'boolean',
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
        return $query->orderBy('sort_order')->orderBy('title');
    }

    /**
     * Payload shape used by the exam create wizard.
     *
     * @return array{id: string, label: string, description: string, category: ?string, icon: ?string, is_default: bool, is_required: bool}
     */
    public function toFormOption(): array
    {
        return [
            'id' => $this->slug,
            'label' => $this->title,
            'description' => (string) ($this->description ?? ''),
            'category' => $this->category,
            'icon' => $this->icon,
            'is_default' => (bool) $this->is_default,
            'is_required' => (bool) $this->is_required,
        ];
    }
}
