<?php

namespace Tests\Feature\Admin;

use App\Models\AdjustmentHistory;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class AdjustmentHistoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_add_balance_records_history(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'user']);
        $wallet = Wallet::create(['user_id' => $user->id, 'asset' => 'USDT', 'balance' => '500.00000000']);

        $this->actingAs($admin)->post(route('admin.users.adjust-balance', $user), [
            'action' => 'add',
            'asset' => 'USDT',
            'amount' => '100',
            'reason' => 'Bonus',
        ]);

        $this->assertSame(1, AdjustmentHistory::count());

        $history = AdjustmentHistory::first();

        $this->assertSame($admin->id, $history->admin_id);
        $this->assertSame($user->id, $history->user_id);
        $this->assertSame($wallet->id, $history->wallet_id);
        $this->assertSame('USDT', $history->asset);
        $this->assertSame('add', $history->action);
        $this->assertSame('100.00000000', $history->amount);
        $this->assertSame('500.00000000', $history->balance_before);
        $this->assertSame('600.00000000', $history->balance_after);
        $this->assertSame('Bonus', $history->reason);
    }

    public function test_deduct_balance_records_history(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'user']);
        $wallet = Wallet::create(['user_id' => $user->id, 'asset' => 'USDT', 'balance' => '500.00000000']);

        $this->actingAs($admin)->post(route('admin.users.adjust-balance', $user), [
            'action' => 'deduct',
            'asset' => 'USDT',
            'amount' => '100',
            'reason' => 'Manual correction',
        ]);

        $this->assertSame(1, AdjustmentHistory::count());

        $history = AdjustmentHistory::first();

        $this->assertSame($admin->id, $history->admin_id);
        $this->assertSame($user->id, $history->user_id);
        $this->assertSame($wallet->id, $history->wallet_id);
        $this->assertSame('deduct', $history->action);
        $this->assertSame('100.00000000', $history->amount);
        $this->assertSame('500.00000000', $history->balance_before);
        $this->assertSame('400.00000000', $history->balance_after);
        $this->assertSame('Manual correction', $history->reason);
    }

    public function test_insufficient_balance_creates_no_history(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'user']);
        $wallet = Wallet::create(['user_id' => $user->id, 'asset' => 'USDT', 'balance' => '50.00000000']);

        $this->actingAs($admin)->post(route('admin.users.adjust-balance', $user), [
            'action' => 'deduct',
            'asset' => 'USDT',
            'amount' => '100',
            'reason' => 'Manual correction',
        ]);

        $this->assertSame('50.00000000', $wallet->fresh()->balance);
        $this->assertSame(0, AdjustmentHistory::count());
    }

    public function test_invalid_amount_creates_no_history(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'user']);
        Wallet::create(['user_id' => $user->id, 'asset' => 'USDT', 'balance' => '500.00000000']);

        $this->actingAs($admin)->post(route('admin.users.adjust-balance', $user), [
            'action' => 'add',
            'asset' => 'USDT',
            'amount' => '0',
            'reason' => 'Manual correction',
        ]);

        $this->assertSame(0, AdjustmentHistory::count());
    }

    public function test_wallet_not_found_creates_no_history(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'user']);
        Wallet::create(['user_id' => $user->id, 'asset' => 'USDT', 'balance' => '500.00000000']);

        $this->actingAs($admin)->post(route('admin.users.adjust-balance', $user), [
            'action' => 'add',
            'asset' => 'ETH',
            'amount' => '10',
            'reason' => 'Manual correction',
        ]);

        $this->assertSame(0, AdjustmentHistory::count());
    }

    public function test_invalid_action_creates_no_history(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'user']);
        Wallet::create(['user_id' => $user->id, 'asset' => 'USDT', 'balance' => '500.00000000']);

        $this->actingAs($admin)->post(route('admin.users.adjust-balance', $user), [
            'action' => 'force_win',
            'asset' => 'USDT',
            'amount' => '10',
            'reason' => 'Manual correction',
        ]);

        $this->assertSame(0, AdjustmentHistory::count());
    }

    public function test_unauthorized_requests_create_no_history(): void
    {
        $regular = User::factory()->create(['role' => 'user']);
        $target = User::factory()->create(['role' => 'user']);
        Wallet::create(['user_id' => $target->id, 'asset' => 'USDT', 'balance' => '500.00000000']);

        // Guest
        $this->post(route('admin.users.adjust-balance', $target), [
            'action' => 'add', 'asset' => 'USDT', 'amount' => '10', 'reason' => 'x',
        ]);

        // Regular user
        $this->actingAs($regular)->post(route('admin.users.adjust-balance', $target), [
            'action' => 'add', 'asset' => 'USDT', 'amount' => '10', 'reason' => 'x',
        ]);

        $this->assertSame(0, AdjustmentHistory::count());
    }

    public function test_history_failure_rolls_back_wallet_update(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'user']);
        $wallet = Wallet::create(['user_id' => $user->id, 'asset' => 'USDT', 'balance' => '500.00000000']);

        AdjustmentHistory::creating(function () {
            throw new RuntimeException('Simulated history persistence failure.');
        });

        try {
            $response = $this->actingAs($admin)->post(route('admin.users.adjust-balance', $user), [
                'action' => 'add',
                'asset' => 'USDT',
                'amount' => '100',
                'reason' => 'Manual correction',
            ]);

            $response->assertSessionHas('error', 'balance-add-failed');
            $this->assertSame('500.00000000', $wallet->fresh()->balance);
            $this->assertSame(0, AdjustmentHistory::count());
        } finally {
            AdjustmentHistory::flushEventListeners();
        }
    }

    public function test_single_adjustment_creates_exactly_one_history_record(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'user']);
        Wallet::create(['user_id' => $user->id, 'asset' => 'USDT', 'balance' => '500.00000000']);

        $this->actingAs($admin)->post(route('admin.users.adjust-balance', $user), [
            'action' => 'add',
            'asset' => 'USDT',
            'amount' => '100',
            'reason' => 'Manual correction',
        ]);

        $this->assertSame(1, AdjustmentHistory::count());
    }

    public function test_admin_id_is_taken_from_session_not_request(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $otherAdmin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'user']);
        Wallet::create(['user_id' => $user->id, 'asset' => 'USDT', 'balance' => '500.00000000']);

        $this->actingAs($admin)->post(route('admin.users.adjust-balance', $user), [
            'action' => 'add',
            'asset' => 'USDT',
            'amount' => '100',
            'reason' => 'Manual correction',
            'admin_id' => $otherAdmin->id,
        ]);

        $history = AdjustmentHistory::first();
        $this->assertSame($admin->id, $history->admin_id);
        $this->assertNotSame($otherAdmin->id, $history->admin_id);
    }

    public function test_user_relationship_returns_adjustment_histories(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'user']);
        Wallet::create(['user_id' => $user->id, 'asset' => 'USDT', 'balance' => '500.00000000']);

        $this->actingAs($admin)->post(route('admin.users.adjust-balance', $user), [
            'action' => 'add',
            'asset' => 'USDT',
            'amount' => '100',
            'reason' => 'Manual correction',
        ]);

        $this->assertCount(1, $user->fresh()->adjustmentHistories);
        $this->assertSame($admin->id, $user->fresh()->adjustmentHistories->first()->admin->id);
    }
}
