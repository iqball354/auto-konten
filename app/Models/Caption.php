<?php

namespace App\Models;

class Caption extends BaseModel
{
    protected $table = 'captions';

    protected $fillable = [
        'user_id',
        'platform',
        'topic',
        'tone',
        'audience',
        'caption_content',
        'hashtags',
        'status',
    ];

    protected $casts = [
        'hashtags' => 'array',
    ];
}
