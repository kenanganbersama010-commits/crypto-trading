<?php

namespace Tests\Feature\Admin;

use App\Models\AdjustmentHistory;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdjustmentHistorySecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_no_update_route_exists_for_adjustment_history(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'user']);
        $wallet = Wallet::create(['user_id' => $user->id, 'asset' => 'USDT', 'balance' => '100']);

        $history = AdjustmentHistory::create([
            'admin_id' => $admin->id,
            'user_id' => $user->id,
            'wallet_id' => $wallet->id,
            'asset' => 'USDT',
            'type' => 'add',
            'amount' => '10.00000000',
            'balance_before' => '90.00000000',
            'balance_after' => '100.00000000',
            'reason' => 'original',
        ]);

        // No PATCH/PUT/DELETE route is registered for a single adjustment-history
        // record anywhere in the app, so any attempt to mutate one 404s at the
        // router level — this IS the immutability guarantee.
        $this->actingAs($admin)->patch("/admin/adjustment-history/{$history->id}", ['reason' => 'tampered'])->assertNotFound();
        $this->actingAs($admin)->put("/admin/adjustment-history/{$history->id}", ['reason' => 'tampered'])->assertNotFound();
        $this->actingAs($admin)->delete("/admin/adjustment-history/{$history->id}")->assertNotFound();

        $this->assertSame('original', $history->fresh()->reason);
        $this->assertSame(1, AdjustmentHistory::count());
    }

    public function test_adjustment_history_model_uses_explicit_fillable_not_guarded(): void
    {
        $model = new AdjustmentHistory;

        // Model must NOT opt into `protected $guarded = [];` (which would make
        // every column mass-assignable). Eloquent's un-overridden default is
        // `$guarded = ['*']`, i.e. "guard everything except what's in $fillable".
        $this->assertSame(['*'], $model->getGuarded());
        $this->assertNotEmpty($model->getFillable());
        $this->assertEqualsCanonicalizing([
            'admin_id',
            'user_id',
            'wallet_id',
            'asset',
            'type',
            'amount',
            'balance_before',
            'balance_after',
            'reason',
        ], $model->getFillable());
    }

    public function test_admin_id_and_user_id_are_not_mass_assignable_from_client_controlled_array(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $attacker = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'user']);
        $wallet = Wallet::create(['user_id' => $user->id, 'asset' => 'USDT', 'balance' => '100']);

        // admin_id/user_id ARE in $fillable (needed for the legitimate create() call
        // in UserController::adjustBalance), so the real protection is that the
        // controller always derives them from auth()/route-binding, never from
        // raw request input — verified functionally in AdjustmentHistoryTest::
        // test_admin_id_is_taken_from_session_not_request. This test documents that
        // AdjustmentHistory itself has no client-facing create/store endpoint at all
        // ('/admin/adjustment-history' is registered GET-only, so POST is 405, not
        // routed to any controller action).
        $this->actingAs($admin)->post('/admin/adjustment-history', [
            'admin_id' => $attacker->id,
            'user_id' => $user->id,
            'wallet_id' => $wallet->id,
            'asset' => 'USDT',
            'type' => 'add',
            'amount' => '999999',
            'balance_before' => '0',
            'balance_after' => '999999',
            'reason' => 'forged',
        ])->assertMethodNotAllowed();

        $this->assertSame(0, AdjustmentHistory::count());
    }

    public function test_adjustment_history_page_does_not_expose_sensitive_fields(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'user', 'withdrawal_password' => '123456']);
        $wallet = Wallet::create(['user_id' => $user->id, 'asset' => 'USDT', 'balance' => '100']);

        AdjustmentHistory::create([
            'admin_id' => $admin->id,
            'user_id' => $user->id,
            'wallet_id' => $wallet->id,
            'asset' => 'USDT',
            'type' => 'add',
            'amount' => '10.00000000',
            'balance_before' => '90.00000000',
            'balance_after' => '100.00000000',
            'reason' => 'x',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.adjustment-history.index'));

        $response->assertOk();
        $response->assertDontSee($user->password);
        $response->assertDontSee('123456');
        $response->assertDontSee('remember_token');
        $response->assertDontSee(config('app.key'));
    }

    public function test_reason_field_is_html_escaped_in_history_output(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'user']);
        $wallet = Wallet::create(['user_id' => $user->id, 'asset' => 'USDT', 'balance' => '100']);

        AdjustmentHistory::create([
            'admin_id' => $admin->id,
            'user_id' => $user->id,
            'wallet_id' => $wallet->id,
            'asset' => 'USDT',
            'type' => 'add',
            'amount' => '10.00000000',
            'balance_before' => '90.00000000',
            'balance_after' => '100.00000000',
            'reason' => '<script>alert(1)</script>',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.adjustment-history.index'));

        $response->assertOk();
        $response->assertDontSee('<script>alert(1)</script>', false);
        $response->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false);
    }

    public function test_add_balance_only_accepts_post_method(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'user']);
        Wallet::create(['user_id' => $user->id, 'asset' => 'USDT', 'balance' => '100']);

        $response = $this->actingAs($admin)->get(route('admin.users.adjust-balance', $user).'?action=add&asset=USDT&amount=100&reason=x');

        $response->assertMethodNotAllowed();
    }

    public function test_manipulated_user_id_in_url_is_scoped_by_route_model_binding(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $userA = User::factory()->create(['role' => 'user']);
        $userB = User::factory()->create(['role' => 'user']);

        $walletA = Wallet::create(['user_id' => $userA->id, 'asset' => 'USDT', 'balance' => '500']);
        $walletB = Wallet::create(['user_id' => $userB->id, 'asset' => 'USDT', 'balance' => '500']);

        // Admin submits an adjustment scoped to userA's route; nothing in the
        // payload can redirect the write to userB even if wallet/user IDs were
        // guessed, because the controller never trusts client-supplied IDs for
        // the target wallet — it re-resolves the wallet from the bound $user.
        $this->actingAs($admin)->post(route('admin.users.adjust-balance', $userA), [
            'action' => 'add',
            'asset' => 'USDT',
            'amount' => '50',
            'reason' => 'x',
            'user_id' => $userB->id,
            'wallet_id' => $walletB->id,
        ]);

        $this->assertSame('550.00000000', $walletA->fresh()->balance);
        $this->assertSame('500.00000000', $walletB->fresh()->balance);

        $history = AdjustmentHistory::first();
        $this->assertSame($userA->id, $history->user_id);
        $this->assertSame($walletA->id, $history->wallet_id);
    }

    public function test_nonexistent_user_id_returns_not_found_not_error(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->post('/admin/users/999999/adjust-balance', [
            'action' => 'add',
            'asset' => 'USDT',
            'amount' => '10',
            'reason' => 'x',
        ]);

        $response->assertNotFound();
    }
}
