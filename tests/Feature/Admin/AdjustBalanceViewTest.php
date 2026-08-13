<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdjustBalanceViewTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_show_page_renders_with_updated_balance_after_add(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'user']);
        Wallet::create(['user_id' => $user->id, 'asset' => 'USDT', 'balance' => '500.00000000']);

        $this->actingAs($admin)->post(route('admin.users.adjust-balance', $user), [
            'action' => 'add',
            'asset' => 'USDT',
            'amount' => '100',
            'reason' => 'Manual correction',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.users.show', $user));

        $response->assertOk();
        $response->assertSee('Saldo berhasil ditambahkan ke dompet.');
        $response->assertSee('600');
    }

    public function test_user_show_page_renders_with_updated_balance_after_deduct(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'user']);
        Wallet::create(['user_id' => $user->id, 'asset' => 'USDT', 'balance' => '500.00000000']);

        $this->actingAs($admin)->post(route('admin.users.adjust-balance', $user), [
            'action' => 'deduct',
            'asset' => 'USDT',
            'amount' => '100',
            'reason' => 'Manual correction',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.users.show', $user));

        $response->assertOk();
        $response->assertSee('Saldo berhasil dikurangi dari dompet.');
        $response->assertSee('400');
    }

    public function test_user_show_page_renders_insufficient_balance_error(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'user']);
        Wallet::create(['user_id' => $user->id, 'asset' => 'USDT', 'balance' => '50.00000000']);

        $this->from(route('admin.users.show', $user))
            ->actingAs($admin)
            ->post(route('admin.users.adjust-balance', $user), [
                'action' => 'deduct',
                'asset' => 'USDT',
                'amount' => '100',
                'reason' => 'Manual correction',
            ]);

        $response = $this->actingAs($admin)->get(route('admin.users.show', $user));

        $response->assertOk();
        $response->assertSee('Insufficient balance');
    }

    public function test_user_show_page_renders_validation_errors_and_reopens_modal(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'user']);
        Wallet::create(['user_id' => $user->id, 'asset' => 'USDT', 'balance' => '500.00000000']);

        $response = $this->from(route('admin.users.show', $user))
            ->actingAs($admin)
            ->post(route('admin.users.adjust-balance', $user), [
                'action' => 'add',
                'asset' => 'USDT',
                'amount' => '0',
                'reason' => '',
            ]);

        $response->assertRedirect(route('admin.users.show', $user));

        $follow = $this->actingAs($admin)->get(route('admin.users.show', $user));
        $follow->assertOk();
    }
}
