<?php

namespace Tests\Feature\Admin;

use App\Models\AdjustmentHistory;
use App\Models\Deposit;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * STEP 4.8 audit: fills the coverage gaps left after Steps 4.5–4.7 that are
 * specific to cross-cutting security/integrity concerns (approve-vs-reject
 * race, reviewer spoofing, injection attempts) rather than single-action
 * behavior already covered by DepositApproveTest/DepositRejectTest/DepositFilterTest.
 */
class DepositSecurityIntegrityTest extends TestCase
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

    // STEP 16 / TEST 17 — Approve vs Reject race: only one final state wins,
    // and the loser must be a safe no-op (no wallet mutation, no status flip).
    public function test_approve_then_reject_only_the_first_request_wins(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'user']);
        $wallet = Wallet::create(['user_id' => $user->id, 'asset' => 'USDT', 'balance' => '0.00000000']);
        $deposit = $this->makeDeposit($user, ['amount' => '100.00000000']);

        $approve = $this->actingAs($admin)->post(route('admin.deposits.approve', $deposit));
        $approve->assertSessionHas('status', 'deposit-approved');

        $reject = $this->actingAs($admin)->post(route('admin.deposits.reject', $deposit), [
            'rejection_reason' => 'Too late, already approved.',
        ]);
        $reject->assertSessionHas('error', 'deposit-already-processed');

        $fresh = $deposit->fresh();
        $this->assertSame('approved', $fresh->status);
        $this->assertNull($fresh->rejection_reason);
        $this->assertSame('100.00000000', $wallet->fresh()->balance);
        $this->assertSame(1, AdjustmentHistory::count());
    }

    public function test_reject_then_approve_only_the_first_request_wins(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'user']);
        $wallet = Wallet::create(['user_id' => $user->id, 'asset' => 'USDT', 'balance' => '0.00000000']);
        $deposit = $this->makeDeposit($user, ['amount' => '100.00000000']);

        $reject = $this->actingAs($admin)->post(route('admin.deposits.reject', $deposit), [
            'rejection_reason' => 'Invalid proof.',
        ]);
        $reject->assertSessionHas('status', 'deposit-rejected');

        $approve = $this->actingAs($admin)->post(route('admin.deposits.approve', $deposit));
        $approve->assertSessionHas('error', 'deposit-already-processed');

        $fresh = $deposit->fresh();
        $this->assertSame('rejected', $fresh->status);
        $this->assertSame('Invalid proof.', $fresh->rejection_reason);
        $this->assertSame('0.00000000', $wallet->fresh()->balance);
        $this->assertSame(0, AdjustmentHistory::count());
    }

    // STEP 17 / TEST 21 — reviewer must come from the authenticated admin, never the request body.
    public function test_reviewed_by_cannot_be_spoofed_on_approve(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $impersonated = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'user']);
        $deposit = $this->makeDeposit($user);

        $this->actingAs($admin)->post(route('admin.deposits.approve', $deposit), [
            'reviewed_by' => $impersonated->id,
        ]);

        $this->assertSame($admin->id, $deposit->fresh()->reviewed_by);
    }

    public function test_reviewed_by_cannot_be_spoofed_on_reject(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $impersonated = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'user']);
        $deposit = $this->makeDeposit($user);

        $this->actingAs($admin)->post(route('admin.deposits.reject', $deposit), [
            'rejection_reason' => 'Invalid proof.',
            'reviewed_by' => $impersonated->id,
        ]);

        $this->assertSame($admin->id, $deposit->fresh()->reviewed_by);
    }

    // STEP 20 — status is never taken from request input on either action.
    public function test_status_field_in_request_body_is_ignored_on_reject(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'user']);
        $deposit = $this->makeDeposit($user);

        $this->actingAs($admin)->post(route('admin.deposits.reject', $deposit), [
            'rejection_reason' => 'Invalid proof.',
            'status' => 'approved',
        ]);

        $this->assertSame('rejected', $deposit->fresh()->status);
    }

    public function test_status_field_in_request_body_is_ignored_on_approve(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'user']);
        $deposit = $this->makeDeposit($user);

        $this->actingAs($admin)->post(route('admin.deposits.approve', $deposit), [
            'status' => 'rejected',
        ]);

        $this->assertSame('approved', $deposit->fresh()->status);
    }

    // STEP 25 / TEST 23-24 — search/filter must be parameter-bound, not concatenated.
    public function test_search_with_sql_metacharacters_does_not_error_or_leak_data(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'user']);
        $this->makeDeposit($user);

        $payloads = [
            "' OR '1'='1",
            "'; DROP TABLE deposits; --",
            "1' OR status != ''--",
            '%',
        ];

        foreach ($payloads as $payload) {
            $response = $this->actingAs($admin)->get(route('admin.deposits.index', ['search' => $payload]));
            $response->assertOk();
        }

        // The deposits table must still exist and be queryable after every attempt.
        $this->assertSame(1, Deposit::count());
    }

    public function test_filter_with_sql_metacharacters_does_not_error_or_leak_data(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'user']);
        $this->makeDeposit($user, ['status' => 'pending']);

        $response = $this->actingAs($admin)->get(route('admin.deposits.index', [
            'status' => "pending' OR '1'='1",
            'method' => "bank_transfer'; DROP TABLE deposits; --",
            'asset' => "USDT' OR '1'='1",
        ]));

        $response->assertOk();
        // None of the malicious values exactly match a real column value, so
        // the (safely parameter-bound) exact-match filters return zero rows
        // instead of the injected condition ever reaching raw SQL.
        $this->assertTrue($response->viewData('deposits')->isEmpty());
        $this->assertSame(1, Deposit::count());
    }

    // STEP 34/35 — explicit before/after financial integrity check.
    public function test_financial_integrity_before_and_after_approve(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'user']);
        $wallet = Wallet::create(['user_id' => $user->id, 'asset' => 'USDT', 'balance' => '500.00000000']);
        $deposit = $this->makeDeposit($user, ['amount' => '100.00000000']);

        $this->assertSame('500.00000000', $wallet->fresh()->balance);

        $this->actingAs($admin)->post(route('admin.deposits.approve', $deposit));

        $this->assertSame('600.00000000', $wallet->fresh()->balance);

        // A second attempt (refresh/retry) must not increment it again.
        $this->actingAs($admin)->post(route('admin.deposits.approve', $deposit));
        $this->assertSame('600.00000000', $wallet->fresh()->balance);
    }

    public function test_financial_integrity_before_and_after_reject(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'user']);
        $wallet = Wallet::create(['user_id' => $user->id, 'asset' => 'USDT', 'balance' => '500.00000000']);
        $deposit = $this->makeDeposit($user, ['amount' => '100.00000000']);

        $this->actingAs($admin)->post(route('admin.deposits.reject', $deposit), [
            'rejection_reason' => 'Invalid proof.',
        ]);

        $this->assertSame('500.00000000', $wallet->fresh()->balance);
    }

    // STEP 24 — deposit history/search/filter is strictly read-only.
    public function test_index_request_never_mutates_any_deposit(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'user']);
        $pending = $this->makeDeposit($user, ['status' => 'pending']);

        for ($i = 0; $i < 3; $i++) {
            $this->actingAs($admin)->get(route('admin.deposits.index', [
                'search' => 'x', 'status' => 'pending', 'method' => 'bank_transfer', 'asset' => 'USDT',
            ]))->assertOk();
        }

        $fresh = $pending->fresh();
        $this->assertSame('pending', $fresh->status);
        $this->assertNull($fresh->reviewed_by);
        $this->assertNull($fresh->reviewed_at);
        $this->assertNull($fresh->rejection_reason);
    }
}
