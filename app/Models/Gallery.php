<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use App\Traits\HasAuditTrails;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Gallery extends Model
{
    use BelongsToOrganization, HasAuditTrails, HasFactory, SoftDeletes;

    protected $fillable = [
        'organization_id',
        'original_name',
        'file_name',
        'file_path',
        'file_url',
        'original_file_path',
        'modified_file_path',
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

    public function hasModification(): bool
    {
        return filled($this->modified_file_path)
            && $this->modified_file_path !== $this->original_file_path;
    }

    public function displayPath(): string
    {
        if ($this->hasModification()) {
            return (string) $this->modified_file_path;
        }

        return (string) ($this->original_file_path ?: $this->file_path);
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
