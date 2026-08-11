<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeductBalanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_deduct_balance(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'user']);
        $wallet = Wallet::create(['user_id' => $user->id, 'asset' => 'USDT', 'balance' => '500.00000000']);

        $response = $this->actingAs($admin)->post(route('admin.users.adjust-balance', $user), [
            'action' => 'deduct',
            'asset' => 'USDT',
            'amount' => '100',
            'reason' => 'Manual correction',
        ]);

        $response->assertRedirect(route('admin.users.show', $user));
        $response->assertSessionHas('status', 'balance-deducted');
        $this->assertSame('400.00000000', $wallet->fresh()->balance);
    }

    public function test_deduct_to_exact_zero_balance_is_allowed(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'user']);
        $wallet = Wallet::create(['user_id' => $user->id, 'asset' => 'USDT', 'balance' => '100.00000000']);

        $response = $this->actingAs($admin)->post(route('admin.users.adjust-balance', $user), [
            'action' => 'deduct',
            'asset' => 'USDT',
            'amount' => '100',
            'reason' => 'Manual correction',
        ]);

        $response->assertSessionHas('status', 'balance-deducted');
        $this->assertSame('0.00000000', $wallet->fresh()->balance);
    }

    public function test_insufficient_balance_is_rejected_and_unchanged(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'user']);
        $wallet = Wallet::create(['user_id' => $user->id, 'asset' => 'USDT', 'balance' => '50.00000000']);

        $response = $this->actingAs($admin)->post(route('admin.users.adjust-balance', $user), [
            'action' => 'deduct',
            'asset' => 'USDT',
            'amount' => '100',
            'reason' => 'Manual correction',
        ]);

        $response->assertSessionHasErrorsIn('adjustBalance', ['amount']);
        $errors = session('errors')->getBag('adjustBalance');
        $this->assertStringContainsString('Insufficient balance', $errors->first('amount'));
        $this->assertStringContainsString('50', $errors->first('amount'));
        $this->assertSame('50.00000000', $wallet->fresh()->balance);
    }

    public function test_deduct_invalid_amounts_are_rejected_and_balance_unchanged(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'user']);
        $wallet = Wallet::create(['user_id' => $user->id, 'asset' => 'USDT', 'balance' => '500.00000000']);

        foreach (['', '0', '-10', 'abc', '1e3'] as $invalidAmount) {
            $response = $this->actingAs($admin)->post(route('admin.users.adjust-balance', $user), [
                'action' => 'deduct',
                'asset' => 'USDT',
                'amount' => $invalidAmount,
                'reason' => 'Manual correction',
            ]);

            $response->assertSessionHasErrorsIn('adjustBalance', ['amount']);
        }

        $this->assertSame('500.00000000', $wallet->fresh()->balance);
    }

    public function test_deduct_wallet_not_found_is_handled_safely(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'user']);
        Wallet::create(['user_id' => $user->id, 'asset' => 'USDT', 'balance' => '500.00000000']);

        $response = $this->actingAs($admin)->post(route('admin.users.adjust-balance', $user), [
            'action' => 'deduct',
            'asset' => 'ETH',
            'amount' => '10',
            'reason' => 'Manual correction',
        ]);

        $response->assertSessionHasErrorsIn('adjustBalance', ['asset']);
    }

    public function test_invalid_action_is_rejected_and_balance_unchanged(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'user']);
        $wallet = Wallet::create(['user_id' => $user->id, 'asset' => 'USDT', 'balance' => '500.00000000']);

        $response = $this->actingAs($admin)->post(route('admin.users.adjust-balance', $user), [
            'action' => 'withdraw',
            'asset' => 'USDT',
            'amount' => '100',
            'reason' => 'Manual correction',
        ]);

        $response->assertSessionHas('error', 'invalid-adjustment-action');
        $this->assertSame('500.00000000', $wallet->fresh()->balance);
    }

    public function test_guest_is_denied(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        Wallet::create(['user_id' => $user->id, 'asset' => 'USDT', 'balance' => '500.00000000']);

        $response = $this->post(route('admin.users.adjust-balance', $user), [
            'action' => 'deduct',
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
            'action' => 'deduct',
            'asset' => 'USDT',
            'amount' => '100',
            'reason' => 'Manual correction',
        ]);

        $response->assertForbidden();
        $this->assertSame('500.00000000', $wallet->fresh()->balance);
    }

    public function test_deduct_never_touches_another_users_wallet(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $targetUser = User::factory()->create(['role' => 'user']);
        $otherUser = User::factory()->create(['role' => 'user']);

        Wallet::create(['user_id' => $targetUser->id, 'asset' => 'USDT', 'balance' => '500.00000000']);
        $otherWallet = Wallet::create(['user_id' => $otherUser->id, 'asset' => 'USDT', 'balance' => '300.00000000']);

        $this->actingAs($admin)->post(route('admin.users.adjust-balance', $targetUser), [
            'action' => 'deduct',
            'asset' => 'USDT',
            'amount' => '100',
            'reason' => 'Manual correction',
        ]);

        $this->assertSame('300.00000000', $otherWallet->fresh()->balance);
    }

    public function test_add_balance_still_works_after_deduct_implementation(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'user']);
        $wallet = Wallet::create(['user_id' => $user->id, 'asset' => 'USDT', 'balance' => '400.00000000']);

        $response = $this->actingAs($admin)->post(route('admin.users.adjust-balance', $user), [
            'action' => 'add',
            'asset' => 'USDT',
            'amount' => '100',
            'reason' => 'Manual correction',
        ]);

        $response->assertSessionHas('status', 'balance-added');
        $this->assertSame('500.00000000', $wallet->fresh()->balance);
    }

    public function test_sequential_deducts_do_not_lose_updates(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'user']);
        $wallet = Wallet::create(['user_id' => $user->id, 'asset' => 'USDT', 'balance' => '500.00000000']);

        $this->actingAs($admin)->post(route('admin.users.adjust-balance', $user), [
            'action' => 'deduct',
            'asset' => 'USDT',
            'amount' => '400',
            'reason' => 'First admin deduct',
        ]);

        $this->assertSame('100.00000000', $wallet->fresh()->balance);

        $second = $this->actingAs($admin)->post(route('admin.users.adjust-balance', $user), [
            'action' => 'deduct',
            'asset' => 'USDT',
            'amount' => '200',
            'reason' => 'Second admin deduct, should fail: only 100 left',
        ]);

        $second->assertSessionHasErrorsIn('adjustBalance', ['amount']);
        $this->assertSame('100.00000000', $wallet->fresh()->balance);
    }
}
