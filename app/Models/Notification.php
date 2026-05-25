<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends BaseModel
{
    protected $table = 'notifications';

    const UPDATED_AT = null;
 
    protected $fillable = [
        'user_id',
        'type',     // posting_success | posting_failed | token_expired | dll
        'title',
        'message',
        'is_read',
        'data',     // JSON — data tambahan (post_id, dll)
        'read_at',
    ];
 
    protected $casts = [
        'data'    => 'array',
        'is_read' => 'boolean',
        'read_at' => 'datetime',
    ];
 
    // Relasi ke user
    public function user()
    {
        return $this->belongsTo(User::class);
    }
 
    // Scope: hanya yang belum dibaca
    public function scopeBelumDibaca($query)
    {
        return $query->where('is_read', 0);
    }
}

