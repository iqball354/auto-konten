<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Subscription extends BaseModel
{
    protected $table = 'subscriptions';
 
    protected $fillable = [
        'user_id',
        'plan',       // free | basic | pro | enterprise
        'status',     // active | expired | cancelled
        'started_at',
        'expired_at',
        'max_accounts',
        'max_posts_per_month',
    ];
 
    protected $casts = [
        'started_at' => 'datetime',
        'expired_at' => 'datetime',
    ];
 
    // Relasi ke user
    public function user()
    {
        return $this->belongsTo(User::class);
    }
 
    // Cek apakah subscription masih aktif
    public function isActive(): bool
    {
        return $this->isCurrentlyActive();
    }

    public function isExpired(): bool
    {
        if (!$this->expired_at) {
            return false;
        }

        return $this->expired_at->isPast();
    }

    public function isCurrentlyActive(): bool
    {
        if ($this->status !== 'active') {
            return false;
        }

        if (!$this->expired_at) {
            return true;
        }

        return $this->expired_at->isFuture();
    }
 
    // Scope: hanya yang aktif
    public function scopeAktif($query)
    {
        return $this->scopeActive($query);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query
            ->where('status', 'active')
            ->where(function (Builder $q): void {
                $q->whereNull('expired_at')
                    ->orWhere('expired_at', '>', now());
            });
    }
}

