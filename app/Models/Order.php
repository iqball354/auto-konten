<?php

namespace App\Models;

class Order extends BaseModel
{
    protected $table = 'orders';

    protected $fillable = [
        'order_id',
        'user_id',
        'total_price',
        'status',
        'bukti_pembayaran',
        'confirmed_at',
        'catatan',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
