<?php

namespace Tests\Feature\Admin;

use App\Models\Deposit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DepositFilterTest extends TestCase
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

    public function test_status_filter_returns_only_matching_status(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'user']);
        $pending = $this->makeDeposit($user, ['status' => 'pending']);
        $approved = $this->makeDeposit($user, ['status' => 'approved', 'reviewed_by' => $admin->id, 'reviewed_at' => now()]);
        $rejected = $this->makeDeposit($user, ['status' => 'rejected', 'reviewed_by' => $admin->id, 'reviewed_at' => now()]);

        $response = $this->actingAs($admin)->get(route('admin.deposits.index', ['status' => 'approved']));

        $response->assertOk();
        $ids = $response->viewData('deposits')->pluck('id')->all();
        $this->assertSame([$approved->id], $ids);
    }

    public function test_method_filter_returns_only_matching_method(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'user']);
        $bank = $this->makeDeposit($user, ['method' => 'bank_transfer']);
        $crypto = $this->makeDeposit($user, ['method' => 'crypto']);

        $response = $this->actingAs($admin)->get(route('admin.deposits.index', ['method' => 'crypto']));

        $response->assertOk();
        $ids = $response->viewData('deposits')->pluck('id')->all();
        $this->assertSame([$crypto->id], $ids);
    }

    public function test_asset_filter_returns_only_matching_asset(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'user']);
        $usdt = $this->makeDeposit($user, ['asset' => 'USDT']);
        $btc = $this->makeDeposit($user, ['asset' => 'BTC']);

        $response = $this->actingAs($admin)->get(route('admin.deposits.index', ['asset' => 'BTC']));

        $response->assertOk();
        $ids = $response->viewData('deposits')->pluck('id')->all();
        $this->assertSame([$btc->id], $ids);
    }

    public function test_search_by_deposit_id(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'user']);
        $target = $this->makeDeposit($user);
        $this->makeDeposit($user);

        $response = $this->actingAs($admin)->get(route('admin.deposits.index', ['search' => (string) $target->id]));

        $response->assertOk();
        $ids = $response->viewData('deposits')->pluck('id')->all();
        $this->assertContains($target->id, $ids);
    }

    public function test_search_by_user_name(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'user', 'name' => 'Sultan Deposit Tester']);
        $other = User::factory()->create(['role' => 'user', 'name' => 'Someone Else']);
        $target = $this->makeDeposit($user);
        $this->makeDeposit($other);

        $response = $this->actingAs($admin)->get(route('admin.deposits.index', ['search' => 'Sultan']));

        $response->assertOk();
        $ids = $response->viewData('deposits')->pluck('id')->all();
        $this->assertSame([$target->id], $ids);
    }

    public function test_search_by_user_email(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'user', 'email' => 'depositor@example.com']);
        $other = User::factory()->create(['role' => 'user']);
        $target = $this->makeDeposit($user);
        $this->makeDeposit($other);

        $response = $this->actingAs($admin)->get(route('admin.deposits.index', ['search' => 'depositor@example.com']));

        $response->assertOk();
        $ids = $response->viewData('deposits')->pluck('id')->all();
        $this->assertSame([$target->id], $ids);
    }

    public function test_combined_filters_apply_and_logic(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'user', 'name' => 'Combo Match']);
        $match = $this->makeDeposit($user, ['status' => 'pending', 'method' => 'bank_transfer']);
        $this->makeDeposit($user, ['status' => 'approved', 'method' => 'bank_transfer', 'reviewed_by' => $admin->id, 'reviewed_at' => now()]);
        $this->makeDeposit($user, ['status' => 'pending', 'method' => 'crypto']);

        $response = $this->actingAs($admin)->get(route('admin.deposits.index', [
            'search' => 'Combo',
            'status' => 'pending',
            'method' => 'bank_transfer',
        ]));

        $response->assertOk();
        $ids = $response->viewData('deposits')->pluck('id')->all();
        $this->assertSame([$match->id], $ids);
    }

    public function test_reset_link_shown_only_when_filters_active(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'user']);
        $this->makeDeposit($user);

        $this->actingAs($admin)->get(route('admin.deposits.index'))
            ->assertOk()->assertDontSee('Reset');

        $this->actingAs($admin)->get(route('admin.deposits.index', ['status' => 'pending']))
            ->assertOk()->assertSee('Reset');
    }

    public function test_filter_values_are_retained_in_form_after_submission(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'user']);
        $this->makeDeposit($user, ['status' => 'pending']);

        $response = $this->actingAs($admin)->get(route('admin.deposits.index', ['search' => 'Sultan', 'status' => 'pending']));

        $response->assertOk();
        $response->assertSee('value="Sultan"', false);
        $response->assertSee('<option value="pending" selected', false);
    }

    public function test_empty_result_shows_filtered_empty_state(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'user']);
        $this->makeDeposit($user);

        $response = $this->actingAs($admin)->get(route('admin.deposits.index', ['search' => 'no-such-keyword-xyz']));

        $response->assertOk();
        $response->assertSee('No deposits match your current filters.');
    }

    public function test_pagination_returns_second_page(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'user']);

        foreach (range(1, 20) as $i) {
            $this->makeDeposit($user, ['amount' => (string) $i]);
        }

        $firstPage = $this->actingAs($admin)->get(route('admin.deposits.index'));
        $secondPage = $this->actingAs($admin)->get(route('admin.deposits.index', ['page' => 2]));

        $firstPage->assertOk();
        $secondPage->assertOk();

        $firstIds = $firstPage->viewData('deposits')->pluck('id')->all();
        $secondIds = $secondPage->viewData('deposits')->pluck('id')->all();

        $this->assertCount(15, $firstIds);
        $this->assertCount(5, $secondIds);
        $this->assertEmpty(array_intersect($firstIds, $secondIds));
    }

    public function test_pagination_preserves_active_filter(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'user']);

        foreach (range(1, 20) as $i) {
            $this->makeDeposit($user, ['status' => 'approved', 'reviewed_by' => $admin->id, 'reviewed_at' => now(), 'amount' => (string) $i]);
        }
        $this->makeDeposit($user, ['status' => 'pending']);

        $response = $this->actingAs($admin)->get(route('admin.deposits.index', ['status' => 'approved', 'page' => 2]));

        $response->assertOk();
        $ids = $response->viewData('deposits')->pluck('id')->all();
        $this->assertCount(5, $ids);
        $response->assertSee('<option value="approved" selected', false);
    }

    public function test_read_only_no_database_mutation(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'user']);
        $deposit = $this->makeDeposit($user);

        $this->actingAs($admin)->get(route('admin.deposits.index', [
            'search' => 'x',
            'status' => 'pending',
            'method' => 'bank_transfer',
            'asset' => 'USDT',
        ]))->assertOk();

        $fresh = $deposit->fresh();
        $this->assertSame('pending', $fresh->status);
        $this->assertNull($fresh->reviewed_by);
        $this->assertNull($fresh->reviewed_at);
        $this->assertNull($fresh->rejection_reason);
    }

    public function test_guest_is_denied(): void
    {
        $response = $this->get(route('admin.deposits.index', ['search' => 'x']));

        $response->assertRedirect(route('login'));
    }

    public function test_regular_user_is_denied(): void
    {
        $regular = User::factory()->create(['role' => 'user']);

        $response = $this->actingAs($regular)->get(route('admin.deposits.index', ['status' => 'pending']));

        $response->assertForbidden();
    }
}
