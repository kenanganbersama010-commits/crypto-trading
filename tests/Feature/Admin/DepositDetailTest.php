<?php

namespace Tests\Feature\Admin;

use App\Models\Deposit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DepositDetailTest extends TestCase
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

    public function test_pending_deposit_shows_not_reviewed_yet(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'user']);
        $deposit = $this->makeDeposit($user, ['status' => 'pending']);

        $response = $this->actingAs($admin)->get(route('admin.deposits.show', $deposit));

        $response->assertOk();
        $response->assertSee('Menunggu');
        $response->assertSee('Belum ditinjau.');
    }

    public function test_approved_deposit_shows_reviewer_and_reviewed_at(): void
    {
        $reviewingAdmin = User::factory()->create(['role' => 'admin', 'name' => 'Admin Reviewer']);
        $viewingAdmin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'user']);
        $deposit = $this->makeDeposit($user, [
            'status' => 'approved',
            'reviewed_by' => $reviewingAdmin->id,
            'reviewed_at' => now(),
        ]);

        $response = $this->actingAs($viewingAdmin)->get(route('admin.deposits.show', $deposit));

        $response->assertOk();
        $response->assertSee('Disetujui');
        $response->assertSee('Admin Reviewer');
        $response->assertSee($deposit->fresh()->reviewed_at->format('d M Y'));
    }

    public function test_rejected_deposit_shows_reviewer_and_rejection_reason(): void
    {
        $reviewingAdmin = User::factory()->create(['role' => 'admin', 'name' => 'Admin Reviewer']);
        $user = User::factory()->create(['role' => 'user']);
        $deposit = $this->makeDeposit($user, [
            'status' => 'rejected',
            'reviewed_by' => $reviewingAdmin->id,
            'reviewed_at' => now(),
            'rejection_reason' => 'Proof image unreadable',
        ]);

        $response = $this->actingAs($reviewingAdmin)->get(route('admin.deposits.show', $deposit));

        $response->assertOk();
        $response->assertSee('Ditolak');
        $response->assertSee('Admin Reviewer');
        $response->assertSee('Proof image unreadable');
    }

    public function test_pending_deposit_does_not_show_rejection_reason_section(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'user']);
        $deposit = $this->makeDeposit($user, ['status' => 'pending', 'rejection_reason' => null]);

        $response = $this->actingAs($admin)->get(route('admin.deposits.show', $deposit));

        $response->assertOk();
        $response->assertSee('Belum ditinjau.');
    }

    public function test_deposit_with_proof_image_renders_it(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'user']);
        $deposit = $this->makeDeposit($user, ['proof_image' => 'deposits/proofs/sample.jpg']);

        $response = $this->actingAs($admin)->get(route('admin.deposits.show', $deposit));

        $response->assertOk();
        $response->assertDontSee('Bukti pembayaran tidak tersedia.');
        $response->assertSee('storage/deposits/proofs/sample.jpg', false);
    }

    public function test_deposit_without_proof_image_shows_fallback_message(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'user']);
        $deposit = $this->makeDeposit($user, ['proof_image' => null]);

        $response = $this->actingAs($admin)->get(route('admin.deposits.show', $deposit));

        $response->assertOk();
        $response->assertSee('Bukti pembayaran tidak tersedia.');
    }

    public function test_view_user_link_points_to_user_detail_route(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'user']);
        $deposit = $this->makeDeposit($user);

        $response = $this->actingAs($admin)->get(route('admin.deposits.show', $deposit));

        $response->assertOk();
        $response->assertSee(route('admin.users.show', $user), false);
    }

    public function test_back_to_deposits_link_points_to_index_route(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'user']);
        $deposit = $this->makeDeposit($user);

        $response = $this->actingAs($admin)->get(route('admin.deposits.show', $deposit));

        $response->assertOk();
        $response->assertSee(route('admin.deposits.index'), false);
    }

    public function test_viewing_deposit_detail_does_not_mutate_anything(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'user']);
        $deposit = $this->makeDeposit($user, ['status' => 'pending']);

        $this->actingAs($admin)->get(route('admin.deposits.show', $deposit));

        $fresh = $deposit->fresh();
        $this->assertSame('pending', $fresh->status);
        $this->assertNull($fresh->reviewed_by);
        $this->assertNull($fresh->reviewed_at);
        $this->assertNull($fresh->rejection_reason);
        $this->assertSame($deposit->updated_at->toDateTimeString(), $fresh->updated_at->toDateTimeString());
    }

    public function test_viewing_deposit_detail_does_not_mutate_wallet_or_create_adjustment(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'user']);
        $wallet = \App\Models\Wallet::create(['user_id' => $user->id, 'asset' => 'USDT', 'balance' => '100.00000000']);
        $deposit = $this->makeDeposit($user);

        $this->actingAs($admin)->get(route('admin.deposits.show', $deposit));

        $this->assertSame('100.00000000', $wallet->fresh()->balance);
        $this->assertSame(0, \App\Models\AdjustmentHistory::count());
    }

    public function test_approve_button_shown_only_for_pending_deposit(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'user']);
        $pending = $this->makeDeposit($user, ['status' => 'pending']);
        $approved = $this->makeDeposit($user, [
            'status' => 'approved',
            'reviewed_by' => $admin->id,
            'reviewed_at' => now(),
        ]);
        $rejected = $this->makeDeposit($user, [
            'status' => 'rejected',
            'reviewed_by' => $admin->id,
            'reviewed_at' => now(),
        ]);

        $this->actingAs($admin)->get(route('admin.deposits.show', $pending))
            ->assertOk()->assertSee('Setujui Deposit');

        $this->actingAs($admin)->get(route('admin.deposits.show', $approved))
            ->assertOk()->assertDontSee('Setujui Deposit');

        $this->actingAs($admin)->get(route('admin.deposits.show', $rejected))
            ->assertOk()->assertDontSee('Setujui Deposit');
    }

    public function test_reject_button_shown_only_for_pending_deposit(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'user']);
        $pending = $this->makeDeposit($user, ['status' => 'pending']);
        $approved = $this->makeDeposit($user, [
            'status' => 'approved',
            'reviewed_by' => $admin->id,
            'reviewed_at' => now(),
        ]);
        $rejected = $this->makeDeposit($user, [
            'status' => 'rejected',
            'reviewed_by' => $admin->id,
            'reviewed_at' => now(),
        ]);

        $this->actingAs($admin)->get(route('admin.deposits.show', $pending))
            ->assertOk()->assertSee('Tolak Deposit');

        $this->actingAs($admin)->get(route('admin.deposits.show', $approved))
            ->assertOk()->assertDontSee('Tolak Deposit');

        $this->actingAs($admin)->get(route('admin.deposits.show', $rejected))
            ->assertOk()->assertDontSee('Tolak Deposit');
    }

    public function test_deposit_detail_data_is_html_escaped(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'user', 'name' => '<script>alert(1)</script>']);
        $deposit = $this->makeDeposit($user, [
            'status' => 'rejected',
            'reviewed_by' => $admin->id,
            'reviewed_at' => now(),
            'rejection_reason' => '<img src=x onerror=alert(1)>',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.deposits.show', $deposit));

        $response->assertOk();
        $response->assertDontSee('<script>alert(1)</script>', false);
        $response->assertDontSee('<img src=x onerror=alert(1)>', false);
        $response->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false);
    }

    public function test_guest_is_denied(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $deposit = $this->makeDeposit($user);

        $response = $this->get(route('admin.deposits.show', $deposit));

        $response->assertRedirect(route('login'));
    }

    public function test_regular_user_is_denied_even_with_valid_deposit_id(): void
    {
        $regular = User::factory()->create(['role' => 'user']);
        $targetUser = User::factory()->create(['role' => 'user']);
        $deposit = $this->makeDeposit($targetUser);

        $response = $this->actingAs($regular)->get(route('admin.deposits.show', $deposit));

        $response->assertForbidden();
    }

    public function test_nonexistent_deposit_id_returns_404(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get('/admin/deposits/999999');

        $response->assertNotFound();
    }
}
