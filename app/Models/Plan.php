<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'price',
        'tasks_limit',
        'color',
        'is_popular',
        'features'
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'is_popular' => 'boolean',
        'features' => 'array'
    ];

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    // Helper methods
    public function getFormattedPriceAttribute()
    {
        return 'Rp ' . number_format($this->price, 0, ',', '.');
    }

    public function getFeaturesArrayAttribute()
    {
        return is_string($this->features) ? json_decode($this->features, true) : $this->features;
    }
}
