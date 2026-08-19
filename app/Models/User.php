<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'withdrawal_password',
        'role',
        'referral_code',
        'kyc_status',
        'account_status',
        'profile_photo',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'withdrawal_password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'withdrawal_password' => 'hashed',
        ];
    }

    public function wallets(): HasMany
    {
        return $this->hasMany(Wallet::class);
    }

    public function deposits(): HasMany
    {
        return $this->hasMany(Deposit::class);
    }

    public function reviewedDeposits(): HasMany
    {
        return $this->hasMany(Deposit::class, 'reviewed_by');
    }

    public function withdrawals(): HasMany
    {
        return $this->hasMany(Withdrawal::class);
    }

    public function reviewedWithdrawals(): HasMany
    {
        return $this->hasMany(Withdrawal::class, 'reviewed_by');
    }

    public function kycSubmissions(): HasMany
    {
        return $this->hasMany(KycSubmission::class);
    }

    public function reviewedKycSubmissions(): HasMany
    {
        return $this->hasMany(KycSubmission::class, 'reviewed_by');
    }

    public function trades(): HasMany
    {
        return $this->hasMany(Trade::class);
    }

    public function adjustmentHistories(): HasMany
    {
        return $this->hasMany(AdjustmentHistory::class);
    }
}
