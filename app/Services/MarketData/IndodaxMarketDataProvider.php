<?php

namespace App\Services\MarketData;

use App\Contracts\MarketDataProvider;
use App\Models\ApiCredential;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Live prices from Indodax (a Bappebti-licensed Indonesian exchange), quoted
 * in USDT. No API key needed — /api/summaries is a public market-data
 * endpoint. Chosen over Binance because api.binance.com is blocked
 * nationwide for Indonesian ISPs by Kominfo's Trust+ Positif DNS filter.
 *
 * BTC/ETH/SOL/SHIB are all priced via Indodax's IDR pairs (its primary,
 * liquid markets), divided by the live USDT/IDR rate — not the *_usdt
 * pairs, which are thin enough to sit unchanged for minutes at a time (e.g.
 * eth_usdt did ~$1.87 of 24h volume when this was checked; see
 * config('market.indodax_pairs') for switching a symbol back to a direct
 * USDT pair if it ever becomes liquid enough). XAU (gold) has no Indodax
 * market at all — it's fetched separately from CoinGecko's public API
 * (pax-gold, a token pegged 1:1 to a troy ounce of physical gold) as a
 * supplement.
 *
 * Indodax and CoinGecko are cached independently, on purpose, with very
 * different TTLs (config('market.cache_ttl') vs config('market.
 * coingecko_cache_ttl')): Indodax has no documented rate limit trouble at a
 * 1-second refresh, but CoinGecko's free/keyless tier does — it starts
 * returning HTTP 429 within seconds under that pace. So the 4 Indodax
 * symbols refresh every second while XAU refreshes less often, rather than
 * either throttling everything down to CoinGecko's pace or dropping XAU.
 * If a live fetch fails, the last successful snapshot for that source is
 * served instead of breaking the site.
 *
 * Candles are synthetic (a deterministic seeded walk, reseeded hourly,
 * anchored to the real live price) — neither Indodax's public API nor
 * CoinGecko's free tier offers historical OHLCV matching our arbitrary
 * 1m/5m/15m/1h/4h/1d timeframe selector.
 */
class IndodaxMarketDataProvider implements MarketDataProvider
{
    /**
     * @var array<string, int>
     */
    private const TIMEFRAME_SECONDS = [
        '1m' => 60,
        '5m' => 300,
        '15m' => 900,
        '1h' => 3600,
        '4h' => 14400,
        '1d' => 86400,
    ];

    private const INDODAX_CACHE_KEY = 'market:indodax:quotes';

    private const INDODAX_STALE_CACHE_KEY = 'market:indodax:quotes:stale';

    private const COINGECKO_CACHE_KEY = 'market:coingecko:quotes';

    private const COINGECKO_STALE_CACHE_KEY = 'market:coingecko:quotes:stale';

    public function getPrice(string $symbol): ?float
    {
        return $this->getQuote($symbol)['price'] ?? null;
    }

    public function getQuote(string $symbol): ?array
    {
        $symbol = strtoupper($symbol);

        if ($symbol === 'USDT') {
            return $this->usdtQuote();
        }

        if (array_key_exists($symbol, config('market.coingecko_ids'))) {
            return $this->coingeckoQuotes()[$symbol] ?? null;
        }

        return $this->indodaxQuotes()[$symbol] ?? null;
    }

    public function listSymbols(): array
    {
        return [...array_keys(config('market.symbols')), 'USDT'];
    }

    /**
     * Synthetic candles seeded off the real live price — see class docblock
     * for why neither data source can supply real OHLCV here.
     */
    public function getCandles(string $symbol, string $timeframe, int $limit = 100): array
    {
        $symbol = strtoupper($symbol);
        $quote = $this->getQuote($symbol);

        if (! $quote) {
            return [];
        }

        $timeframe = strtolower($timeframe);
        $intervalSeconds = self::TIMEFRAME_SECONDS[$timeframe] ?? self::TIMEFRAME_SECONDS['1h'];

        $now = time();
        $now -= $now % $intervalSeconds;

        $hourBucket = $now - ($now % 3600);
        mt_srand(crc32($symbol.$timeframe.$hourBucket));

        $price = $quote['price'] * 0.97;
        $candles = [];

        for ($i = $limit - 1; $i >= 0; $i--) {
            $time = $now - ($i * $intervalSeconds);
            $volatility = max($price * 0.008, 0.00000001);

            $open = $price;
            $close = max($open + (mt_rand(-1000, 1000) / 1000) * $volatility, $price * 0.1);
            $high = max($open, $close) + (mt_rand(0, 400) / 1000) * $volatility;
            $low = max(min($open, $close) - (mt_rand(0, 400) / 1000) * $volatility, 0.00000001);

            $candles[] = [
                'time' => $time,
                'open' => round($open, 8),
                'high' => round($high, 8),
                'low' => round($low, 8),
                'close' => round($close, 8),
                'volume' => round(mt_rand(1000, 100000) / 100, 2),
            ];

            $price = $close;
        }

        // Last candle always closes exactly on the real live price.
        $candles[count($candles) - 1]['close'] = round($quote['price'], 8);
        $candles[count($candles) - 1]['high'] = max($candles[count($candles) - 1]['high'], $quote['price']);
        $candles[count($candles) - 1]['low'] = min($candles[count($candles) - 1]['low'], $quote['price']);

        mt_srand();

        return $candles;
    }

    private function usdtQuote(): array
    {
        return [
            'symbol' => 'USDT',
            'name' => 'Tether',
            'price' => 1.0,
            'change_24h' => 0.0,
            'high_24h' => 1.0,
            'low_24h' => 1.0,
            'volume_24h' => 0,
        ];
    }

    /**
     * @return array<string, array{symbol: string, name: string, price: float, change_24h: float, high_24h: float, low_24h: float, volume_24h: float}>
     */
    private function indodaxQuotes(): array
    {
        return Cache::remember(self::INDODAX_CACHE_KEY, config('market.cache_ttl', 1), function () {
            try {
                $baseUrl = rtrim(config('services.indodax.base_url'), '/');

                $response = Http::timeout(10)
                    ->get("{$baseUrl}/api/summaries")
                    ->throw()
                    ->json();

                $fresh = $this->parseIndodaxTickers($response['tickers'] ?? [], config('market.symbols'));

                if ($fresh !== []) {
                    Cache::put(self::INDODAX_STALE_CACHE_KEY, $fresh, now()->addDay());
                }

                return $fresh;
            } catch (\Throwable $e) {
                Log::error('Indodax price fetch failed, serving stale cache.', ['error' => $e->getMessage()]);

                return Cache::get(self::INDODAX_STALE_CACHE_KEY, []);
            }
        });
    }

    /**
     * @return array<string, array{symbol: string, name: string, price: float, change_24h: float, high_24h: float, low_24h: float, volume_24h: float}>
     */
    private function coingeckoQuotes(): array
    {
        return Cache::remember(self::COINGECKO_CACHE_KEY, config('market.coingecko_cache_ttl', 30), function () {
            try {
                $baseUrl = rtrim(config('services.coingecko.base_url'), '/');
                
                // Try to get API key from database first, fallback to config
                $credential = ApiCredential::where('provider', 'coingecko')
                    ->where('is_active', true)
                    ->first();
                
                $key = $credential ? $credential->api_key : config('services.coingecko.key');
                $ids = config('market.coingecko_ids');

                // Build query parameters
                $params = [
                    'vs_currency' => 'usd',
                    'ids' => implode(',', $ids),
                ];
                
                // Add API key as query parameter if available
                if (filled($key)) {
                    $params['x_cg_demo_api_key'] = $key;
                }

                $response = Http::timeout(10)
                    ->get("{$baseUrl}/coins/markets", $params)
                    ->throw()
                    ->json();

                $fresh = $this->parseCoinGeckoMarkets($response ?? [], $ids, config('market.symbols'));

                if ($fresh !== []) {
                    Cache::put(self::COINGECKO_STALE_CACHE_KEY, $fresh, now()->addDay());
                }

                return $fresh;
            } catch (\Throwable $e) {
                Log::error('CoinGecko price fetch failed, serving stale cache.', ['error' => $e->getMessage()]);

                return Cache::get(self::COINGECKO_STALE_CACHE_KEY, []);
            }
        });
    }

    /**
     * @param  array<string, array{last?: string, high?: string, low?: string, vol_usdt?: string, vol_idr?: string}>  $tickers
     * @param  array<string, string>  $names
     * @return array<string, array{symbol: string, name: string, price: float, change_24h: float, high_24h: float, low_24h: float, volume_24h: float}>
     */
    private function parseIndodaxTickers(array $tickers, array $names): array
    {
        $quotes = [];

        foreach (config('market.indodax_pairs') as $symbol => $pairKey) {
            $ticker = $tickers[$pairKey] ?? null;

            if (! $ticker) {
                continue;
            }

            $quotes[$symbol] = $this->buildQuoteFromTicker(
                $symbol,
                $names[$symbol],
                $ticker,
                (float) ($ticker['vol_usdt'] ?? 0),
            );
        }

        $usdtIdrTicker = $tickers[config('market.indodax_usdt_idr_pair')] ?? null;
        $usdtIdrRate = $usdtIdrTicker ? (float) $usdtIdrTicker['last'] : null;

        if ($usdtIdrRate) {
            foreach (config('market.indodax_cross_pairs') as $symbol => $pairKey) {
                $ticker = $tickers[$pairKey] ?? null;

                if (! $ticker) {
                    continue;
                }

                $quotes[$symbol] = $this->buildQuoteFromTicker(
                    $symbol,
                    $names[$symbol],
                    $ticker,
                    (float) ($ticker['vol_idr'] ?? 0) / $usdtIdrRate,
                    $usdtIdrRate,
                );
            }
        }

        return $quotes;
    }

    /**
     * @param  list<array<string, mixed>>  $coins
     * @param  array<string, string>  $coingeckoIds
     * @param  array<string, string>  $names
     * @return array<string, array{symbol: string, name: string, price: float, change_24h: float, high_24h: float, low_24h: float, volume_24h: float}>
     */
    private function parseCoinGeckoMarkets(array $coins, array $coingeckoIds, array $names): array
    {
        $byId = collect($coins)->keyBy('id');
        $quotes = [];

        foreach ($coingeckoIds as $symbol => $coinId) {
            $coin = $byId->get($coinId);

            if (! $coin || ! isset($coin['current_price'])) {
                continue;
            }

            $price = (float) $coin['current_price'];

            $quotes[$symbol] = [
                'symbol' => $symbol,
                'name' => $coin['name'] ?? $names[$symbol],
                'price' => round($price, 8),
                'change_24h' => round((float) ($coin['price_change_percentage_24h'] ?? 0), 2),
                'high_24h' => round((float) ($coin['high_24h'] ?? $price), 8),
                'low_24h' => round((float) ($coin['low_24h'] ?? $price), 8),
                'volume_24h' => round((float) ($coin['total_volume'] ?? 0), 2),
            ];
        }

        return $quotes;
    }

    /**
     * @param  array{last?: string, high?: string, low?: string}  $ticker
     * @return array{symbol: string, name: string, price: float, change_24h: float, high_24h: float, low_24h: float, volume_24h: float}|null
     */
    private function buildQuoteFromTicker(string $symbol, string $name, array $ticker, float $volume24h, ?float $divideBy = null): ?array
    {
        if (! isset($ticker['last'])) {
            return null;
        }

        $divisor = $divideBy ?: 1.0;
        $price = (float) $ticker['last'] / $divisor;
        $high = (float) ($ticker['high'] ?? $ticker['last']) / $divisor;
        $low = (float) ($ticker['low'] ?? $ticker['last']) / $divisor;

        // Indodax's ticker has no explicit 24h % change or open field —
        // approximated from live price vs. the midpoint of the 24h
        // high/low range, the closest available signal.
        $mid = ($high + $low) / 2;
        $changePercent = $mid > 0 ? (($price - $mid) / $mid) * 100 : 0.0;

        return [
            'symbol' => $symbol,
            'name' => $name,
            'price' => round($price, 8),
            'change_24h' => round($changePercent, 2),
            'high_24h' => round(max($high, $price), 8),
            'low_24h' => round(min($low, $price), 8),
            'volume_24h' => round($volume24h, 2),
        ];
    }
}
