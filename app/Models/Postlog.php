<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PostLog extends BaseModel
{
    protected $table = 'post_logs';

    public $timestamps = false;
 
    protected $fillable = [
        'post_id',
        'status',            // success | failed
        'platform_post_id',  // ID postingan di Meta jika sukses
        'error_code',
        'error_message',
        'response_payload',  // JSON raw response dari Meta API
        'executed_at',
    ];
 
    protected $casts = [
        'response_payload' => 'array',
        'executed_at'      => 'datetime',
    ];
 
    public function post()
    {
        return $this->belongsTo(SosialPost::class, 'post_id');
    }

    // Relasi kompatibilitas untuk query existing (log -> schedule -> post)
    public function schedule()
    {
        return $this->hasOneThrough(
            PostScheduler::class,
            PostDetail::class,
            'post_id',
            'detail_id',
            'post_id',
            'id'
        );
    }
}

