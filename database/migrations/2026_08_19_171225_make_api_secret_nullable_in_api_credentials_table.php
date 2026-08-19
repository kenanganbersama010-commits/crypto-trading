<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Make api_secret nullable to support providers that only use API Key
     * (e.g., CoinGecko) while maintaining encryption for providers that 
     * require API Secret (e.g., Binance, Indodax).
     */
    public function up(): void
    {
        Schema::table('api_credentials', function (Blueprint $table) {
            $table->text('api_secret')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('api_credentials', function (Blueprint $table) {
            $table->text('api_secret')->nullable(false)->change();
        });
    }
};
