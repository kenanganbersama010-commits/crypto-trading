<?php

namespace App\Services;

use App\Models\ApiCredential;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Binance Authenticated API Service
 * 
 * Handles authenticated requests to Binance API using stored credentials.
 * Uses HMAC SHA256 signature for request signing.
 * 
 * SECURITY: API Secret is only decrypted server-side and NEVER sent to frontend.
 */
class BinanceAuthService
{
    private const BASE_URL = 'https://api.binance.com';
    private const TIMEOUT = 10;
    
    /**
     * Test Binance API connection with stored credentials
     * 
     * @return array{success: bool, message: string, account?: array}
     */
    public function testConnection(): array
    {
        try {
            // Get encrypted credentials from database
            $credential = ApiCredential::where('provider', 'binance')
                ->where('is_active', true)
                ->first();
            
            if (!$credential) {
                return [
                    'success' => false,
                    'message' => 'No Binance API credentials configured.',
                ];
            }
            
            // Decrypt API Secret (happens server-side only via Attribute accessor)
            $apiKey = $credential->api_key;
            $apiSecret = $credential->api_secret; // Auto-decrypted by model
            
            if (!$apiKey || !$apiSecret) {
                return [
                    'success' => false,
                    'message' => 'Invalid API credentials.',
                ];
            }
            
            // Test connection using account endpoint (read-only)
            $accountInfo = $this->getAccountInfo($apiKey, $apiSecret);
            
            if ($accountInfo['success']) {
                return [
                    'success' => true,
                    'message' => 'Binance connection successful.',
                    'account' => [
                        'canTrade' => $accountInfo['data']['canTrade'] ?? false,
                        'canWithdraw' => $accountInfo['data']['canWithdraw'] ?? false,
                        'canDeposit' => $accountInfo['data']['canDeposit'] ?? false,
                        'updateTime' => $accountInfo['data']['updateTime'] ?? null,
                    ],
                ];
            }
            
            return [
                'success' => false,
                'message' => $accountInfo['message'],
            ];
            
        } catch (\Exception $e) {
            // Log error without exposing secrets
            Log::error('Binance test connection failed', [
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
            ]);
            
            return [
                'success' => false,
                'message' => 'Unable to connect to Binance. Please check your credentials.',
            ];
        }
    }
    
    /**
     * Get account information (requires valid API key and signature)
     * 
     * @param string $apiKey
     * @param string $apiSecret
     * @return array{success: bool, message: string, data?: array}
     */
    private function getAccountInfo(string $apiKey, string $apiSecret): array
    {
        try {
            $timestamp = round(microtime(true) * 1000);
            $recvWindow = 5000; // 5 seconds
            
            // Build query string
            $queryString = http_build_query([
                'timestamp' => $timestamp,
                'recvWindow' => $recvWindow,
            ]);
            
            // Generate signature using HMAC SHA256
            $signature = hash_hmac('sha256', $queryString, $apiSecret);
            
            // Make authenticated request
            $response = Http::timeout(self::TIMEOUT)
                ->withHeaders([
                    'X-MBX-APIKEY' => $apiKey,
                ])
                ->get(self::BASE_URL . '/api/v3/account', [
                    'timestamp' => $timestamp,
                    'recvWindow' => $recvWindow,
                    'signature' => $signature,
                ]);
            
            if ($response->successful()) {
                $data = $response->json();
                
                return [
                    'success' => true,
                    'message' => 'Account info retrieved successfully.',
                    'data' => $data,
                ];
            }
            
            // Handle Binance API errors
            $error = $response->json();
            $errorMsg = $error['msg'] ?? 'Unknown error';
            $errorCode = $error['code'] ?? $response->status();
            
            // Provide user-friendly messages
            $userMessage = match ($errorCode) {
                -1022 => 'Invalid signature. Please check your API Secret.',
                -2014 => 'Invalid API Key.',
                -2015 => 'Invalid API Key format.',
                -1021 => 'Timestamp error. Please sync your server time.',
                default => 'Binance API error: ' . $errorMsg,
            };
            
            Log::warning('Binance API error', [
                'code' => $errorCode,
                'message' => $errorMsg,
                'status' => $response->status(),
            ]);
            
            return [
                'success' => false,
                'message' => $userMessage,
            ];
            
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            return [
                'success' => false,
                'message' => 'Connection timeout. Please try again.',
            ];
        } catch (\Exception $e) {
            Log::error('Binance account info exception', [
                'message' => $e->getMessage(),
            ]);
            
            return [
                'success' => false,
                'message' => 'Failed to connect to Binance.',
            ];
        }
    }
    
    /**
     * Get account balances (authenticated)
     * 
     * @return array|null
     */
    public function getBalances(): ?array
    {
        $credential = ApiCredential::where('provider', 'binance')
            ->where('is_active', true)
            ->first();
        
        if (!$credential) {
            return null;
        }
        
        $accountInfo = $this->getAccountInfo($credential->api_key, $credential->api_secret);
        
        if (!$accountInfo['success']) {
            return null;
        }
        
        // Return only non-zero balances
        $balances = collect($accountInfo['data']['balances'] ?? [])
            ->filter(fn($balance) => (float)$balance['free'] > 0 || (float)$balance['locked'] > 0)
            ->values()
            ->toArray();
        
        return $balances;
    }
}
