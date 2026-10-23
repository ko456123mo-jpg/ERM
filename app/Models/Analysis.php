<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Analysis extends Model
{
    use HasFactory;

    protected $fillable = [
        'application_id',
        'engine',
        'analysis_type',
        'risk_level',
        'score',
        'findings_json',
        'permissions_json',
        'components_json',
        'security_issues_json',
        'raw_metadata_json',
        'status',
        'error_message',
    ];

    protected $casts = [
        'findings_json' => 'array',
        'permissions_json' => 'array',
        'components_json' => 'array',
        'security_issues_json' => 'array',
        'raw_metadata_json' => 'array',
    ];

    public function application()
    {
        return $this->belongsTo(Application::class);
    }
}
