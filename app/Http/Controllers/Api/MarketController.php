<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\CryptoMarketService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class MarketController extends Controller
{
    protected CryptoMarketService $marketService;

    public function __construct(CryptoMarketService $marketService)
    {
        $this->marketService = $marketService;
    }

    /**
     * Get ticker data for a symbol
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function ticker(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'symbol' => 'required|string|regex:/^[A-Z0-9]+$/|max:20',
            'fallback' => 'nullable|in:true,false,1,0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid symbol format',
                'errors' => $validator->errors(),
            ], 400);
        }

        $symbol = strtoupper($request->input('symbol'));
        $allowFallback = filter_var($request->input('fallback', true), FILTER_VALIDATE_BOOLEAN);
        
        $ticker = $this->marketService->getTickerWithFallback($symbol, $allowFallback);

        if (!$ticker) {
            return response()->json([
                'success' => false,
                'message' => 'Market data temporarily unavailable for this symbol',
                'data' => null,
            ], 503);
        }

        $isFallback = ($ticker['provider'] ?? '') === 'fallback';

        return response()->json([
            'success' => true,
            'message' => 'Market data retrieved successfully',
            'data' => $ticker,
            'is_fallback' => $isFallback,
            'note' => $isFallback ? 'Using demo/fallback data - provider unavailable' : null,
        ]);
    }

    /**
     * Get ticker data for multiple symbols
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function tickers(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'symbols' => 'nullable|string',
            'fallback' => 'nullable|in:true,false,1,0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid request format',
                'errors' => $validator->errors(),
            ], 400);
        }

        // Use provided symbols or default ones
        $symbolsInput = $request->input('symbols');
        $allowFallback = filter_var($request->input('fallback', true), FILTER_VALIDATE_BOOLEAN);
        
        if ($symbolsInput) {
            $symbols = array_map('trim', explode(',', $symbolsInput));
            
            // Validate each symbol
            foreach ($symbols as $symbol) {
                if (!preg_match('/^[A-Z0-9]+$/i', $symbol)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Invalid symbol format in list',
                        'data' => [],
                    ], 400);
                }
            }
        } else {
            $symbols = CryptoMarketService::getDefaultSymbols();
        }

        $tickers = $this->marketService->getMultipleTickersWithFallback($symbols, $allowFallback);

        $hasFallbackData = false;
        foreach ($tickers as $ticker) {
            if (($ticker['provider'] ?? '') === 'fallback') {
                $hasFallbackData = true;
                break;
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Market data retrieved successfully',
            'data' => $tickers,
            'count' => count($tickers),
            'is_fallback' => $hasFallbackData,
            'note' => $hasFallbackData ? 'Using demo/fallback data - provider unavailable' : null,
        ]);
    }

    /**
     * Get service health status
     *
     * @return JsonResponse
     */
    public function health(): JsonResponse
    {
        $isAvailable = $this->marketService->isAvailable();

        return response()->json([
            'success' => $isAvailable,
            'message' => $isAvailable ? 'Market data service is available' : 'Market data service is unavailable',
            'provider' => 'binance',
            'timestamp' => now()->toIso8601String(),
        ], $isAvailable ? 200 : 503);
    }

    /**
     * Get historical kline/candlestick data
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function klines(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'symbol' => 'required|string|regex:/^[A-Z0-9]+$/|max:20',
            'interval' => 'nullable|string|in:1m,3m,5m,15m,30m,1h,2h,4h,6h,8h,12h,1d,3d,1w,1M',
            'limit' => 'nullable|integer|min:1|max:1000',
            'fallback' => 'nullable|in:true,false,1,0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid request parameters',
                'errors' => $validator->errors(),
            ], 400);
        }

        $symbol = strtoupper($request->input('symbol'));
        $interval = $request->input('interval', '1m');
        $limit = (int) $request->input('limit', 100);
        $allowFallback = filter_var($request->input('fallback', true), FILTER_VALIDATE_BOOLEAN);

        $klines = $this->marketService->getKlinesWithFallback($symbol, $interval, $limit, $allowFallback);

        if (!$klines) {
            return response()->json([
                'success' => false,
                'message' => 'Historical market data temporarily unavailable for this symbol',
                'data' => [],
            ], 503);
        }

        return response()->json([
            'success' => true,
            'message' => 'Historical market data retrieved successfully',
            'data' => $klines,
            'count' => count($klines),
        ]);
    }

    /**
     * Get WebSocket connection info
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function websocket(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'symbol' => 'required|string|regex:/^[A-Z0-9]+$/|max:20',
            'stream' => 'nullable|string|in:ticker,kline_1m,kline_5m,kline_15m,kline_1h',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid request parameters',
                'errors' => $validator->errors(),
            ], 400);
        }

        $symbol = $request->input('symbol');
        $stream = $request->input('stream', 'ticker');

        $wsUrl = $this->marketService->getWebSocketUrl($symbol, $stream);

        return response()->json([
            'success' => true,
            'message' => 'WebSocket connection info retrieved',
            'data' => [
                'url' => $wsUrl,
                'symbol' => strtoupper($symbol),
                'stream' => $stream,
            ],
        ]);
    }
}
