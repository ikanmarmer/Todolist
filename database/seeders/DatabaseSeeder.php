<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Plan;


class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        // User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);

        User::create([
            'name' => env('ADMIN_NAME'),
            'email' => env('ADMIN_EMAIL'),
            'password' => bcrypt(env('ADMIN_PASSWORD')),
            'is_admin' => true,
        ]);

        User::create([
            'name' => 'User',
            'email' => 'user@example.com',
            'password' => bcrypt(value: 'password123'),
            'is_admin' => false,
        ]);

        $this->call([
            PlanSeeder::class,
        ]);
    }
}
