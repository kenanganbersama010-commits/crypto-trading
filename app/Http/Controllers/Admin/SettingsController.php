<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SystemSetting;
use App\Models\ApiCredential;
use App\Services\BinanceAuthService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
        $apiCredential = ApiCredential::where('provider', 'binance')
            ->where('is_active', true)
            ->first();

        return view('admin.settings.index', [
            'dashboardName' => $dashboardName,
            'apiCredential' => $apiCredential,
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
}
