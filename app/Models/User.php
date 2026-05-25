<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\HasApiTokens;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'is_active',
        'avatar',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'is_active' => 'boolean'
    ];

    protected $appends = [
        'effective_active',
    ];
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }

    // Relasi ke akun sosial media
    public function socialAccounts()
    {
        return $this->hasMany(SosialAccount::class, 'user_id');
    }

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class, 'user_id');
    }

    public function latestSubscription()
    {
        return $this->hasOne(Subscription::class, 'user_id')->latestOfMany();
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeInactive(Builder $query): Builder
    {
        return $query->where('is_active', false);
    }

    public function getEffectiveActiveAttribute(): bool
    {
        if (($this->role ?? null) === 'admin') {
            return true;
        }

        $subscription = $this->relationLoaded('latestSubscription')
            ? $this->getRelation('latestSubscription')
            : $this->latestSubscription()->first();

        return $subscription?->isCurrentlyActive() ?? false;
    }

    /**
     * Jalankan callback dalam DB transaction dengan retry otomatis.
     */
    public static function runInTransaction(callable $callback, int $attempts = 1)
    {
        return DB::transaction($callback, $attempts);
    }
}

