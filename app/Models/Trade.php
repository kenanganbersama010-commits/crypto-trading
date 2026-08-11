<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Trade extends Model
{
    protected $fillable = [
        'user_id',
        'pair',
        'side',
        'amount',
        'price',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:8',
            'price' => 'decimal:8',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
