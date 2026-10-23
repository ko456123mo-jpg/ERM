<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'username',
        'name',
        'email',
        'password',
        'role_id',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function auditLogs()
    {
        return $this->hasMany(AuditLog::class);
    }

    public function getRoleLevel(): int
    {
        if (!$this->role) {
            return 1;
        }
        return $this->role->level;
    }

    public function isSuperAdmin(): bool
    {
        return $this->getRoleLevel() >= 4;
    }

    public function isAdmin(): bool
    {
        return $this->getRoleLevel() >= 3;
    }

    public function isAnalyst(): bool
    {
        return $this->getRoleLevel() === 2;
    }

    public function isStandardUser(): bool
    {
        return $this->getRoleLevel() <= 1;
    }

    public function canManageUser(User $target): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        // Only Administrator level (level >= 3) can manage users
        if (!$this->isAdmin()) {
            return false;
        }

        // Cannot manage a user with level >= caller's level
        return $this->getRoleLevel() > $target->getRoleLevel();
    }

    public function canAssignRole(Role $targetRole): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        // Only Administrator level can assign roles
        if (!$this->isAdmin()) {
            return false;
        }

        // Cannot assign a role with level >= caller's level
        return $this->getRoleLevel() > $targetRole->level;
    }

    public function hasRole(string $roleName): bool
    {
        if (!$this->role) {
            return false;
        }
        return strtoupper(trim($this->role->name)) === strtoupper(trim($roleName));
    }

    public function hasPermission(string $permissionName): bool
    {
        // Super Admin has implicit access to all permissions
        if ($this->isSuperAdmin()) {
            return true;
        }

        if (!$this->role) {
            return false;
        }

        // Lazy load permissions if not loaded
        if (!$this->role->relationLoaded('permissions')) {
            $this->role->load('permissions');
        }

        return $this->role->permissions->contains('name', $permissionName);
    }
}
