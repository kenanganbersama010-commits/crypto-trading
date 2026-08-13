<?php

namespace Tests\Feature\Admin;

use App\Models\AdjustmentHistory;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdjustmentHistoryViewTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_adjustment_history_page(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'user']);
        $wallet = Wallet::create(['user_id' => $user->id, 'asset' => 'USDT', 'balance' => '600.00000000']);

        AdjustmentHistory::create([
            'admin_id' => $admin->id,
            'user_id' => $user->id,
            'wallet_id' => $wallet->id,
            'asset' => 'USDT',
            'type' => 'add',
            'amount' => '100.00000000',
            'balance_before' => '500.00000000',
            'balance_after' => '600.00000000',
            'reason' => 'Bonus',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.adjustment-history.index'));

        $response->assertOk();
        $response->assertSee('Riwayat Penyesuaian Saldo');
        $response->assertSee($user->name);
        $response->assertSee('Tambah');
        $response->assertSee('Bonus');
    }

    public function test_adjustment_history_page_shows_empty_state(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get(route('admin.adjustment-history.index'));

        $response->assertOk();
        $response->assertSee('Riwayat penyesuaian tidak ditemukan.');
    }

    public function test_guest_is_denied_access_to_adjustment_history(): void
    {
        $response = $this->get(route('admin.adjustment-history.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_regular_user_is_denied_access_to_adjustment_history(): void
    {
        $user = User::factory()->create(['role' => 'user']);

        $response = $this->actingAs($user)->get(route('admin.adjustment-history.index'));

        $response->assertForbidden();
    }

    public function test_adjustment_history_page_eager_loads_relations_without_n_plus_one(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $seed = function (int $count) use ($admin) {
            foreach (range(1, $count) as $i) {
                $user = User::factory()->create(['role' => 'user']);
                $wallet = Wallet::create(['user_id' => $user->id, 'asset' => 'USDT', 'balance' => '100.00000000']);

                AdjustmentHistory::create([
                    'admin_id' => $admin->id,
                    'user_id' => $user->id,
                    'wallet_id' => $wallet->id,
                    'asset' => 'USDT',
                    'type' => 'add',
                    'amount' => '10.00000000',
                    'balance_before' => '90.00000000',
                    'balance_after' => '100.00000000',
                    'reason' => 'Test',
                ]);
            }
        };

        $countQueries = function () use ($admin) {
            $queryCount = 0;
            $listener = function () use (&$queryCount) {
                $queryCount++;
            };

            \Illuminate\Support\Facades\DB::listen($listener);
            $this->actingAs($admin)->get(route('admin.adjustment-history.index'))->assertOk();

            return $queryCount;
        };

        // Query count is a handful of fixed-cost aggregate/eager-load queries
        // (page items, user eager-load, admin eager-load, paginator total count,
        // add-count, deduct-count, distinct asset list) — it must NOT scale with
        // the number of history rows, which is what an N+1 would look like.
        $seed(3);
        $queriesWithFewRows = $countQueries();

        $seed(5);
        $queriesWithMoreRows = $countQueries();

        $this->assertSame($queriesWithFewRows, $queriesWithMoreRows);
    }
}
