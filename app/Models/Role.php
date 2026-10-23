<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'description'];

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function permissions()
    {
        return $this->belongsToMany(Permission::class, 'role_permissions');
    }

    public function syncPermissions(array $permissionIds): void
    {
        $this->permissions()->sync($permissionIds);
    }

    public function getLevelAttribute(): int
    {
        $nameUpper = strtoupper(trim($this->name));
        if (str_contains($nameUpper, 'SUPER ADMIN') || str_contains($nameUpper, 'SUPERADMIN')) {
            return 4;
        }
        if (str_contains($nameUpper, 'ADMIN')) {
            return 3;
        }
        if (str_contains($nameUpper, 'ANALYST') || str_contains($nameUpper, 'DEVELOPER')) {
            return 2;
        }
        return 1; // Standard User, Viewer, etc.
    }
}
