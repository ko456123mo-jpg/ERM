<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Application extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'original_filename',
        'storage_path',
        'sha256',
        'package_name',
        'version_name',
        'file_size',
        'status',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function analyses()
    {
        return $this->hasMany(Analysis::class);
    }

    public function latestAnalysis()
    {
        return $this->hasOne(Analysis::class)->latestOfMany();
    }
}
