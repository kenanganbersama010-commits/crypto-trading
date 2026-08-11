<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BalanceValidationHardeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_decimal_precision_is_consistent_across_add_then_deduct(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'user']);
        $wallet = Wallet::create(['user_id' => $user->id, 'asset' => 'BTC', 'balance' => '10.50000000']);

        $this->actingAs($admin)->post(route('admin.users.adjust-balance', $user), [
            'action' => 'add',
            'asset' => 'BTC',
            'amount' => '0.25',
            'reason' => 'Bonus',
        ]);

        $this->assertSame('10.75000000', $wallet->fresh()->balance);

        $this->actingAs($admin)->post(route('admin.users.adjust-balance', $user), [
            'action' => 'deduct',
            'asset' => 'BTC',
            'amount' => '0.75',
            'reason' => 'Manual correction',
        ]);

        $this->assertSame('10.00000000', $wallet->fresh()->balance);
    }

    public function test_amount_exceeding_column_capacity_is_rejected_cleanly_not_a_server_error(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'user']);
        $wallet = Wallet::create(['user_id' => $user->id, 'asset' => 'USDT', 'balance' => '500.00000000']);

        // wallets.balance is decimal(20,8): max 12 integer digits. 13 digits must be
        // rejected by validation, not allowed to reach the database layer.
        $response = $this->actingAs($admin)->post(route('admin.users.adjust-balance', $user), [
            'action' => 'add',
            'asset' => 'USDT',
            'amount' => '1234567890123',
            'reason' => 'Manual correction',
        ]);

        $response->assertSessionHasErrorsIn('adjustBalance', ['amount']);
        $this->assertSame('500.00000000', $wallet->fresh()->balance);
    }

    public function test_amount_at_column_capacity_boundary_is_accepted(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'user']);
        // 12 integer digits, within decimal(20,8) capacity.
        $wallet = Wallet::create(['user_id' => $user->id, 'asset' => 'USDT', 'balance' => '0.00000000']);

        $response = $this->actingAs($admin)->post(route('admin.users.adjust-balance', $user), [
            'action' => 'add',
            'asset' => 'USDT',
            'amount' => '123456789012',
            'reason' => 'Manual correction',
        ]);

        $response->assertSessionHas('status', 'balance-added');
        $this->assertSame('123456789012.00000000', $wallet->fresh()->balance);
    }
}
