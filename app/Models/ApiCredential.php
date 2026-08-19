<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Facades\Crypt;

class ApiCredential extends Model
{
    protected $fillable = ['provider', 'api_key', 'api_secret', 'is_active'];

    /**
     * Encrypt API Secret when setting.
     * Decrypt API Secret when getting.
     */
    protected function apiSecret(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value ? Crypt::decryptString($value) : null,
            set: fn ($value) => $value ? Crypt::encryptString($value) : null,
        );
    }

    /**
     * Get masked API Key for display (show first 4 and last 4 characters).
     */
    public function getMaskedApiKeyAttribute(): string
    {
        if (!$this->api_key || strlen($this->api_key) < 8) {
            return '••••••••••••••••';
        }

        $first = substr($this->api_key, 0, 4);
        $last = substr($this->api_key, -4);
        
        return $first . '••••••••' . $last;
    }
}
