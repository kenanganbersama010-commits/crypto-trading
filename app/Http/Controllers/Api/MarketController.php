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
            'fallback' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid symbol format',
                'errors' => $validator->errors(),
            ], 400);
        }

        $symbol = strtoupper($request->input('symbol'));
        $allowFallback = $request->input('fallback', true);
        
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
            'fallback' => 'nullable|boolean',
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
        $allowFallback = $request->input('fallback', true);
        
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
}
