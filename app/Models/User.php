<?php

namespace App\Models;

use App\Models\Organization;
use App\Support\OrganizationRoles;
use App\Traits\HasAuditTrails;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasAuditTrails, HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'name',
        'username',
        'email',
        'password',
        'status',
        'created_by',
        'updated_by',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function profile()
    {
        return $this->hasOne(Profile::class, 'id', 'id');
    }

    public function appSettings()
    {
        return $this->hasOne(UserAppSetting::class);
    }

    public function organizations(): BelongsToMany
    {
        return $this->belongsToMany(Organization::class, 'user_organizations')
            ->withPivot(['role', 'status'])
            ->withTimestamps();
    }

    public function belongsToOrganization(int $organizationId): bool
    {
        return $this->organizations()->where('organizations.id', $organizationId)->exists();
    }

    public function activeOrganizationRole(): ?string
    {
        return $this->organizations()
            ->wherePivot('status', 'active')
            ->orderBy('user_organizations.id')
            ->first()
            ?->pivot
            ?->role;
    }

    public function canAccessAdminPanel(): bool
    {
        return OrganizationRoles::canAccessAdminPanel($this->activeOrganizationRole());
    }

    public function ensureCandidateMembership(?int $organizationId = null): void
    {
        $organizationId = $organizationId ?: Organization::query()->value('id');
        if (! $organizationId) {
            return;
        }

        if ($this->belongsToOrganization((int) $organizationId)) {
            return;
        }

        $this->organizations()->attach($organizationId, [
            'role' => OrganizationRoles::CANDIDATE,
            'status' => 'active',
        ]);
    }

    public function questions()
    {
        return $this->hasMany(Question::class, 'created_by');
    }

    public function exams()
    {
        return $this->hasMany(Exam::class, 'created_by');
    }

    public function examAttempts()
    {
        return $this->hasMany(ExamAttempt::class);
    }

    public function activityLogs()
    {
        return $this->hasMany(UserActivityLog::class);
    }
}
