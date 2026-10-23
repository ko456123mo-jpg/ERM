<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'username',
        'action',
        'target',
        'ip_address',
        'user_agent',
        'details',
    ];

    protected $casts = [
        'details' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public static function log(string $action, ?string $target = null, ?array $details = null): self
    {
        $user = auth()->user();
        return self::create([
            'user_id' => $user?->id,
            'username' => $user?->username ?? 'System/Guest',
            'action' => $action,
            'target' => $target,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'details' => $details,
        ]);
    }
}
