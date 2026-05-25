<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PostScheduler extends BaseModel
{
    protected $table = 'post_scheduler';
 
    protected $fillable = [
        'detail_id',
        'sosial_account_id',
        'scheduled_at',
        'executed_at',
        'status',         // pending | processing | done | failed
        'retry_count',
        'next_retry_at',
    ];
 
    protected $casts = [
        'scheduled_at'  => 'datetime',
        'executed_at'   => 'datetime',
        'next_retry_at' => 'datetime',
    ];
 
    // Relasi ke detail postingan
    public function detail()
    {
        return $this->belongsTo(PostDetail::class, 'detail_id');
    }

    // Relasi ke postingan utama melalui detail
    public function post()
    {
        return $this->hasOneThrough(
            SosialPost::class,
            PostDetail::class,
            'id',
            'id',
            'detail_id',
            'post_id'
        );
    }
 
    // Relasi ke akun sosial
    public function akunSosial()
    {
        return $this->belongsTo(SosialAccount::class, 'sosial_account_id');
    }
 
    // Relasi ke log eksekusi
    public function logs()
    {
        return $this->hasMany(PostLog::class, 'post_id', 'post_id');
    }

    public function getPostIdAttribute(): ?int
    {
        return $this->detail?->post_id;
    }

    public function getScheduledTimeAttribute()
    {
        return $this->scheduled_at;
    }
}

