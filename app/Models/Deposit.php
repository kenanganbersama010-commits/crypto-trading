<?php

namespace App\Models;

use Illuminate\Contracts\Filesystem\Cloud;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Deposit extends Model
{
    protected $fillable = [
        'user_id',
        'method',
        'asset',
        'amount',
        'proof_image',
        'status',
        'reviewed_by',
        'reviewed_at',
        'rejection_reason',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:8',
            'reviewed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    protected function proofImageUrl(): Attribute
    {
        return Attribute::make(
            get: function (): ?string {
                if (! $this->proof_image) {
                    return null;
                }

                // Storage::disk() is typed by its facade docblock as the base
                // Filesystem contract, which doesn't declare url() — but the
                // 'public' disk is actually backed by LocalFilesystemAdapter,
                // which implements the richer Cloud contract that does.
                /** @var Cloud $disk */
                $disk = Storage::disk('public');

                return $disk->url($this->proof_image);
            },
        );
    }
}
