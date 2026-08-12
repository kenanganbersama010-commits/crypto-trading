<?php

namespace Tests\Feature;

use App\Models\Deposit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DepositTest extends TestCase
{
    use RefreshDatabase;

    public function test_deposit_can_be_created(): void
    {
        $user = User::factory()->create(['role' => 'user']);

        $deposit = Deposit::create([
            'user_id' => $user->id,
            'method' => 'bank_transfer',
            'asset' => 'USDT',
            'amount' => '250.00000000',
        ]);

        $this->assertDatabaseHas('deposits', [
            'id' => $deposit->id,
            'user_id' => $user->id,
            'method' => 'bank_transfer',
            'asset' => 'USDT',
        ]);
        $this->assertSame('250.00000000', $deposit->amount);
    }

    public function test_deposit_belongs_to_user(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $deposit = Deposit::create([
            'user_id' => $user->id,
            'method' => 'crypto',
            'asset' => 'BTC',
            'amount' => '0.01000000',
        ]);

        $this->assertTrue($deposit->user->is($user));
    }

    public function test_user_has_many_deposits(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        Deposit::create(['user_id' => $user->id, 'method' => 'crypto', 'asset' => 'BTC', 'amount' => '0.01']);
        Deposit::create(['user_id' => $user->id, 'method' => 'crypto', 'asset' => 'ETH', 'amount' => '0.5']);

        $this->assertCount(2, $user->fresh()->deposits);
    }

    public function test_status_defaults_to_pending(): void
    {
        $user = User::factory()->create(['role' => 'user']);

        $deposit = Deposit::create([
            'user_id' => $user->id,
            'method' => 'e_wallet',
            'asset' => 'USDT',
            'amount' => '100',
        ]);

        $this->assertSame('pending', $deposit->fresh()->status);
    }

    public function test_review_fields_are_nullable_on_creation(): void
    {
        $user = User::factory()->create(['role' => 'user']);

        $deposit = Deposit::create([
            'user_id' => $user->id,
            'method' => 'bank_transfer',
            'asset' => 'USDT',
            'amount' => '100',
        ]);

        $deposit->refresh();

        $this->assertNull($deposit->reviewed_by);
        $this->assertNull($deposit->reviewed_at);
        $this->assertNull($deposit->rejection_reason);
        $this->assertNull($deposit->proof_image);
    }

    public function test_deposit_has_reviewer_relationship(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'user']);

        $deposit = Deposit::create([
            'user_id' => $user->id,
            'method' => 'bank_transfer',
            'asset' => 'USDT',
            'amount' => '100',
            'status' => 'approved',
            'reviewed_by' => $admin->id,
            'reviewed_at' => now(),
        ]);

        $this->assertTrue($deposit->reviewer->is($admin));
    }

    public function test_deposit_amount_uses_decimal_not_float(): void
    {
        $user = User::factory()->create(['role' => 'user']);

        $deposit = Deposit::create([
            'user_id' => $user->id,
            'method' => 'crypto',
            'asset' => 'BTC',
            'amount' => '0.00000001',
        ]);

        $this->assertSame('0.00000001', $deposit->fresh()->amount);
    }

    public function test_deposit_model_uses_explicit_fillable_not_guarded(): void
    {
        $model = new Deposit;

        $this->assertSame(['*'], $model->getGuarded());
        $this->assertEqualsCanonicalizing([
            'user_id',
            'method',
            'asset',
            'amount',
            'proof_image',
            'status',
            'reviewed_by',
            'reviewed_at',
            'rejection_reason',
        ], $model->getFillable());
    }

    public function test_proof_image_url_accessor_returns_null_when_no_proof_image(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $deposit = Deposit::create([
            'user_id' => $user->id,
            'method' => 'bank_transfer',
            'asset' => 'USDT',
            'amount' => '100',
            'proof_image' => null,
        ]);

        $this->assertNull($deposit->proof_image_url);
    }

    public function test_proof_image_url_accessor_resolves_to_public_disk_url(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $deposit = Deposit::create([
            'user_id' => $user->id,
            'method' => 'bank_transfer',
            'asset' => 'USDT',
            'amount' => '100',
            'proof_image' => 'deposits/proofs/sample.jpg',
        ]);

        $this->assertStringContainsString('storage/deposits/proofs/sample.jpg', $deposit->proof_image_url);
    }
}
