<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PostInsight extends Model
{
    use HasFactory;

    protected $table = 'post_insights';

    protected $fillable = [
        'post_id',
        'sosial_account_id',
        'metric_impressions',
        'metric_reach',
        'metric_engaged_users',
        'metric_clicks',
        'hour',
        'day_of_week',
        'recorded_at',
    ];

    protected $casts = [
        'recorded_at' => 'datetime',
        'metric_impressions' => 'integer',
        'metric_reach' => 'integer',
        'metric_engaged_users' => 'integer',
        'metric_clicks' => 'integer',
        'hour' => 'integer',
        'day_of_week' => 'integer',
    ];

    // Relationships
    public function sosialAccount()
    {
        return $this->belongsTo(SosialAccount::class, 'sosial_account_id');
    }

    public function post()
    {
        return $this->belongsTo(SosialPost::class, 'post_id', 'platform_post_id');
    }
}
