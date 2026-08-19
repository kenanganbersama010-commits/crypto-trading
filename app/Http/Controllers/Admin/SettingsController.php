<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SystemSetting;
use App\Models\ApiCredential;
use App\Services\BinanceAuthService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class SettingsController extends Controller
{
    /**
     * Display the settings page.
     */
    public function index()
    {
        $dashboardName = SystemSetting::get('dashboard_name', 'Crypto Trading');
        $binanceCredential = ApiCredential::where('provider', 'binance')
            ->where('is_active', true)
            ->first();
        $indodaxCredential = ApiCredential::where('provider', 'indodax')
            ->where('is_active', true)
            ->first();
        $coingeckoCredential = ApiCredential::where('provider', 'coingecko')
            ->where('is_active', true)
            ->first();

        return view('admin.settings.index', [
            'dashboardName' => $dashboardName,
            'apiCredential' => $binanceCredential, // Keep for backward compatibility
            'binanceCredential' => $binanceCredential,
            'indodaxCredential' => $indodaxCredential,
            'coingeckoCredential' => $coingeckoCredential,
        ]);
    }

    /**
     * Update dashboard name.
     */
    public function updateDashboardName(Request $request)
    {
        $request->validate([
            'dashboard_name' => 'required|string|max:100',
        ]);

        SystemSetting::set('dashboard_name', $request->dashboard_name);

        return back()->with('status', 'dashboard-name-updated');
    }

    /**
     * Update admin profile photo.
     */
    public function updateProfilePhoto(Request $request)
    {
        $request->validate([
            'profile_photo' => 'required|image|mimes:jpg,jpeg,png,webp|max:2048', // 2MB max
        ]);

        $user = Auth::user();

        // Delete old photo if exists and not default
        if ($user->profile_photo && Storage::disk('public')->exists($user->profile_photo)) {
            Storage::disk('public')->delete($user->profile_photo);
        }

        // Store new photo
        $path = $request->file('profile_photo')->store('profile-photos', 'public');

        // Update user
        $user->update(['profile_photo' => $path]);

        return back()->with('status', 'profile-photo-updated');
    }

    /**
     * Update or create Binance API configuration.
     */
    public function updateBinanceApi(Request $request)
    {
        $request->validate([
            'api_key' => 'required|string|max:255',
            'api_secret' => 'required|string|max:255',
        ]);

        // Check if API credential already exists
        $credential = ApiCredential::where('provider', 'binance')
            ->where('is_active', true)
            ->first();

        if ($credential) {
            // Update existing
            $credential->update([
                'api_key' => $request->api_key,
                'api_secret' => $request->api_secret, // Will be auto-encrypted by model
            ]);
        } else {
            // Create new
            ApiCredential::create([
                'provider' => 'binance',
                'api_key' => $request->api_key,
                'api_secret' => $request->api_secret, // Will be auto-encrypted by model
                'is_active' => true,
            ]);
        }

        return back()->with('status', 'binance-api-updated');
    }

    /**
     * Delete Binance API configuration.
     */
    public function deleteBinanceApi()
    {
        ApiCredential::where('provider', 'binance')
            ->where('is_active', true)
            ->delete();

        return back()->with('status', 'binance-api-deleted');
    }
    
    /**
     * Test Binance API connection with stored credentials.
     */
    public function testBinanceConnection(BinanceAuthService $binanceAuth)
    {
        $result = $binanceAuth->testConnection();
        
        if ($result['success']) {
            return response()->json([
                'success' => true,
                'message' => $result['message'],
                'account' => $result['account'] ?? null,
            ]);
        }
        
        return response()->json([
            'success' => false,
            'message' => $result['message'],
        ], 400);
    }

    /**
     * Update or create Indodax API configuration.
     */
    public function updateIndodaxApi(Request $request)
    {
        $request->validate([
            'api_key' => 'required|string|max:255',
            'api_secret' => 'required|string|max:255',
        ]);

        $credential = ApiCredential::where('provider', 'indodax')
            ->where('is_active', true)
            ->first();

        if ($credential) {
            $credential->update([
                'api_key' => $request->api_key,
                'api_secret' => $request->api_secret,
            ]);
        } else {
            ApiCredential::create([
                'provider' => 'indodax',
                'api_key' => $request->api_key,
                'api_secret' => $request->api_secret,
                'is_active' => true,
            ]);
        }

        return back()->with('status', 'indodax-api-updated');
    }

    /**
     * Delete Indodax API configuration.
     */
    public function deleteIndodaxApi()
    {
        ApiCredential::where('provider', 'indodax')
            ->where('is_active', true)
            ->delete();

        return back()->with('status', 'indodax-api-deleted');
    }

    /**
     * Test Indodax API connection.
     */
    public function testIndodaxConnection()
    {
        $credential = ApiCredential::where('provider', 'indodax')
            ->where('is_active', true)
            ->first();

        if (!$credential) {
            return response()->json([
                'success' => false,
                'message' => 'Indodax API credentials not configured.',
            ], 400);
        }

        try {
            $baseUrl = rtrim(config('services.indodax.base_url'), '/');
            
            // Test with getInfo endpoint (requires authentication)
            $nonce = (int) (microtime(true) * 1000);
            $method = 'getInfo';
            
            $signData = http_build_query([
                'method' => $method,
                'timestamp' => $nonce,
            ]);
            
            $signature = hash_hmac('sha512', $signData, $credential->api_secret);
            
            $response = Http::timeout(10)
                ->asForm()
                ->withHeaders([
                    'Key' => $credential->api_key,
                    'Sign' => $signature,
                ])
                ->post("{$baseUrl}/tapi", [
                    'method' => $method,
                    'timestamp' => $nonce,
                ]);
            
            $data = $response->json();

            if (isset($data['success']) && $data['success'] === 1) {
                return response()->json([
                    'success' => true,
                    'message' => 'Indodax API connection successful!',
                    'account' => [
                        'name' => $data['return']['name'] ?? 'N/A',
                        'email' => $data['return']['email'] ?? 'N/A',
                    ],
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => $data['error'] ?? 'Connection failed. Please check your credentials.',
            ], 400);

        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Connection failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update or create CoinGecko API configuration.
     */
    public function updateCoingeckoApi(Request $request)
    {
        $request->validate([
            'api_key' => 'required|string|max:255',
        ]);

        $credential = ApiCredential::where('provider', 'coingecko')
            ->where('is_active', true)
            ->first();

        if ($credential) {
            $credential->update([
                'api_key' => $request->api_key,
                'api_secret' => null, // CoinGecko doesn't use secret
            ]);
        } else {
            ApiCredential::create([
                'provider' => 'coingecko',
                'api_key' => $request->api_key,
                'api_secret' => null,
                'is_active' => true,
            ]);
        }

        return back()->with('status', 'coingecko-api-updated');
    }

    /**
     * Delete CoinGecko API configuration.
     */
    public function deleteCoingeckoApi()
    {
        ApiCredential::where('provider', 'coingecko')
            ->where('is_active', true)
            ->delete();

        return back()->with('status', 'coingecko-api-deleted');
    }

    /**
     * Test CoinGecko API connection.
     */
    public function testCoingeckoConnection()
    {
        $credential = ApiCredential::where('provider', 'coingecko')
            ->where('is_active', true)
            ->first();

        if (!$credential) {
            return response()->json([
                'success' => false,
                'message' => 'CoinGecko API key not configured.',
            ], 400);
        }

        try {
            $baseUrl = rtrim(config('services.coingecko.base_url'), '/');
            
            // Test with simple/price endpoint (Bitcoin price)
            $response = Http::timeout(10)
                ->get("{$baseUrl}/simple/price", [
                    'ids' => 'bitcoin',
                    'vs_currencies' => 'usd',
                    'x_cg_demo_api_key' => $credential->api_key,
                ]);
            
            $data = $response->json();

            if (isset($data['bitcoin']['usd'])) {
                return response()->json([
                    'success' => true,
                    'message' => 'CoinGecko API connection successful!',
                    'data' => [
                        'bitcoin_price' => '$' . number_format($data['bitcoin']['usd'], 2),
                        'api_status' => 'Active',
                    ],
                ]);
            }

            // Check for error in response
            if (isset($data['status']['error_code'])) {
                return response()->json([
                    'success' => false,
                    'message' => $data['status']['error_message'] ?? 'API key validation failed.',
                ], 400);
            }

            return response()->json([
                'success' => false,
                'message' => 'Connection failed. Please check your API key.',
            ], 400);

        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Connection failed: ' . $e->getMessage(),
            ], 500);
        }
    }
}
