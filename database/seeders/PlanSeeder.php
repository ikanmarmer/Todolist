<?php
// database/seeders/PlanSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Plan;

class PlanSeeder extends Seeder
{
    public function run()
    {
        $plans = [
            [
                'name' => 'Free',
                'description' => 'Cocok untuk pemula yang ingin mencoba fitur dasar',
                'price' => 0,
                'tasks_limit' => 5,
                'color' => '#64748b',
                'is_popular' => false,
                'features' => json_encode([
                    '5 Task per bulan',
                    'Basic support',
                    'Todo list sederhana'
                ])
            ],
            [
                'name' => 'Basic',
                'description' => 'Ideal untuk penggunaan personal dengan kebutuhan menengah',
                'price' => 29000,
                'tasks_limit' => 50,
                'color' => '#06b6d4',
                'is_popular' => false,
                'features' => json_encode([
                    '50 Task per bulan',
                    'Email support',
                    'Advanced todo features',
                    'Export data'
                ])
            ],
            [
                'name' => 'Pro',
                'description' => 'Terbaik untuk profesional dan tim kecil',
                'price' => 79000,
                'tasks_limit' => 200,
                'color' => '#8b5cf6',
                'is_popular' => true,
                'features' => json_encode([
                    '200 Task per bulan',
                    'Priority support',
                    'Team collaboration',
                    'Advanced analytics',
                    'Custom categories'
                ])
            ],
            [
                'name' => 'Enterprise',
                'description' => 'Solusi lengkap untuk bisnis dan organisasi besar',
                'price' => 199000,
                'tasks_limit' => 1000,
                'color' => '#f59e0b',
                'is_popular' => false,
                'features' => json_encode([
                    '1000 Task per bulan',
                    '24/7 Premium support',
                    'Advanced team management',
                    'Custom integrations',
                    'White-label solution',
                    'API access'
                ])
            ]
        ];

        foreach ($plans as $plan) {
            Plan::create($plan);
        }
    }
}
