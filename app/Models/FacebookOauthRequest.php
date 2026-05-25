<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FacebookOauthRequest extends BaseModel
{
    use HasFactory;

    protected $table = 'facebook_oauth_requests';

    protected $fillable = [
        'user_id',
        'state',
        'redirect_uri',
        'short_lived_token',
        'long_lived_token',
        'status',
        'approved_by',
        'approved_at',
        'notes',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}

