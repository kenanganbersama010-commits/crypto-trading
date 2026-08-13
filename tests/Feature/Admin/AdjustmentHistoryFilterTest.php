<?php

namespace Tests\Feature\Admin;

use App\Models\AdjustmentHistory;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AdjustmentHistoryFilterTest extends TestCase
{
    use RefreshDatabase;

    private function makeAdjustment(User $admin, User $user, Wallet $wallet, string $type, string $asset, string $reason, string $createdAt): AdjustmentHistory
    {
        $adjustment = AdjustmentHistory::create([
            'admin_id' => $admin->id,
            'user_id' => $user->id,
            'wallet_id' => $wallet->id,
            'asset' => $asset,
            'type' => $type,
            'amount' => '10.00000000',
            'balance_before' => '90.00000000',
            'balance_after' => '100.00000000',
            'reason' => $reason,
        ]);

        // 'created_at' is intentionally not in AdjustmentHistory::$fillable (audit
        // trail integrity), so it's set here via forceFill purely to control test
        // ordering/date-range fixtures — never do this in application code.
        $adjustment->forceFill(['created_at' => $createdAt])->save();

        return $adjustment;
    }

    public function test_no_filter_returns_all_history_newest_first(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $sultan = User::factory()->create(['role' => 'user', 'name' => 'Sultan']);
        $budi = User::factory()->create(['role' => 'user', 'name' => 'Budi']);
        $walletSultan = Wallet::create(['user_id' => $sultan->id, 'asset' => 'USDT', 'balance' => '100']);
        $walletBudi = Wallet::create(['user_id' => $budi->id, 'asset' => 'BTC', 'balance' => '1']);

        $older = $this->makeAdjustment($admin, $sultan, $walletSultan, 'add', 'USDT', 'first', '2026-08-01 10:00:00');
        $newer = $this->makeAdjustment($admin, $budi, $walletBudi, 'deduct', 'BTC', 'second', '2026-08-05 10:00:00');

        $response = $this->actingAs($admin)->get(route('admin.adjustment-history.index'));

        $response->assertOk();
        $ids = $response->viewData('adjustments')->pluck('id')->all();
        $this->assertSame([$newer->id, $older->id], $ids);
    }

    public function test_search_by_nickname_filters_to_that_user_only(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $sultan = User::factory()->create(['role' => 'user', 'name' => 'Sultan']);
        $budi = User::factory()->create(['role' => 'user', 'name' => 'Budi']);
        $walletSultan = Wallet::create(['user_id' => $sultan->id, 'asset' => 'USDT', 'balance' => '100']);
        $walletBudi = Wallet::create(['user_id' => $budi->id, 'asset' => 'USDT', 'balance' => '100']);

        $this->makeAdjustment($admin, $sultan, $walletSultan, 'add', 'USDT', 'x', now());
        $this->makeAdjustment($admin, $budi, $walletBudi, 'add', 'USDT', 'y', now());

        $response = $this->actingAs($admin)->get(route('admin.adjustment-history.index', ['search' => 'Sultan']));

        $response->assertOk();
        $adjustments = $response->viewData('adjustments');
        $this->assertCount(1, $adjustments);
        $this->assertSame('Sultan', $adjustments->first()->user->name);
    }

    public function test_filter_by_add_type(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'user']);
        $wallet = Wallet::create(['user_id' => $user->id, 'asset' => 'USDT', 'balance' => '100']);

        $this->makeAdjustment($admin, $user, $wallet, 'add', 'USDT', 'x', now());
        $this->makeAdjustment($admin, $user, $wallet, 'deduct', 'USDT', 'y', now());

        $response = $this->actingAs($admin)->get(route('admin.adjustment-history.index', ['type' => 'add']));

        $adjustments = $response->viewData('adjustments');
        $this->assertCount(1, $adjustments);
        $this->assertSame('add', $adjustments->first()->type);
    }

    public function test_filter_by_deduct_type(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'user']);
        $wallet = Wallet::create(['user_id' => $user->id, 'asset' => 'USDT', 'balance' => '100']);

        $this->makeAdjustment($admin, $user, $wallet, 'add', 'USDT', 'x', now());
        $this->makeAdjustment($admin, $user, $wallet, 'deduct', 'USDT', 'y', now());

        $response = $this->actingAs($admin)->get(route('admin.adjustment-history.index', ['type' => 'deduct']));

        $adjustments = $response->viewData('adjustments');
        $this->assertCount(1, $adjustments);
        $this->assertSame('deduct', $adjustments->first()->type);
    }

    public function test_filter_by_asset(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'user']);
        $walletUsdt = Wallet::create(['user_id' => $user->id, 'asset' => 'USDT', 'balance' => '100']);
        $walletBtc = Wallet::create(['user_id' => $user->id, 'asset' => 'BTC', 'balance' => '1']);

        $this->makeAdjustment($admin, $user, $walletUsdt, 'add', 'USDT', 'x', now());
        $this->makeAdjustment($admin, $user, $walletBtc, 'add', 'BTC', 'y', now());

        $response = $this->actingAs($admin)->get(route('admin.adjustment-history.index', ['asset' => 'BTC']));

        $adjustments = $response->viewData('adjustments');
        $this->assertCount(1, $adjustments);
        $this->assertSame('BTC', $adjustments->first()->asset);
    }

    public function test_filter_by_date_range(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'user']);
        $wallet = Wallet::create(['user_id' => $user->id, 'asset' => 'USDT', 'balance' => '100']);

        $inRange = $this->makeAdjustment($admin, $user, $wallet, 'add', 'USDT', 'in', '2026-08-05 12:00:00');
        $this->makeAdjustment($admin, $user, $wallet, 'add', 'USDT', 'out', '2026-07-01 12:00:00');

        $response = $this->actingAs($admin)->get(route('admin.adjustment-history.index', [
            'from_date' => '2026-08-01',
            'to_date' => '2026-08-10',
        ]));

        $adjustments = $response->viewData('adjustments');
        $this->assertCount(1, $adjustments);
        $this->assertSame($inRange->id, $adjustments->first()->id);
    }

    public function test_combined_filters_work_together(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $sultan = User::factory()->create(['role' => 'user', 'name' => 'Sultan']);
        $wallet = Wallet::create(['user_id' => $sultan->id, 'asset' => 'USDT', 'balance' => '100']);

        $match = $this->makeAdjustment($admin, $sultan, $wallet, 'add', 'USDT', 'match', '2026-08-05 12:00:00');
        $this->makeAdjustment($admin, $sultan, $wallet, 'deduct', 'USDT', 'wrong type', '2026-08-05 12:00:00');
        $this->makeAdjustment($admin, $sultan, $wallet, 'add', 'USDT', 'wrong date', '2026-07-01 12:00:00');

        $response = $this->actingAs($admin)->get(route('admin.adjustment-history.index', [
            'search' => 'Sultan',
            'asset' => 'USDT',
            'type' => 'add',
            'from_date' => '2026-08-01',
            'to_date' => '2026-08-10',
        ]));

        $adjustments = $response->viewData('adjustments');
        $this->assertCount(1, $adjustments);
        $this->assertSame($match->id, $adjustments->first()->id);
    }

    public function test_pagination_preserves_active_filters_on_page_two(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'user']);
        $wallet = Wallet::create(['user_id' => $user->id, 'asset' => 'USDT', 'balance' => '100']);

        foreach (range(1, 20) as $i) {
            $this->makeAdjustment($admin, $user, $wallet, 'add', 'USDT', "reason-{$i}", now()->subMinutes($i));
        }
        // A deduct row that must never leak into the type=add filtered pages.
        $this->makeAdjustment($admin, $user, $wallet, 'deduct', 'USDT', 'noise', now());

        $response = $this->actingAs($admin)->get(route('admin.adjustment-history.index', ['type' => 'add', 'page' => 2]));

        $response->assertOk();
        $adjustments = $response->viewData('adjustments');
        $this->assertTrue($adjustments->currentPage() === 2);
        $this->assertTrue($adjustments->every(fn ($a) => $a->type === 'add'));
        $response->assertSee('type=add', false);
    }

    public function test_reset_clears_all_filters(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'user']);
        $wallet = Wallet::create(['user_id' => $user->id, 'asset' => 'USDT', 'balance' => '100']);

        $this->makeAdjustment($admin, $user, $wallet, 'add', 'USDT', 'x', now());
        $this->makeAdjustment($admin, $user, $wallet, 'deduct', 'USDT', 'y', now());

        $response = $this->actingAs($admin)->get(route('admin.adjustment-history.index'));

        $response->assertOk();
        $this->assertCount(2, $response->viewData('adjustments'));
    }

    public function test_no_results_shows_filtered_empty_state(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'user']);
        $wallet = Wallet::create(['user_id' => $user->id, 'asset' => 'USDT', 'balance' => '100']);

        $this->makeAdjustment($admin, $user, $wallet, 'add', 'USDT', 'x', now());

        $response = $this->actingAs($admin)->get(route('admin.adjustment-history.index', ['search' => 'NonExistentUserXYZ']));

        $response->assertOk();
        $response->assertSee('Riwayat penyesuaian tidak ditemukan untuk filter yang dipilih.');
    }

    public function test_inverted_date_range_is_ignored_not_a_query_error(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'user']);
        $wallet = Wallet::create(['user_id' => $user->id, 'asset' => 'USDT', 'balance' => '100']);

        $this->makeAdjustment($admin, $user, $wallet, 'add', 'USDT', 'x', now());

        $response = $this->actingAs($admin)->get(route('admin.adjustment-history.index', [
            'from_date' => '2026-08-10',
            'to_date' => '2026-08-01',
        ]));

        $response->assertOk();
        $response->assertSee('Filter tanggal tidak valid.');
        $this->assertCount(1, $response->viewData('adjustments'));
    }

    public function test_malformed_date_string_is_rejected_with_validation_message_not_a_query_error(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'user']);
        $wallet = Wallet::create(['user_id' => $user->id, 'asset' => 'USDT', 'balance' => '100']);

        $this->makeAdjustment($admin, $user, $wallet, 'add', 'USDT', 'x', now());

        $response = $this->actingAs($admin)->get(route('admin.adjustment-history.index', ['from_date' => 'not-a-date']));

        $response->assertOk();
        $response->assertSee('Filter tanggal tidak valid.');
        // The malformed date must not silently narrow results without feedback.
        $this->assertCount(1, $response->viewData('adjustments'));
    }

    public function test_invalid_type_parameter_is_ignored_not_a_query_error(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'user']);
        $wallet = Wallet::create(['user_id' => $user->id, 'asset' => 'USDT', 'balance' => '100']);

        $this->makeAdjustment($admin, $user, $wallet, 'add', 'USDT', 'x', now());

        $response = $this->actingAs($admin)->get(route('admin.adjustment-history.index', ['type' => 'DROP TABLE users']));

        $response->assertOk();
        $this->assertCount(1, $response->viewData('adjustments'));
    }

    public function test_pagination_uses_eloquent_paginator_not_full_get(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'user']);
        $wallet = Wallet::create(['user_id' => $user->id, 'asset' => 'USDT', 'balance' => '100']);

        foreach (range(1, 16) as $i) {
            $this->makeAdjustment($admin, $user, $wallet, 'add', 'USDT', "reason-{$i}", now()->subMinutes($i));
        }

        $response = $this->actingAs($admin)->get(route('admin.adjustment-history.index'));

        $adjustments = $response->viewData('adjustments');
        $this->assertInstanceOf(\Illuminate\Pagination\LengthAwarePaginator::class, $adjustments);
        $this->assertCount(15, $adjustments);
        $this->assertSame(16, $adjustments->total());
    }

    public function test_search_does_not_cause_n_plus_one(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        foreach (range(1, 5) as $i) {
            $user = User::factory()->create(['role' => 'user']);
            $wallet = Wallet::create(['user_id' => $user->id, 'asset' => 'USDT', 'balance' => '100']);
            $this->makeAdjustment($admin, $user, $wallet, 'add', 'USDT', "reason-{$i}", now()->subMinutes($i));
        }

        $queryCount = 0;
        DB::listen(function () use (&$queryCount) {
            $queryCount++;
        });

        $this->actingAs($admin)->get(route('admin.adjustment-history.index'))->assertOk();

        $this->assertLessThanOrEqual(8, $queryCount);
    }
}
