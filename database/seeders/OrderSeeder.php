<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class OrderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::query()
            ->where('role', 'user')
            ->each(function (User $user) {
                Order::factory(fake()->numberBetween(1, 10))->create([
                    'user_id' => $user->id,
                ]);
            });
    }
}
