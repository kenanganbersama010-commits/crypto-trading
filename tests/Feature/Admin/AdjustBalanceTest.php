<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdjustBalanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_add_balance(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'user']);
        $wallet = Wallet::create(['user_id' => $user->id, 'asset' => 'USDT', 'balance' => '500.00000000']);

        $response = $this->actingAs($admin)->post(route('admin.users.adjust-balance', $user), [
            'action' => 'add',
            'asset' => 'USDT',
            'amount' => '100',
            'reason' => 'Manual correction',
        ]);

        $response->assertRedirect(route('admin.users.show', $user));
        $response->assertSessionHas('status', 'balance-added');
        $this->assertSame('600.00000000', $wallet->fresh()->balance);
    }

    public function test_add_balance_respects_decimal_precision(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'user']);
        $wallet = Wallet::create(['user_id' => $user->id, 'asset' => 'BTC', 'balance' => '1.50000000']);

        $this->actingAs($admin)->post(route('admin.users.adjust-balance', $user), [
            'action' => 'add',
            'asset' => 'BTC',
            'amount' => '0.25',
            'reason' => 'Bonus',
        ]);

        $this->assertSame('1.75000000', $wallet->fresh()->balance);
    }

    public function test_invalid_amounts_are_rejected_and_balance_unchanged(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'user']);
        $wallet = Wallet::create(['user_id' => $user->id, 'asset' => 'USDT', 'balance' => '500.00000000']);

        foreach (['', '0', '-10', 'abc', '1e3'] as $invalidAmount) {
            $response = $this->actingAs($admin)->post(route('admin.users.adjust-balance', $user), [
                'action' => 'add',
                'asset' => 'USDT',
                'amount' => $invalidAmount,
                'reason' => 'Manual correction',
            ]);

            $response->assertSessionHasErrorsIn('adjustBalance', ['amount']);
        }

        $this->assertSame('500.00000000', $wallet->fresh()->balance);
    }

    public function test_reason_is_required(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'user']);
        Wallet::create(['user_id' => $user->id, 'asset' => 'USDT', 'balance' => '500.00000000']);

        $response = $this->actingAs($admin)->post(route('admin.users.adjust-balance', $user), [
            'action' => 'add',
            'asset' => 'USDT',
            'amount' => '10',
            'reason' => '',
        ]);

        $response->assertSessionHasErrorsIn('adjustBalance', ['reason']);
    }

    public function test_wallet_not_found_is_handled_safely(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'user']);
        Wallet::create(['user_id' => $user->id, 'asset' => 'USDT', 'balance' => '500.00000000']);

        $response = $this->actingAs($admin)->post(route('admin.users.adjust-balance', $user), [
            'action' => 'add',
            'asset' => 'ETH',
            'amount' => '10',
            'reason' => 'Manual correction',
        ]);

        $response->assertSessionHasErrorsIn('adjustBalance', ['asset']);
    }

    public function test_guest_is_denied(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        Wallet::create(['user_id' => $user->id, 'asset' => 'USDT', 'balance' => '500.00000000']);

        $response = $this->post(route('admin.users.adjust-balance', $user), [
            'action' => 'add',
            'asset' => 'USDT',
            'amount' => '100',
            'reason' => 'Manual correction',
        ]);

        $response->assertRedirect(route('login'));
    }

    public function test_regular_user_is_denied(): void
    {
        $regular = User::factory()->create(['role' => 'user']);
        $target = User::factory()->create(['role' => 'user']);
        $wallet = Wallet::create(['user_id' => $target->id, 'asset' => 'USDT', 'balance' => '500.00000000']);

        $response = $this->actingAs($regular)->post(route('admin.users.adjust-balance', $target), [
            'action' => 'add',
            'asset' => 'USDT',
            'amount' => '100',
            'reason' => 'Manual correction',
        ]);

        $response->assertForbidden();
        $this->assertSame('500.00000000', $wallet->fresh()->balance);
    }

    public function test_wallet_of_another_user_is_never_touched(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $targetUser = User::factory()->create(['role' => 'user']);
        $otherUser = User::factory()->create(['role' => 'user']);

        Wallet::create(['user_id' => $targetUser->id, 'asset' => 'USDT', 'balance' => '500.00000000']);
        $otherWallet = Wallet::create(['user_id' => $otherUser->id, 'asset' => 'USDT', 'balance' => '300.00000000']);

        $this->actingAs($admin)->post(route('admin.users.adjust-balance', $targetUser), [
            'action' => 'add',
            'asset' => 'USDT',
            'amount' => '100',
            'reason' => 'Manual correction',
        ]);

        $this->assertSame('300.00000000', $otherWallet->fresh()->balance);
    }
}
