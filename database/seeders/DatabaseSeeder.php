<?php

namespace Database\Seeders;

use App\Models\Deposit;
use App\Models\KycSubmission;
use App\Models\Trade;
use App\Models\User;
use App\Models\Wallet;
use App\Models\Withdrawal;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        $testUser = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        $this->call(AdminUserSeeder::class);

        Wallet::updateOrCreate(
            ['user_id' => $testUser->id, 'asset' => 'USDT'],
            ['balance' => 1000]
        );

        Wallet::updateOrCreate(
            ['user_id' => $testUser->id, 'asset' => 'BTC'],
            ['balance' => 0.05]
        );

        Deposit::updateOrCreate(
            ['user_id' => $testUser->id, 'asset' => 'USDT', 'status' => 'pending'],
            ['method' => 'crypto', 'amount' => 500]
        );

        Withdrawal::updateOrCreate(
            ['user_id' => $testUser->id, 'asset' => 'USDT', 'status' => 'pending'],
            ['method' => 'crypto', 'amount' => 100, 'destination' => 'test-address']
        );

        KycSubmission::updateOrCreate(
            ['user_id' => $testUser->id, 'status' => 'pending'],
            ['name' => 'Test User', 'nationality' => 'Indonesia', 'id_number' => 'test-id']
        );

        Trade::updateOrCreate(
            ['user_id' => $testUser->id, 'pair' => 'BTC/USDT', 'side' => 'buy'],
            ['amount' => 0.01, 'price' => 65000, 'status' => 'completed']
        );

        Trade::updateOrCreate(
            ['user_id' => $testUser->id, 'pair' => 'ETH/USDT', 'side' => 'sell'],
            ['amount' => 0.2, 'price' => 3200, 'status' => 'completed']
        );
    }
}
