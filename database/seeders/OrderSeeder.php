<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Seeder;

class OrderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create users if they don't exist
        if (User::count() == 0) {
            User::factory(5)->create();
        }

        // Create 20 random orders in pending status
        $users = User::all();

        foreach ($users as $user) {
            Order::factory()
                ->count(4)  // 4 orders per user
                ->create([
                    'user_id' => $user->id,
                ]);
        }

        $this->command->info('Created ' . (4 * $users->count()) . ' orders in pending status');
    }
}
