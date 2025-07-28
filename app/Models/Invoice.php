<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Invoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'invoice_number',
        'pdf_url',
        'amount',
        'tax_amount',
        'subtotal'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'subtotal' => 'decimal:2'
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    // Helper methods
    public function getFullPdfUrlAttribute()
    {
        return Storage::disk('public')->url($this->pdf_url);
    }

    public function getFormattedAmountAttribute()
    {
        return 'Rp ' . number_format($this->amount, 0, ',', '.');
    }

    public function getFormattedSubtotalAttribute()
    {
        return 'Rp ' . number_format($this->subtotal, 0, ',', '.');
    }

    public function getFormattedTaxAmountAttribute()
    {
        return 'Rp ' . number_format($this->tax_amount, 0, ',', '.');
    }

    public function pdfExists()
    {
        return Storage::disk('public')->exists($this->pdf_url);
    }
}
