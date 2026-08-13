<?php

namespace Tests\Feature\Admin;

use App\Models\AdjustmentHistory;
use App\Models\Deposit;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class DepositRejectTest extends TestCase
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

    public function test_normal_reject_updates_deposit_and_does_not_touch_wallet(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'user']);
        $wallet = Wallet::create(['user_id' => $user->id, 'asset' => 'USDT', 'balance' => '50.00000000']);
        $deposit = $this->makeDeposit($user);

        $response = $this->actingAs($admin)->post(route('admin.deposits.reject', $deposit), [
            'rejection_reason' => 'Payment proof is invalid.',
        ]);

        $response->assertRedirect(route('admin.deposits.show', $deposit));
        $response->assertSessionHas('status', 'deposit-rejected');

        $fresh = $deposit->fresh();
        $this->assertSame('rejected', $fresh->status);
        $this->assertSame($admin->id, $fresh->reviewed_by);
        $this->assertNotNull($fresh->reviewed_at);
        $this->assertSame('Payment proof is invalid.', $fresh->rejection_reason);

        $this->assertSame('50.00000000', $wallet->fresh()->balance);
        $this->assertSame(0, AdjustmentHistory::count());
    }

    public function test_empty_reason_fails_validation_and_leaves_deposit_pending(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'user']);
        $wallet = Wallet::create(['user_id' => $user->id, 'asset' => 'USDT', 'balance' => '50.00000000']);
        $deposit = $this->makeDeposit($user);

        $response = $this->actingAs($admin)->post(route('admin.deposits.reject', $deposit), [
            'rejection_reason' => '',
        ]);

        $response->assertSessionHasErrorsIn('rejectDeposit', ['rejection_reason']);

        $this->assertSame('pending', $deposit->fresh()->status);
        $this->assertNull($deposit->fresh()->reviewed_by);
        $this->assertSame('50.00000000', $wallet->fresh()->balance);
    }

    public function test_reason_over_max_length_fails_validation(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'user']);
        $deposit = $this->makeDeposit($user);

        $response = $this->actingAs($admin)->post(route('admin.deposits.reject', $deposit), [
            'rejection_reason' => str_repeat('a', 501),
        ]);

        $response->assertSessionHasErrorsIn('rejectDeposit', ['rejection_reason']);
        $this->assertSame('pending', $deposit->fresh()->status);
    }

    public function test_double_click_only_rejects_once(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'user']);
        $deposit = $this->makeDeposit($user);

        $first = $this->actingAs($admin)->post(route('admin.deposits.reject', $deposit), [
            'rejection_reason' => 'Invalid proof.',
        ]);
        $first->assertSessionHas('status', 'deposit-rejected');

        $second = $this->actingAs($admin)->post(route('admin.deposits.reject', $deposit), [
            'rejection_reason' => 'Second attempt.',
        ]);
        $second->assertSessionHas('error', 'deposit-already-processed');

        $this->assertSame('Invalid proof.', $deposit->fresh()->rejection_reason);
    }

    public function test_refresh_after_reject_does_not_reprocess(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'user']);
        $deposit = $this->makeDeposit($user);

        $this->actingAs($admin)->post(route('admin.deposits.reject', $deposit), [
            'rejection_reason' => 'Invalid proof.',
        ]);

        $this->actingAs($admin)->get(route('admin.deposits.show', $deposit))->assertOk();

        $this->assertSame('rejected', $deposit->fresh()->status);
        $this->assertSame('Invalid proof.', $deposit->fresh()->rejection_reason);
    }

    public function test_already_approved_deposit_cannot_be_rejected(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $reviewer = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'user']);
        $wallet = Wallet::create(['user_id' => $user->id, 'asset' => 'USDT', 'balance' => '200.00000000']);
        $deposit = $this->makeDeposit($user, [
            'status' => 'approved',
            'reviewed_by' => $reviewer->id,
            'reviewed_at' => now(),
        ]);

        $response = $this->actingAs($admin)->post(route('admin.deposits.reject', $deposit), [
            'rejection_reason' => 'Trying to reject an approved deposit.',
        ]);

        $response->assertSessionHas('error', 'deposit-already-processed');
        $this->assertSame('approved', $deposit->fresh()->status);
        $this->assertSame($reviewer->id, $deposit->fresh()->reviewed_by);
        $this->assertNull($deposit->fresh()->rejection_reason);
        $this->assertSame('200.00000000', $wallet->fresh()->balance);
    }

    public function test_already_rejected_deposit_cannot_be_rejected_again(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $reviewer = User::factory()->create(['role' => 'admin']);
        $reviewedAt = now()->subHour();
        $user = User::factory()->create(['role' => 'user']);
        $deposit = $this->makeDeposit($user, [
            'status' => 'rejected',
            'reviewed_by' => $reviewer->id,
            'reviewed_at' => $reviewedAt,
            'rejection_reason' => 'Original reason.',
        ]);

        $response = $this->actingAs($admin)->post(route('admin.deposits.reject', $deposit), [
            'rejection_reason' => 'New reason attempt.',
        ]);

        $response->assertSessionHas('error', 'deposit-already-processed');
        $this->assertSame('rejected', $deposit->fresh()->status);
        $this->assertSame('Original reason.', $deposit->fresh()->rejection_reason);
        $this->assertSame($reviewer->id, $deposit->fresh()->reviewed_by);
        $this->assertSame($reviewedAt->format('Y-m-d H:i:s'), $deposit->fresh()->reviewed_at->format('Y-m-d H:i:s'));
    }

    public function test_transaction_failure_rolls_back_deposit(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'user']);
        $deposit = $this->makeDeposit($user);

        Deposit::updating(function () {
            throw new RuntimeException('Simulated failure during rejection.');
        });

        try {
            $response = $this->actingAs($admin)->post(route('admin.deposits.reject', $deposit), [
                'rejection_reason' => 'Invalid proof.',
            ]);

            $response->assertSessionHas('error', 'deposit-reject-failed');
            $this->assertSame('pending', $deposit->fresh()->status);
            $this->assertNull($deposit->fresh()->reviewed_by);
            $this->assertNull($deposit->fresh()->rejection_reason);
        } finally {
            Deposit::flushEventListeners();
        }
    }

    public function test_amount_asset_and_user_are_never_trusted_from_request(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'user']);
        $attacker = User::factory()->create(['role' => 'user']);
        $deposit = $this->makeDeposit($user, ['amount' => '10.00000000', 'asset' => 'USDT']);

        $this->actingAs($admin)->post(route('admin.deposits.reject', $deposit), [
            'rejection_reason' => 'Invalid proof.',
            'amount' => '999999.00000000',
            'asset' => 'BTC',
            'user_id' => $attacker->id,
        ]);

        $fresh = $deposit->fresh();
        $this->assertSame('10.00000000', $fresh->amount);
        $this->assertSame('USDT', $fresh->asset);
        $this->assertSame($user->id, $fresh->user_id);
    }

    public function test_guest_is_denied(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $deposit = $this->makeDeposit($user);

        $response = $this->post(route('admin.deposits.reject', $deposit), [
            'rejection_reason' => 'Invalid proof.',
        ]);

        $response->assertRedirect(route('login'));
        $this->assertSame('pending', $deposit->fresh()->status);
    }

    public function test_regular_user_is_denied(): void
    {
        $regular = User::factory()->create(['role' => 'user']);
        $targetUser = User::factory()->create(['role' => 'user']);
        $deposit = $this->makeDeposit($targetUser);

        $response = $this->actingAs($regular)->post(route('admin.deposits.reject', $deposit), [
            'rejection_reason' => 'Invalid proof.',
        ]);

        $response->assertForbidden();
        $this->assertSame('pending', $deposit->fresh()->status);
    }

    public function test_nonexistent_deposit_returns_404(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->post('/admin/deposits/999999/reject', [
            'rejection_reason' => 'Invalid proof.',
        ]);

        $response->assertNotFound();
    }

    public function test_reject_route_only_accepts_post(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'user']);
        $deposit = $this->makeDeposit($user);

        $response = $this->actingAs($admin)->get(route('admin.deposits.reject', $deposit));

        $response->assertMethodNotAllowed();
        $this->assertSame('pending', $deposit->fresh()->status);
    }
}
