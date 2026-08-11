<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdjustmentHistory extends Model
{
    protected $fillable = [
        'admin_id',
        'user_id',
        'wallet_id',
        'asset',
        'type',
        'amount',
        'balance_before',
        'balance_after',
        'reason',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:8',
            'balance_before' => 'decimal:8',
            'balance_after' => 'decimal:8',
        ];
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }
}
