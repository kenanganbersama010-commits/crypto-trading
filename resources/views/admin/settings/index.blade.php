@extends('layouts.admin')

@section('title', 'Settings')

@section('content')
<div x-data="{ loading: false }" @submit="loading = true">
    <!-- Header Section with Gradient -->
    <div class="bg-gradient-to-br from-blue-600 via-blue-700 to-blue-800 text-white -mt-6 sm:-mt-6 lg:-mt-8 -mx-4 sm:-mx-6 lg:-mx-8 px-4 sm:px-6 lg:px-8 pt-6 pb-20 relative overflow-hidden">
        <div class="absolute top-0 right-0 w-96 h-96 bg-white/5 rounded-full -mr-32 -mt-32 blur-3xl"></div>
        <div class="absolute bottom-0 left-0 w-64 h-64 bg-blue-500/10 rounded-full -ml-32 -mb-32 blur-3xl"></div>
        
        <div class="relative z-10 max-w-7xl mx-auto">
            <p class="text-blue-200 text-sm font-semibold mb-1 uppercase tracking-wider">System</p>
            <h1 class="text-2xl sm:text-3xl md:text-4xl font-bold mb-2">Settings</h1>
            <nav class="flex items-center text-sm text-blue-200">
                <a href="{{ route('admin.dashboard') }}" class="hover:text-white transition-colors">Dashboard</a>
                <svg class="w-4 h-4 mx-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                <span class="text-white font-medium">Settings</span>
            </nav>
        </div>
    </div>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto">
        <div class="-mt-12 pb-8 relative z-20 space-y-6">

            @if (session('status'))
                <div class="bg-emerald-50 border border-emerald-200 rounded-xl flex items-center gap-3 p-4 shadow-sm">
                    <svg class="w-6 h-6 text-emerald-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <p class="text-sm font-semibold text-emerald-800">
                        @if (session('status') === 'dashboard-name-updated') Dashboard name updated successfully.
                        @elseif (session('status') === 'profile-photo-updated') Profile photo updated successfully.
                        @elseif (session('status') === 'binance-api-updated') Binance API configuration updated successfully.
                        @elseif (session('status') === 'binance-api-deleted') Binance API configuration deleted successfully.
                        @else {{ session('status') }}
                        @endif
                    </p>
                </div>
            @endif

            @if ($errors->any())
                <div class="bg-red-50 border border-red-200 rounded-xl p-4 shadow-sm">
                    <div class="flex items-start gap-3">
                        <svg class="w-6 h-6 text-red-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        <div class="flex-1">
                            <p class="text-sm font-semibold text-red-800 mb-1">Please fix the following errors:</p>
                            <ul class="text-sm text-red-700 list-disc list-inside space-y-1">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>
            @endif

            <!-- General Settings & Profile Settings Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                
                <!-- General Settings Card -->
                <div class="bg-white rounded-2xl shadow-lg shadow-gray-200/50 border border-gray-100 overflow-hidden">
                    <div class="border-b border-gray-100 px-6 py-5 bg-gradient-to-r from-gray-50 to-white">
                        <h2 class="text-lg font-bold text-gray-900 flex items-center gap-2">
                            <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                            General Settings
                        </h2>
                        <p class="text-sm text-gray-600 mt-1">Configure dashboard name and system preferences</p>
                    </div>

                    <form action="{{ route('admin.settings.dashboard-name') }}" method="POST" class="p-6">
                        @csrf
                        <div class="space-y-4">
                            <div>
                                <label for="dashboard_name" class="block text-sm font-semibold text-gray-700 mb-2">
                                    Dashboard Name
                                </label>
                                <input 
                                    type="text" 
                                    id="dashboard_name"
                                    name="dashboard_name"
                                    value="{{ old('dashboard_name', $dashboardName) }}"
                                    class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-500 focus:ring-opacity-20 outline-none transition-all text-gray-900 font-medium"
                                    placeholder="Enter dashboard name"
                                    required
                                >
                                <p class="text-xs text-gray-500 mt-2">This name will appear in the browser title and layout.</p>
                            </div>

                            <div class="flex justify-end pt-2">
                                <button 
                                    type="submit"
                                    class="w-full sm:w-auto inline-flex items-center justify-center gap-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold px-6 py-3 rounded-lg shadow-sm transition-all"
                                >
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                    Save Changes
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Profile Settings Card -->
                <div class="bg-white rounded-2xl shadow-lg shadow-gray-200/50 border border-gray-100 overflow-hidden">
                    <div class="border-b border-gray-100 px-6 py-5 bg-gradient-to-r from-gray-50 to-white">
                        <h2 class="text-lg font-bold text-gray-900 flex items-center gap-2">
                            <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                            Profile Settings
                        </h2>
                        <p class="text-sm text-gray-600 mt-1">Update your profile photo and personal information</p>
                    </div>

                    <form action="{{ route('admin.settings.profile-photo') }}" method="POST" enctype="multipart/form-data" class="p-6">
                        @csrf
                        <div class="space-y-6">
                            <!-- Current Photo -->
                            <div class="flex flex-col sm:flex-row items-center sm:items-center gap-4 sm:gap-6">
                                <div class="flex-shrink-0">
                                    @if(Auth::user()->profile_photo)
                                        <img src="{{ asset('storage/' . Auth::user()->profile_photo) }}" alt="Profile" class="w-20 h-20 rounded-full object-cover border-4 border-gray-100 shadow-md">
                                    @else
                                        <div class="w-20 h-20 rounded-full flex items-center justify-center text-3xl font-bold bg-gradient-to-br from-blue-500 to-blue-600 text-white shadow-md border-4 border-gray-100">
                                            {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                                        </div>
                                    @endif
                                </div>
                                <div class="flex-1 text-center sm:text-left">
                                    <h3 class="text-sm font-semibold text-gray-900 mb-1">{{ Auth::user()->name }}</h3>
                                    <p class="text-sm text-gray-600 mb-2">{{ Auth::user()->email }}</p>
                                    <p class="text-xs text-gray-500">JPG, JPEG, PNG, or WEBP (Max 2MB)</p>
                                </div>
                            </div>

                            <!-- Upload New Photo -->
                            <div>
                                <label for="profile_photo" class="block text-sm font-semibold text-gray-700 mb-2">
                                    Upload New Photo
                                </label>
                                <input 
                                    type="file" 
                                    id="profile_photo"
                                    name="profile_photo"
                                    accept="image/jpeg,image/jpg,image/png,image/webp"
                                    class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:border-purple-500 focus:ring-2 focus:ring-purple-500 focus:ring-opacity-20 outline-none transition-all text-gray-900"
                                    required
                                >
                            </div>

                            <div class="flex justify-end pt-2">
                                <button 
                                    type="submit"
                                    class="w-full sm:w-auto inline-flex items-center justify-center gap-2 bg-purple-600 hover:bg-purple-700 text-white font-semibold px-6 py-3 rounded-lg shadow-sm transition-all"
                                >
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                                    Update Profile
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
                
            </div>

            <!-- Binance API Settings Card -->
            <div class="bg-white rounded-2xl shadow-lg shadow-gray-200/50 border border-gray-100 overflow-hidden">
                <div class="border-b border-gray-100 px-6 py-5 bg-gradient-to-r from-gray-50 to-white">
                    <h2 class="text-lg font-bold text-gray-900 flex items-center gap-2">
                        <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                        Binance API Configuration
                    </h2>
                    <p class="text-sm text-gray-600 mt-1">Configure your Binance API credentials for trading operations</p>
                </div>

                <form action="{{ route('admin.settings.binance-api') }}" method="POST" class="p-6">
                    @csrf
                    <div class="space-y-4">
                        <!-- Security Notice -->
                        <div class="bg-amber-50 border border-amber-200 rounded-lg p-4">
                            <div class="flex items-start gap-3">
                                <svg class="w-5 h-5 text-amber-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                                <div>
                                    <p class="text-sm font-semibold text-amber-900">Security Notice</p>
                                    <p class="text-xs text-amber-800 mt-1">Your API Secret is encrypted and will never be displayed after saving. Do not use API keys with withdrawal permissions.</p>
                                </div>
                            </div>
                        </div>

                        <div>
                            <label for="api_key" class="block text-sm font-semibold text-gray-700 mb-2">
                                API Key
                            </label>
                            <input 
                                type="text" 
                                id="api_key"
                                name="api_key"
                                value="{{ old('api_key', $apiCredential ? $apiCredential->masked_api_key : '') }}"
                                class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:border-amber-500 focus:ring-2 focus:ring-amber-500 focus:ring-opacity-20 outline-none transition-all text-gray-900 font-mono text-sm"
                                placeholder="Enter your Binance API Key"
                                required
                            >
                            @if($apiCredential)
                                <p class="text-xs text-gray-500 mt-2">Current API Key is masked for security.</p>
                            @endif
                        </div>

                        <div>
                            <label for="api_secret" class="block text-sm font-semibold text-gray-700 mb-2">
                                API Secret
                            </label>
                            <input 
                                type="password" 
                                id="api_secret"
                                name="api_secret"
                                class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:border-amber-500 focus:ring-2 focus:ring-amber-500 focus:ring-opacity-20 outline-none transition-all text-gray-900 font-mono text-sm"
                                placeholder="{{ $apiCredential ? '••••••••••••••••' : 'Enter your Binance API Secret' }}"
                                required
                            >
                            @if($apiCredential)
                                <p class="text-xs text-gray-500 mt-2">Enter new API Secret to update the existing one.</p>
                            @endif
                        </div>

                        <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between pt-2 gap-3">
                            @if($apiCredential)
                                <form action="{{ route('admin.settings.binance-api.delete') }}" method="POST" class="w-full sm:w-auto" onsubmit="return confirm('Are you sure you want to delete the Binance API configuration?');">
                                    @csrf
                                    @method('DELETE')
                                    <button 
                                        type="submit"
                                        class="w-full sm:w-auto inline-flex items-center justify-center gap-2 bg-red-600 hover:bg-red-700 text-white font-semibold px-6 py-3 rounded-lg shadow-sm transition-all"
                                    >
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                        Delete Configuration
                                    </button>
                                </form>
                            @else
                                <div></div>
                            @endif
                            
                            <button 
                                type="submit"
                                class="w-full sm:w-auto inline-flex items-center justify-center gap-2 bg-amber-600 hover:bg-amber-700 text-white font-semibold px-6 py-3 rounded-lg shadow-sm transition-all"
                            >
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                {{ $apiCredential ? 'Update Configuration' : 'Save Configuration' }}
                            </button>
                        </div>
                    </div>
                </form>
            </div>

        </div>
    </div>

    <!-- Loading Overlay -->
    <div
        x-show="loading"
        style="display: none;"
        class="fixed inset-0 z-50 flex items-center justify-center gap-2 bg-white/60 backdrop-blur-sm"
        role="status"
        aria-live="polite"
    >
        <svg class="animate-spin h-8 w-8 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        <span class="text-sm font-semibold text-blue-900">Saving changes...</span>
    </div>
</div>
@endsection
