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

    /**
     * Get historical kline/candlestick data
     *
     * @param string $symbol e.g., 'BTCUSDT'
     * @param string $interval e.g., '1m', '5m', '15m', '1h', '4h', '1d'
     * @param int $limit Number of candles (default: 100, max: 1000)
     * @return array|null
     */
    public function getKlines(string $symbol, string $interval = '1m', int $limit = 100): ?array
    {
        $symbol = strtoupper($symbol);

        // Validate symbol
        if (!preg_match('/^[A-Z0-9]+$/', $symbol)) {
            Log::warning('Invalid symbol format', ['symbol' => $symbol]);
            return null;
        }

        // Validate interval
        $validIntervals = ['1m', '3m', '5m', '15m', '30m', '1h', '2h', '4h', '6h', '8h', '12h', '1d', '3d', '1w', '1M'];
        if (!in_array($interval, $validIntervals)) {
            Log::warning('Invalid interval', ['interval' => $interval]);
            return null;
        }

        // Limit between 1 and 1000
        $limit = max(1, min(1000, $limit));

        $cacheKey = "crypto_klines_{$symbol}_{$interval}_{$limit}";

        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($symbol, $interval, $limit) {
            return $this->fetchKlines($symbol, $interval, $limit);
        });
    }

    /**
     * Fetch klines from Binance API
     *
     * @param string $symbol
     * @param string $interval
     * @param int $limit
     * @return array|null
     */
    protected function fetchKlines(string $symbol, string $interval, int $limit): ?array
    {
        try {
            $response = Http::timeout($this->timeout)
                ->connectTimeout(3)
                ->get("{$this->baseUrl}/api/v3/klines", [
                    'symbol' => $symbol,
                    'interval' => $interval,
                    'limit' => $limit
                ]);

            if ($response->successful()) {
                $data = $response->json();
                return $this->normalizeKlines($data);
            }

            Log::warning('Binance klines API error', [
                'symbol' => $symbol,
                'interval' => $interval,
                'status' => $response->status(),
            ]);

            return null;
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error('Binance klines API connection timeout', [
                'symbol' => $symbol,
                'interval' => $interval,
                'message' => 'Connection timeout or network error'
            ]);
            return null;
        } catch (\Exception $e) {
            Log::error('Binance klines API exception', [
                'symbol' => $symbol,
                'interval' => $interval,
                'message' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Normalize klines data to internal format
     *
     * @param array $data
     * @return array
     */
    protected function normalizeKlines(array $data): array
    {
        $normalized = [];

        foreach ($data as $kline) {
            // Binance kline format:
            // [
            //   0: Open time
            //   1: Open
            //   2: High
            //   3: Low
            //   4: Close
            //   5: Volume
            //   6: Close time
            //   7: Quote asset volume
            //   8: Number of trades
            //   9: Taker buy base asset volume
            //   10: Taker buy quote asset volume
            //   11: Ignore
            // ]
            $normalized[] = [
                'time' => (int) ($kline[0] / 1000), // Convert to seconds
                'open' => (float) $kline[1],
                'high' => (float) $kline[2],
                'low' => (float) $kline[3],
                'close' => (float) $kline[4],
                'volume' => (float) $kline[5],
            ];
        }

        return $normalized;
    }

    /**
     * Get WebSocket URL for symbol stream
     *
     * @param string $symbol
     * @param string $stream e.g., 'ticker', 'kline_1m'
     * @return string
     */
    public function getWebSocketUrl(string $symbol, string $stream = 'kline_1m'): string
    {
        $wsBaseUrl = config('services.binance.ws_url', 'wss://data-stream.binance.vision:443');
        $symbolLower = strtolower($symbol);
        
        return "{$wsBaseUrl}/ws/{$symbolLower}@{$stream}";
    }

    /**
     * Get fallback klines data
     *
     * @param string $symbol
     * @param int $limit
     * @return array|null
     */
    public function getFallbackKlines(string $symbol, int $limit = 100): ?array
    {
        // Generate realistic-looking demo candles based on current fallback price
        $fallbackTicker = $this->getFallbackData($symbol);
        
        if (!$fallbackTicker) {
            return null;
        }

        $currentPrice = $fallbackTicker['price'];
        $candles = [];
        $timestamp = time() - ($limit * 60); // Start from $limit minutes ago

        for ($i = 0; $i < $limit; $i++) {
            $variance = ($currentPrice * 0.002); // 0.2% variance
            $open = $currentPrice + (mt_rand(-100, 100) / 100) * $variance;
            $high = $open + (mt_rand(0, 100) / 100) * $variance;
            $low = $open - (mt_rand(0, 100) / 100) * $variance;
            $close = $open + (mt_rand(-100, 100) / 100) * $variance;

            // Ensure high is highest and low is lowest
            $high = max($high, $open, $close);
            $low = min($low, $open, $close);

            $candles[] = [
                'time' => $timestamp + ($i * 60),
                'open' => round($open, 2),
                'high' => round($high, 2),
                'low' => round($low, 2),
                'close' => round($close, 2),
                'volume' => mt_rand(100, 1000),
            ];

            $currentPrice = $close; // Next candle starts from previous close
        }

        return $candles;
    }

    /**
     * Get klines with fallback support
     *
     * @param string $symbol
     * @param string $interval
     * @param int $limit
     * @param bool $allowFallback
     * @return array|null
     */
    public function getKlinesWithFallback(string $symbol, string $interval = '1m', int $limit = 100, bool $allowFallback = true): ?array
    {
        $klines = $this->getKlines($symbol, $interval, $limit);

        if (!$klines && $allowFallback) {
            return $this->getFallbackKlines($symbol, $limit);
        }

        return $klines;
    }
}
