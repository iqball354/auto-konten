<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PostMedia extends BaseModel
{
    protected $table = 'post_media';
 
    protected $fillable = [
        'post_id',
        'media_type',  // image | video
        'file_path',
        'file_url',
        'file_size',
        'mime_type',
        'order',
    ];
 
    // Relasi ke postingan
    public function post()
    {
        return $this->belongsTo(SosialPost::class, 'post_id');
    }
}
