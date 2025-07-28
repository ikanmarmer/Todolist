<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
    'name',
    'email',
    'password',
    'plan_id',
    'plan_expires_at',
    'tasks_used'
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'plan_expires_at' => 'datetime',
        'password' => 'hashed',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'updated_at',
        'crreated_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function plan()
    {
        return $this->belongsTo(Plan::class)->withDefault([
            'id' => 1,
            'name' => 'Free',
            'tasks_limit' => 5,
            'price' => 0
        ]);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function tasks()
    {
        return $this->hasMany(Task::class); // Assuming you have a Task model
    }

    // Helper methods for plan management
    public function canCreateTask()
    {
        return $this->tasks()->count() < $this->plan->tasks_limit;
    }

    public function getRemainingTasksAttribute()
    {
        return max(0, $this->plan->tasks_limit - $this->tasks()->count());
    }

    public function getTaskUsagePercentageAttribute()
    {
        $used = $this->tasks()->count();
        $limit = $this->plan->tasks_limit;
        return $limit > 0 ? ($used / $limit) * 100 : 0;
    }

    public function isPlanExpired()
    {
        return $this->plan_expires_at && $this->plan_expires_at->isPast();
    }

    public function hasActivePremiumPlan()
    {
        return $this->plan_id > 1 && !$this->isPlanExpired();
    }
}
