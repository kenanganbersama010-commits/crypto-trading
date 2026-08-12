<?php

namespace Tests\Feature\Admin;

use App\Models\Deposit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DepositListUiTest extends TestCase
{
    use RefreshDatabase;

    private function makeDeposit(User $user, array $overrides = []): Deposit
    {
        return Deposit::create(array_merge([
            'user_id' => $user->id,
            'method' => 'bank_transfer',
            'asset' => 'USDT',
            'amount' => '250.00000000',
        ], $overrides));
    }

    public function test_pending_status_renders_with_muted_text_styling(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'user']);
        $this->makeDeposit($user, ['status' => 'pending']);

        $response = $this->actingAs($admin)->get(route('admin.deposits.index'));

        $response->assertOk();
        $response->assertSee('text-gray-500', false);
        $response->assertSee('Pending');
    }

    public function test_approved_status_is_displayed(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'user']);
        $this->makeDeposit($user, ['status' => 'approved']);

        $response = $this->actingAs($admin)->get(route('admin.deposits.index'));

        $response->assertOk();
        $response->assertSee('Approved');
    }

    public function test_rejected_status_renders_with_negative_text_styling(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'user']);
        $this->makeDeposit($user, ['status' => 'rejected']);

        $response = $this->actingAs($admin)->get(route('admin.deposits.index'));

        $response->assertOk();
        $response->assertSee('text-red-600', false);
        $response->assertSee('Rejected');
    }

    public function test_deposit_without_proof_shows_no_proof_text(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'user']);
        $this->makeDeposit($user, ['proof_image' => null]);

        $response = $this->actingAs($admin)->get(route('admin.deposits.index'));

        $response->assertOk();
        $response->assertSee('No proof');
    }

    public function test_deposit_with_proof_shows_thumbnail_and_preview_modal(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'user']);
        $deposit = $this->makeDeposit($user, ['proof_image' => 'deposits/proofs/sample.jpg']);

        $response = $this->actingAs($admin)->get(route('admin.deposits.index'));

        $response->assertOk();
        $response->assertDontSee('No proof');
        $response->assertSee('storage/deposits/proofs/sample.jpg', false);
        $response->assertSee("deposit-proof-{$deposit->id}", false);
    }

    public function test_view_action_links_to_deposit_detail_route(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'user']);
        $deposit = $this->makeDeposit($user);

        $response = $this->actingAs($admin)->get(route('admin.deposits.index'));

        $response->assertOk();
        $response->assertSee(route('admin.deposits.show', $deposit), false);
    }

    public function test_method_snake_case_is_formatted_for_display_without_changing_database_value(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'user']);
        $deposit = $this->makeDeposit($user, ['method' => 'bank_transfer']);

        $response = $this->actingAs($admin)->get(route('admin.deposits.index'));

        $response->assertOk();
        $response->assertSee('Bank Transfer');
        $this->assertSame('bank_transfer', $deposit->fresh()->method);
    }

    public function test_amount_and_asset_are_not_hardcoded(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'user']);
        $this->makeDeposit($user, ['asset' => 'BTC', 'amount' => '0.00500000']);

        $response = $this->actingAs($admin)->get(route('admin.deposits.index'));

        $response->assertOk();
        $response->assertSee('BTC');
        $response->assertSee('0.005');
    }

    public function test_user_method_and_asset_are_html_escaped(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'user', 'name' => '<script>alert(1)</script>']);
        $this->makeDeposit($user);

        $response = $this->actingAs($admin)->get(route('admin.deposits.index'));

        $response->assertOk();
        $response->assertDontSee('<script>alert(1)</script>', false);
        $response->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false);
    }

    public function test_no_search_filter_or_pagination_controls_are_present(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'user']);
        $this->makeDeposit($user);

        $response = $this->actingAs($admin)->get(route('admin.deposits.index'));

        $response->assertOk();
        $response->assertDontSee('name="search"', false);
        $response->assertDontSee('Apply Filters');
        $response->assertDontSee('pagination', false);
    }

    public function test_no_approve_or_reject_actions_are_present(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'user']);
        $this->makeDeposit($user);

        $response = $this->actingAs($admin)->get(route('admin.deposits.index'));

        $response->assertOk();
        $response->assertDontSee('Approve');
        $response->assertDontSee('Reject');
    }
}
