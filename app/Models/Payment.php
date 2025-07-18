<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $fillable = [
        'order_id',
        'paid_at',
        'transaction_status',
        'snapshot',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
