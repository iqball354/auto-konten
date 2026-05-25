<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class SosialAccount extends BaseModel
{
    use HasFactory;

    protected $table = 'sosial_accounts';

    protected $fillable = [
        'user_id',
        'platform',           // instagram | facebook
        'platform_user_id',
        'username',
        'page_id',
        'access_token',       // encrypted AES-256
        'token_expires_at',
        'is_active',
        'deleted_at',
    ];
 
    protected $hidden = [
        'access_token', // jangan pernah expose token ke view/response
    ];
 
    protected $casts = [
        'token_expires_at' => 'datetime',
        'deleted_at'       => 'datetime',
        'is_active'        => 'boolean',
    ];

    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    public function scopeNotDeleted(Builder $query): Builder
    {
        return $query->whereNull('deleted_at');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeForUserPlatformUserId(Builder $query, int $userId, string $platform, string $platformUserId): Builder
    {
        return $query->forUser($userId)
            ->where('platform', $platform)
            ->where('platform_user_id', $platformUserId)
            ->notDeleted();
    }

    // Accessor untuk token_status
    public function getTokenStatusAttribute(): string
    {
        if (!$this->is_active) {
            return 'expired';
        }

        if (!$this->token_expires_at) {
            return 'valid';
        }

        $diff = now()->diffInDays($this->token_expires_at, false);

        if ($diff < 0) {
            return 'expired';
        }

        if ($diff <= 7) {
            return 'akan_expired';
        }

        return 'valid';
    }

    public function getDisplayNameAttribute(): string
    {
        return (string) ($this->username ?: $this->platform_user_id);
    }
 
    // Relasi ke user
    public function user()
    {
        return $this->belongsTo(User::class);
    }
 
    // Relasi ke jadwal posting
    public function jadwal()
    {
        return $this->hasMany(PostScheduler::class, 'sosial_account_id');
    }
}

