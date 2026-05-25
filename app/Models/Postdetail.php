<?php

namespace App\Models;

class PostDetail extends BaseModel
{
    protected $table = 'post_detail';

    protected $fillable = [
        'post_id',
        'caption',
        'hashtags',
        'text_template',
        'template_text',
        'media_type',
        'file_path',
        'file_url',
        'file_size',
        'mime_type',
        'checksum',
        'order',
    ];

    public function post()
    {
        return $this->belongsTo(SosialPost::class, 'post_id');
    }

    public function schedules()
    {
        return $this->hasMany(PostScheduler::class, 'detail_id');
    }
}