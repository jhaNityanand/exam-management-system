<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ImportQuestion extends Model
{
    use BelongsToOrganization, HasFactory;

    protected $fillable = [
        'organization_id',
        'original_file_name',
        'file_path',
        'file_type',
        'mime_type',
        'disk',
        'file_size',
        'status',
        'total_rows',
        'successful_rows',
        'failed_rows',
        'import_logs',
        'errors',
        'created_by',
        'imported_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'file_size' => 'integer',
            'total_rows' => 'integer',
            'successful_rows' => 'integer',
            'failed_rows' => 'integer',
            'import_logs' => 'array',
            'errors' => 'array',
            'imported_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function questions(): HasMany
    {
        return $this->hasMany(Question::class, 'import_question_id');
    }
}
