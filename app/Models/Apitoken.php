<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApiToken extends BaseModel
{
    protected $table = 'api_tokens';
 
    protected $fillable = [
        'user_id',
        'meta_app_id',
        'meta_app_secret',    // encrypted
        'short_lived_token',  // encrypted
        'long_lived_token',   // encrypted
        'oauth_state',
        'oauth_redirect_uri',
        'token_refreshed_at',
    ];
 
    protected $hidden = [
        'meta_app_secret',  // jangan expose ke response/view
        'short_lived_token',
        'long_lived_token',
    ];
 
    protected $casts = [
        'token_refreshed_at' => 'datetime',
    ];
 
    // Relasi ke user
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
