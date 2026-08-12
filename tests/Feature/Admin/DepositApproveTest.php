<?php

namespace Tests\Feature\Admin;

use App\Models\AdjustmentHistory;
use App\Models\Deposit;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class DepositApproveTest extends TestCase
{
    use RefreshDatabase;

    private function makeDeposit(User $user, array $overrides = []): Deposit
    {
        return Deposit::create(array_merge([
            'user_id' => $user->id,
            'method' => 'bank_transfer',
            'asset' => 'USDT',
            'amount' => '100.00000000',
        ], $overrides));
    }

    public function test_normal_approval_updates_wallet_and_deposit(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'user']);
        $wallet = Wallet::create(['user_id' => $user->id, 'asset' => 'USDT', 'balance' => '50.00000000']);
        $deposit = $this->makeDeposit($user);

        $response = $this->actingAs($admin)->post(route('admin.deposits.approve', $deposit));

        $response->assertRedirect(route('admin.deposits.show', $deposit));
        $response->assertSessionHas('status', 'deposit-approved');

        $fresh = $deposit->fresh();
        $this->assertSame('approved', $fresh->status);
        $this->assertSame($admin->id, $fresh->reviewed_by);
        $this->assertNotNull($fresh->reviewed_at);

        $this->assertSame('150.00000000', $wallet->fresh()->balance);
    }

    public function test_approval_records_adjustment_history(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'user']);
        Wallet::create(['user_id' => $user->id, 'asset' => 'USDT', 'balance' => '50.00000000']);
        $deposit = $this->makeDeposit($user, ['amount' => '25.00000000']);

        $this->actingAs($admin)->post(route('admin.deposits.approve', $deposit));

        $this->assertSame(1, AdjustmentHistory::count());
        $history = AdjustmentHistory::first();
        $this->assertSame($admin->id, $history->admin_id);
        $this->assertSame($user->id, $history->user_id);
        $this->assertSame('add', $history->type);
        $this->assertSame('25.00000000', $history->amount);
        $this->assertSame('50.00000000', $history->balance_before);
        $this->assertSame('75.00000000', $history->balance_after);
    }

    public function test_approval_creates_wallet_when_none_exists_for_asset(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'user']);
        $deposit = $this->makeDeposit($user, ['asset' => 'BTC', 'amount' => '0.05000000']);

        $this->assertSame(0, Wallet::where('user_id', $user->id)->where('asset', 'BTC')->count());

        $this->actingAs($admin)->post(route('admin.deposits.approve', $deposit));

        $wallet = Wallet::where('user_id', $user->id)->where('asset', 'BTC')->first();
        $this->assertNotNull($wallet);
        $this->assertSame('0.05000000', $wallet->balance);
        $this->assertSame('approved', $deposit->fresh()->status);
    }

    public function test_double_click_only_approves_once(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'user']);
        $wallet = Wallet::create(['user_id' => $user->id, 'asset' => 'USDT', 'balance' => '0.00000000']);
        $deposit = $this->makeDeposit($user, ['amount' => '100.00000000']);

        // assertSessionHas reads the *current* session state, which is overwritten
        // by each subsequent request — so each response must be asserted on before
        // the next request runs, not batched at the end.
        $first = $this->actingAs($admin)->post(route('admin.deposits.approve', $deposit));
        $first->assertSessionHas('status', 'deposit-approved');

        $second = $this->actingAs($admin)->post(route('admin.deposits.approve', $deposit));
        $second->assertSessionHas('error', 'deposit-already-processed');

        $this->assertSame('100.00000000', $wallet->fresh()->balance);
        $this->assertSame(1, AdjustmentHistory::count());
    }

    public function test_refresh_after_approval_does_not_add_balance_again(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'user']);
        $wallet = Wallet::create(['user_id' => $user->id, 'asset' => 'USDT', 'balance' => '0.00000000']);
        $deposit = $this->makeDeposit($user, ['amount' => '100.00000000']);

        $this->actingAs($admin)->post(route('admin.deposits.approve', $deposit));

        // Simulate viewing/refreshing the detail page after approval — a read-only
        // GET must never re-trigger any balance change.
        $this->actingAs($admin)->get(route('admin.deposits.show', $deposit))->assertOk();

        $this->assertSame('100.00000000', $wallet->fresh()->balance);
    }

    public function test_already_approved_deposit_cannot_be_approved_again(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $reviewer = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'user']);
        $wallet = Wallet::create(['user_id' => $user->id, 'asset' => 'USDT', 'balance' => '200.00000000']);
        $deposit = $this->makeDeposit($user, [
            'amount' => '100.00000000',
            'status' => 'approved',
            'reviewed_by' => $reviewer->id,
            'reviewed_at' => now(),
        ]);

        $response = $this->actingAs($admin)->post(route('admin.deposits.approve', $deposit));

        $response->assertSessionHas('error', 'deposit-already-processed');
        $this->assertSame('200.00000000', $wallet->fresh()->balance);
        $this->assertSame('approved', $deposit->fresh()->status);
        $this->assertSame($reviewer->id, $deposit->fresh()->reviewed_by);
        $this->assertSame(0, AdjustmentHistory::count());
    }

    public function test_rejected_deposit_cannot_be_approved(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $reviewer = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'user']);
        $wallet = Wallet::create(['user_id' => $user->id, 'asset' => 'USDT', 'balance' => '200.00000000']);
        $deposit = $this->makeDeposit($user, [
            'amount' => '100.00000000',
            'status' => 'rejected',
            'reviewed_by' => $reviewer->id,
            'reviewed_at' => now(),
            'rejection_reason' => 'Invalid proof',
        ]);

        $response = $this->actingAs($admin)->post(route('admin.deposits.approve', $deposit));

        $response->assertSessionHas('error', 'deposit-already-processed');
        $this->assertSame('200.00000000', $wallet->fresh()->balance);
        $this->assertSame('rejected', $deposit->fresh()->status);
        $this->assertSame(0, AdjustmentHistory::count());
    }

    public function test_transaction_failure_rolls_back_wallet_and_deposit(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'user']);
        $wallet = Wallet::create(['user_id' => $user->id, 'asset' => 'USDT', 'balance' => '50.00000000']);
        $deposit = $this->makeDeposit($user, ['amount' => '100.00000000']);

        AdjustmentHistory::creating(function () {
            throw new RuntimeException('Simulated failure during approval.');
        });

        try {
            $response = $this->actingAs($admin)->post(route('admin.deposits.approve', $deposit));

            $response->assertSessionHas('error', 'deposit-approve-failed');
            $this->assertSame('50.00000000', $wallet->fresh()->balance);
            $this->assertSame('pending', $deposit->fresh()->status);
            $this->assertNull($deposit->fresh()->reviewed_by);
            $this->assertSame(0, AdjustmentHistory::count());
        } finally {
            AdjustmentHistory::flushEventListeners();
        }
    }

    public function test_amount_asset_and_user_are_never_trusted_from_request(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'user']);
        $attacker = User::factory()->create(['role' => 'user']);
        $wallet = Wallet::create(['user_id' => $user->id, 'asset' => 'USDT', 'balance' => '0.00000000']);
        $attackerWallet = Wallet::create(['user_id' => $attacker->id, 'asset' => 'BTC', 'balance' => '0.00000000']);
        $deposit = $this->makeDeposit($user, ['amount' => '10.00000000', 'asset' => 'USDT']);

        // Even if a malicious request tries to override the financial values,
        // the controller must only ever use the values already stored on the
        // Deposit row — it takes no input at all beyond the route-bound ID.
        $this->actingAs($admin)->post(route('admin.deposits.approve', $deposit), [
            'amount' => '999999.00000000',
            'asset' => 'BTC',
            'user_id' => $attacker->id,
            'wallet_id' => $attackerWallet->id,
        ]);

        $this->assertSame('10.00000000', $wallet->fresh()->balance);
        $this->assertSame('0.00000000', $attackerWallet->fresh()->balance);

        $history = AdjustmentHistory::first();
        $this->assertSame($user->id, $history->user_id);
        $this->assertSame('USDT', $history->asset);
        $this->assertSame('10.00000000', $history->amount);
    }

    public function test_guest_is_denied(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $deposit = $this->makeDeposit($user);

        $response = $this->post(route('admin.deposits.approve', $deposit));

        $response->assertRedirect(route('login'));
        $this->assertSame('pending', $deposit->fresh()->status);
    }

    public function test_regular_user_is_denied(): void
    {
        $regular = User::factory()->create(['role' => 'user']);
        $targetUser = User::factory()->create(['role' => 'user']);
        $wallet = Wallet::create(['user_id' => $targetUser->id, 'asset' => 'USDT', 'balance' => '0.00000000']);
        $deposit = $this->makeDeposit($targetUser);

        $response = $this->actingAs($regular)->post(route('admin.deposits.approve', $deposit));

        $response->assertForbidden();
        $this->assertSame('pending', $deposit->fresh()->status);
        $this->assertSame('0.00000000', $wallet->fresh()->balance);
    }

    public function test_nonexistent_deposit_returns_404(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->post('/admin/deposits/999999/approve');

        $response->assertNotFound();
    }

    public function test_approve_route_only_accepts_post(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'user']);
        $deposit = $this->makeDeposit($user);

        $response = $this->actingAs($admin)->get(route('admin.deposits.approve', $deposit));

        $response->assertMethodNotAllowed();
        $this->assertSame('pending', $deposit->fresh()->status);
    }
}
