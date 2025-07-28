<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'plan_id',
        'status',
        'amount',
        'expires_at',
        'completed_at',
        'cancelled_at'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'expires_at' => 'datetime',
        'completed_at' => 'datetime',
        'cancelled_at' => 'datetime'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }

    public function payment()
    {
        return $this->hasOne(Payment::class);
    }

    public function invoice()
    {
        return $this->hasOne(Invoice::class);
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<', now())
                    ->where('status', 'pending');
    }

    // Helper methods
    public function isExpired()
    {
        return $this->expires_at && $this->expires_at->isPast() && $this->status === 'pending';
    }

    public function getFormattedAmountAttribute()
    {
        return 'Rp ' . number_format($this->amount, 0, ',', '.');
    }
}

