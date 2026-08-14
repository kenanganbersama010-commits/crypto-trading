<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CryptoMarketService
{
    /**
     * Base URL for Binance Public API
     */
    protected string $baseUrl;

    /**
     * Cache TTL in seconds (30 seconds for market data)
     */
    protected int $cacheTtl;

    /**
     * HTTP timeout in seconds
     */
    protected int $timeout;

    public function __construct()
    {
        $this->baseUrl = config('services.binance.api_url', 'https://api.binance.com');
        $this->cacheTtl = config('services.binance.cache_ttl', 30);
        $this->timeout = config('services.binance.timeout', 5);
    }

    /**
     * Get ticker data for a single symbol
     *
     * @param string $symbol e.g., 'BTCUSDT'
     * @return array|null
     */
    public function getTicker(string $symbol): ?array
    {
        $symbol = strtoupper($symbol);

        // Validate symbol format (alphanumeric only)
        if (!preg_match('/^[A-Z0-9]+$/', $symbol)) {
            Log::warning('Invalid symbol format', ['symbol' => $symbol]);
            return null;
        }

        $cacheKey = "crypto_ticker_{$symbol}";

        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($symbol) {
            return $this->fetchTicker($symbol);
        });
    }

    /**
     * Get ticker data for multiple symbols
     *
     * @param array $symbols e.g., ['BTCUSDT', 'ETHUSDT']
     * @return array
     */
    public function getMultipleTickers(array $symbols): array
    {
        $results = [];

        foreach ($symbols as $symbol) {
            $ticker = $this->getTicker($symbol);
            if ($ticker) {
                $results[] = $ticker;
            }
        }

        return $results;
    }

    /**
     * Get static fallback data when provider is unavailable
     *
     * @param string $symbol
     * @return array|null
     */
    public function getFallbackData(string $symbol): ?array
    {
        $fallbackData = [
            'BTCUSDT' => [
                'symbol' => 'BTCUSDT',
                'base_asset' => 'BTC',
                'quote_asset' => 'USDT',
                'price' => 62802.29,
                'change_24h' => 1.24,
                'high_24h' => 64120.00,
                'low_24h' => 61220.00,
                'volume_24h' => 24567.89,
                'quote_volume_24h' => 1542000000.00,
                'timestamp' => now()->toIso8601String(),
                'provider' => 'fallback',
            ],
            'ETHUSDT' => [
                'symbol' => 'ETHUSDT',
                'base_asset' => 'ETH',
                'quote_asset' => 'USDT',
                'price' => 1876.37,
                'change_24h' => 0.82,
                'high_24h' => 1920.00,
                'low_24h' => 1850.00,
                'volume_24h' => 156789.45,
                'quote_volume_24h' => 294000000.00,
                'timestamp' => now()->toIso8601String(),
                'provider' => 'fallback',
            ],
            'BNBUSDT' => [
                'symbol' => 'BNBUSDT',
                'base_asset' => 'BNB',
                'quote_asset' => 'USDT',
                'price' => 605.01,
                'change_24h' => 2.35,
                'high_24h' => 620.00,
                'low_24h' => 590.00,
                'volume_24h' => 89456.78,
                'quote_volume_24h' => 54000000.00,
                'timestamp' => now()->toIso8601String(),
                'provider' => 'fallback',
            ],
            'SOLUSDT' => [
                'symbol' => 'SOLUSDT',
                'base_asset' => 'SOL',
                'quote_asset' => 'USDT',
                'price' => 142.18,
                'change_24h' => -1.06,
                'high_24h' => 145.00,
                'low_24h' => 140.00,
                'volume_24h' => 234567.89,
                'quote_volume_24h' => 33000000.00,
                'timestamp' => now()->toIso8601String(),
                'provider' => 'fallback',
            ],
            'XRPUSDT' => [
                'symbol' => 'XRPUSDT',
                'base_asset' => 'XRP',
                'quote_asset' => 'USDT',
                'price' => 0.5231,
                'change_24h' => -0.44,
                'high_24h' => 0.5350,
                'low_24h' => 0.5180,
                'volume_24h' => 987654321.00,
                'quote_volume_24h' => 517000000.00,
                'timestamp' => now()->toIso8601String(),
                'provider' => 'fallback',
            ],
            'ADAUSDT' => [
                'symbol' => 'ADAUSDT',
                'base_asset' => 'ADA',
                'quote_asset' => 'USDT',
                'price' => 0.3456,
                'change_24h' => 1.15,
                'high_24h' => 0.3520,
                'low_24h' => 0.3390,
                'volume_24h' => 456789123.00,
                'quote_volume_24h' => 158000000.00,
                'timestamp' => now()->toIso8601String(),
                'provider' => 'fallback',
            ],
        ];

        return $fallbackData[$symbol] ?? null;
    }

    /**
     * Get ticker with fallback support
     *
     * @param string $symbol
     * @param bool $allowFallback
     * @return array|null
     */
    public function getTickerWithFallback(string $symbol, bool $allowFallback = true): ?array
    {
        $ticker = $this->getTicker($symbol);

        // If API fails and fallback is allowed, use static fallback data
        if (!$ticker && $allowFallback) {
            return $this->getFallbackData($symbol);
        }

        return $ticker;
    }

    /**
     * Get multiple tickers with fallback support
     *
     * @param array $symbols
     * @param bool $allowFallback
     * @return array
     */
    public function getMultipleTickersWithFallback(array $symbols, bool $allowFallback = true): array
    {
        $results = [];

        foreach ($symbols as $symbol) {
            $ticker = $this->getTickerWithFallback($symbol, $allowFallback);
            if ($ticker) {
                $results[] = $ticker;
            }
        }

        return $results;
    }

    /**
     * Fetch ticker from Binance API
     *
     * @param string $symbol
     * @return array|null
     */
    protected function fetchTicker(string $symbol): ?array
    {
        try {
            $response = Http::timeout($this->timeout)
                ->connectTimeout(3)
                ->get("{$this->baseUrl}/api/v3/ticker/24hr", [
                    'symbol' => $symbol
                ]);

            if ($response->successful()) {
                $data = $response->json();
                return $this->normalizeResponse($data);
            }

            // Log error without exposing sensitive data
            Log::warning('Binance API error', [
                'symbol' => $symbol,
                'status' => $response->status(),
            ]);

            return null;
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error('Binance API connection timeout', [
                'symbol' => $symbol,
                'message' => 'Connection timeout or network error'
            ]);
            return null;
        } catch (\Exception $e) {
            Log::error('Binance API exception', [
                'symbol' => $symbol,
                'message' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Normalize Binance response to internal format
     *
     * @param array $data
     * @return array
     */
    protected function normalizeResponse(array $data): array
    {
        // Extract base and quote asset from symbol (e.g., BTCUSDT -> BTC, USDT)
        $symbol = $data['symbol'] ?? '';
        $baseAsset = '';
        $quoteAsset = '';

        // Common quote assets
        $quoteAssets = ['USDT', 'BUSD', 'USDC', 'BTC', 'ETH', 'BNB'];
        
        foreach ($quoteAssets as $quote) {
            if (str_ends_with($symbol, $quote)) {
                $quoteAsset = $quote;
                $baseAsset = substr($symbol, 0, -strlen($quote));
                break;
            }
        }

        return [
            'symbol' => $symbol,
            'base_asset' => $baseAsset,
            'quote_asset' => $quoteAsset,
            'price' => (float) ($data['lastPrice'] ?? 0),
            'change_24h' => (float) ($data['priceChangePercent'] ?? 0),
            'high_24h' => (float) ($data['highPrice'] ?? 0),
            'low_24h' => (float) ($data['lowPrice'] ?? 0),
            'volume_24h' => (float) ($data['volume'] ?? 0),
            'quote_volume_24h' => (float) ($data['quoteVolume'] ?? 0),
            'timestamp' => now()->toIso8601String(),
            'provider' => 'binance',
        ];
    }

    /**
     * Get default market symbols
     *
     * @return array
     */
    public static function getDefaultSymbols(): array
    {
        return [
            'BTCUSDT',
            'ETHUSDT',
            'BNBUSDT',
            'SOLUSDT',
            'XRPUSDT',
            'ADAUSDT',
        ];
    }

    /**
     * Check if service is available
     *
     * @return bool
     */
    public function isAvailable(): bool
    {
        try {
            $response = Http::timeout(3)
                ->get("{$this->baseUrl}/api/v3/ping");

            return $response->successful();
        } catch (\Exception $e) {
            return false;
        }
    }
}
