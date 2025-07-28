<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'snap_token',
        'transaction_status',
        'payment_method',
        'transaction_id',
        'paid_at'
    ];

    protected $casts = [
        'paid_at' => 'datetime'
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    // Scopes
    public function scopeSuccessful($query)
    {
        return $query->where('transaction_status', 'success');
    }

    public function scopePending($query)
    {
        return $query->where('transaction_status', 'pending');
    }

    public function scopeFailed($query)
    {
        return $query->whereIn('transaction_status', ['deny', 'expire', 'cancel']);
    }

    // Helper methods
    public function isSuccessful()
    {
        return $this->transaction_status === 'success';
    }

    public function isPending()
    {
        return $this->transaction_status === 'pending';
    }

    public function isFailed()
    {
        return in_array($this->transaction_status, ['deny', 'expire', 'cancel']);
    }
}
