<?php

namespace Tests\Feature\Admin;

use App\Models\Deposit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DepositRouteTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_deposits_index(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'user']);
        Deposit::create(['user_id' => $user->id, 'method' => 'bank_transfer', 'asset' => 'USDT', 'amount' => '100']);

        $response = $this->actingAs($admin)->get(route('admin.deposits.index'));

        $response->assertOk();
        $response->assertSee('Deposit');
        $response->assertSee($user->name);
    }

    public function test_deposits_index_shows_empty_state(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get(route('admin.deposits.index'));

        $response->assertOk();
        $response->assertSee('Data deposit tidak ditemukan.');
    }

    public function test_deposits_index_orders_newest_first(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'user']);

        $older = Deposit::create(['user_id' => $user->id, 'method' => 'bank_transfer', 'asset' => 'USDT', 'amount' => '100']);
        $older->forceFill(['created_at' => now()->subDay()])->save();

        $newer = Deposit::create(['user_id' => $user->id, 'method' => 'crypto', 'asset' => 'BTC', 'amount' => '0.01']);

        $response = $this->actingAs($admin)->get(route('admin.deposits.index'));

        $response->assertOk();
        $ids = $response->viewData('deposits')->pluck('id')->all();
        $this->assertSame([$newer->id, $older->id], $ids);
    }

    public function test_deposits_index_eager_loads_user_without_n_plus_one(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        foreach (range(1, 5) as $i) {
            $user = User::factory()->create(['role' => 'user']);
            Deposit::create(['user_id' => $user->id, 'method' => 'bank_transfer', 'asset' => 'USDT', 'amount' => '100']);
        }

        $queryCount = 0;
        DB::listen(function () use (&$queryCount) {
            $queryCount++;
        });

        $this->actingAs($admin)->get(route('admin.deposits.index'))->assertOk();

        // Fixed cost regardless of row count: count + paginated select + batched user
        // eager load + the two distinct method/asset lookups for filter dropdowns.
        $this->assertLessThanOrEqual(5, $queryCount);
    }

    public function test_admin_can_view_deposit_detail(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'user']);
        $deposit = Deposit::create(['user_id' => $user->id, 'method' => 'bank_transfer', 'asset' => 'USDT', 'amount' => '250']);

        $response = $this->actingAs($admin)->get(route('admin.deposits.show', $deposit));

        $response->assertOk();
        $response->assertSee($user->name);
        // Method is formatted for display ("Bank Transfer"), the raw DB value is not shown verbatim.
        $response->assertSee('Bank Transfer');
        $response->assertSee('USDT');
    }

    public function test_nonexistent_deposit_returns_404_not_500(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get('/admin/deposits/999999');

        $response->assertNotFound();
    }

    public function test_guest_is_denied_index(): void
    {
        $response = $this->get(route('admin.deposits.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_guest_is_denied_show(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $deposit = Deposit::create(['user_id' => $user->id, 'method' => 'bank_transfer', 'asset' => 'USDT', 'amount' => '100']);

        $response = $this->get(route('admin.deposits.show', $deposit));

        $response->assertRedirect(route('login'));
    }

    public function test_regular_user_is_denied_index(): void
    {
        $regular = User::factory()->create(['role' => 'user']);

        $response = $this->actingAs($regular)->get(route('admin.deposits.index'));

        $response->assertForbidden();
    }

    public function test_regular_user_is_denied_show(): void
    {
        $regular = User::factory()->create(['role' => 'user']);
        $targetUser = User::factory()->create(['role' => 'user']);
        $deposit = Deposit::create(['user_id' => $targetUser->id, 'method' => 'bank_transfer', 'asset' => 'USDT', 'amount' => '100']);

        $response = $this->actingAs($regular)->get(route('admin.deposits.show', $deposit));

        $response->assertForbidden();
    }

    public function test_deposit_routes_are_read_only(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'user']);
        $deposit = Deposit::create(['user_id' => $user->id, 'method' => 'bank_transfer', 'asset' => 'USDT', 'amount' => '100']);

        // No approve/reject/mutation endpoints exist yet — every write verb on
        // these URIs must be rejected at the router level (405), not silently
        // routed to some action.
        $this->actingAs($admin)->post(route('admin.deposits.index'))->assertMethodNotAllowed();
        $this->actingAs($admin)->post(route('admin.deposits.show', $deposit))->assertMethodNotAllowed();
        $this->actingAs($admin)->put(route('admin.deposits.show', $deposit))->assertMethodNotAllowed();
        $this->actingAs($admin)->patch(route('admin.deposits.show', $deposit))->assertMethodNotAllowed();
        $this->actingAs($admin)->delete(route('admin.deposits.show', $deposit))->assertMethodNotAllowed();

        $this->assertSame('pending', $deposit->fresh()->status);
    }
}
