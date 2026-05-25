<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SosialPost extends BaseModel
{
    protected $table = 'sosial_post';
 
    protected $fillable = [
        'user_id',
        'status',            // draft | scheduled | published | failed
        'publish_type',      // immediate | scheduled
        'platform_targets',  // JSON: ['instagram','facebook']
        'deleted_at',
    ];
 
    protected $casts = [
        'platform_targets' => 'array',
        'deleted_at'       => 'datetime',
    ];
 
    // Relasi ke user
    public function user()
    {
        return $this->belongsTo(User::class);
    }
 
    // Relasi ke detail postingan (tabel post_detail)
    public function media()
    {
        return $this->hasMany(PostDetail::class, 'post_id')
            ->whereNotNull('file_path');
    }

    public function details()
    {
        return $this->hasMany(PostDetail::class, 'post_id');
    }

    public function primaryDetail()
    {
        return $this->hasOne(PostDetail::class, 'post_id')
            ->orderBy('order')
            ->orderBy('id');
    }
 
    // Relasi ke jadwal (tabel post_scheduler)
    public function jadwal()
    {
        return $this->hasManyThrough(
            PostScheduler::class,
            PostDetail::class,
            'post_id',
            'detail_id',
            'id',
            'id'
        );
    }
 
    // Scope: hanya milik user yang login & belum dihapus
    public function scopeMilikSaya($query)
    {
        return $query->where('user_id', auth()->id())->whereNull('deleted_at');
    }

    public function getCaptionAttribute($value): ?string
    {
        if (!empty($value)) {
            return $value;
        }

        if ($this->relationLoaded('primaryDetail')) {
            return $this->primaryDetail?->caption;
        }

        return $this->primaryDetail()->value('caption');
    }

    public function getHashtagsAttribute($value): ?string
    {
        if (!empty($value)) {
            return $value;
        }

        if ($this->relationLoaded('primaryDetail')) {
            return $this->primaryDetail?->hashtags;
        }

        return $this->primaryDetail()->value('hashtags');
    }

    public function getTextTemplateAttribute($value): ?string
    {
        if (!empty($value)) {
            return $value;
        }

        if ($this->relationLoaded('primaryDetail')) {
            return $this->primaryDetail?->text_template;
        }

        return $this->primaryDetail()->value('text_template');
    }

    public function getTemplateTextAttribute($value): ?string
    {
        if (!empty($value)) {
            return $value;
        }

        if ($this->relationLoaded('primaryDetail')) {
            return $this->primaryDetail?->template_text;
        }

        return $this->primaryDetail()->value('template_text');
    }
}

