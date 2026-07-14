<?php

namespace App\Models;

use App\Traits\HasAuditTrails;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Gallery extends Model
{
    use HasAuditTrails, HasFactory, SoftDeletes;

    public const VARIANT_ORIGINAL = 'original';

    public const VARIANT_ADJUSTED = 'adjusted';

    protected $fillable = [
        'organization_id',
        'parent_id',
        'variant',
        'original_name',
        'file_name',
        'file_path',
        'file_url',
        'original_path',
        'bin_path',
        'file_extension',
        'mime_type',
        'kind',
        'file_size',
        'width',
        'height',
        'folder',
        'disk',
        'alt_text',
        'description',
        'status',
        'source',
        'attachable_type',
        'attachable_id',
        'last_referenced_at',
        'uploaded_by',
        'created_by',
        'updated_by',
        'updated_by_history',
        'restored_at',
    ];

    protected function casts(): array
    {
        return [
            'file_size' => 'integer',
            'width' => 'integer',
            'height' => 'integer',
            'updated_by_history' => 'array',
            'restored_at' => 'datetime',
            'deleted_at' => 'datetime',
            'last_referenced_at' => 'datetime',
        ];
    }

    public function scopeForOrg(Builder $query, int $orgId): Builder
    {
        return $query->where('organization_id', $orgId);
    }

    public function scopeOnlyTrashedBin(Builder $query): Builder
    {
        return $query->onlyTrashed();
    }

    public function scopeOrphans(Builder $query): Builder
    {
        return $query->whereNull('attachable_id');
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function adjusted(): HasMany
    {
        return $this->children()->where('variant', self::VARIANT_ADJUSTED);
    }

    public function attachable(): MorphTo
    {
        return $this->morphTo();
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isImage(): bool
    {
        return $this->kind === 'image' || str_starts_with((string) $this->mime_type, 'image/');
    }

    public function isAdjusted(): bool
    {
        return $this->variant === self::VARIANT_ADJUSTED;
    }

    public function humanSize(): string
    {
        $bytes = (int) $this->file_size;
        if ($bytes < 1024) {
            return $bytes.' B';
        }
        if ($bytes < 1048576) {
            return round($bytes / 1024, 1).' KB';
        }

        return round($bytes / 1048576, 2).' MB';
    }

    public function dimensionsLabel(): ?string
    {
        if (! $this->width || ! $this->height) {
            return null;
        }

        return $this->width.' × '.$this->height;
    }
}
