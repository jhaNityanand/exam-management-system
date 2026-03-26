<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory, HasRoles, Notifiable;

    protected $fillable = ['name', 'email', 'password'];

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

    public function pivotRoleForOrganization(?int $organizationId): ?string
    {
        if ($organizationId === null) {
            return null;
        }

        return $this->organizations()->where('organizations.id', $organizationId)->first()?->pivot?->role;
    }

    public function canInCurrentOrg(string $permission): bool
    {
        return org_context()->userCan($this, $permission);
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
}
