/**
 * Live Market Component for Alpine.js
 * Manages real-time market data and chart
 */

import { MarketWebSocket } from "../market-websocket.js";
import { MarketChart } from "../market-chart.js";

export function liveMarket() {
    return {
        symbol: "BTCUSDT",
        interval: "1m",

        // Market data
        price: 0,
        priceChange: 0,
        priceChangePercent: 0,
        high24h: 0,
        low24h: 0,
        volume24h: 0,

        // State
        status: "disconnected", // connecting, connected, disconnected, reconnecting, error
        isLoading: true,
        error: null,

        // Components
        ws: null,
        chart: null,

        // Initialize
        async init() {
            console.log("[Live Market] Initializing...");

            // Load historical data first
            await this.loadHistoricalData();

            // Initialize chart
            this.initChart();

            // Connect to WebSocket
            this.connectWebSocket();

            // Cleanup on page unload
            window.addEventListener("beforeunload", () => {
                this.cleanup();
            });
        },

        async loadHistoricalData() {
            try {
                const response = await axios.get("/api/market/klines", {
                    params: {
                        symbol: this.symbol,
                        interval: this.interval,
                        limit: 100,
                        fallback: true,
                    },
                });

                if (response.data.success && response.data.data) {
                    this.chart = response.data.data;
                    console.log(
                        `[Live Market] Loaded ${response.data.count} candles`,
                    );
                }
            } catch (error) {
                console.error(
                    "[Live Market] Failed to load historical data:",
                    error,
                );
                this.error = "Failed to load historical data";
            } finally {
                this.isLoading = false;
            }
        },

        initChart() {
            if (!this.chart || this.chart.length === 0) {
                console.warn("[Live Market] No chart data available");
                return;
            }

            try {
                // Initialize chart component
                const chartManager = new MarketChart("live-chart", {
                    interval: this.interval,
                });

                // Set historical data
                chartManager.setData(this.chart);

                // Store reference
                this.chartManager = chartManager;

                console.log("[Live Market] Chart initialized");
            } catch (error) {
                console.error(
                    "[Live Market] Failed to initialize chart:",
                    error,
                );
            }
        },

        connectWebSocket() {
            this.ws = new MarketWebSocket({
                symbol: this.symbol,
                stream: "ticker",

                onMessage: (data) => {
                    this.handleTickerUpdate(data);
                },

                onStatusChange: (status) => {
                    this.status = status;

                    // If WebSocket gave up after max attempts, show appropriate message
                    if (
                        status === "error" &&
                        this.ws &&
                        !this.ws.shouldReconnect
                    ) {
                        console.log(
                            "[Live Market] WebSocket unavailable - using static data",
                        );
                    }
                },

                onError: (error) => {
                    console.error("[Live Market] WebSocket error:", error);
                },
            });

            this.ws.connect();
        },

        handleTickerUpdate(data) {
            // Binance ticker stream format
            if (data.e === "24hrTicker") {
                this.price = parseFloat(data.c);
                this.priceChange = parseFloat(data.p);
                this.priceChangePercent = parseFloat(data.P);
                this.high24h = parseFloat(data.h);
                this.low24h = parseFloat(data.l);
                this.volume24h = parseFloat(data.v);

                // Update chart if available
                if (this.chartManager) {
                    this.chartManager.updateFromTicker({
                        price: this.price,
                    });
                }
            }
        },

        // Computed properties
        get isConnected() {
            return this.status === "connected";
        },

        get statusText() {
            const statusMap = {
                connecting: "Connecting...",
                connected: "Live",
                disconnected: "Disconnected",
                reconnecting: "Reconnecting...",
                error: "Error",
            };
            return statusMap[this.status] || "Unknown";
        },

        get statusColor() {
            const colorMap = {
                connecting: "text-yellow-400",
                connected: "text-emerald-400",
                disconnected: "text-slate-500",
                reconnecting: "text-yellow-400",
                error: "text-red-400",
            };
            return colorMap[this.status] || "text-slate-500";
        },

        get statusDot() {
            const dotMap = {
                connecting: "bg-yellow-400 animate-pulse",
                connected: "bg-emerald-400",
                disconnected: "bg-slate-500",
                reconnecting: "bg-yellow-400 animate-pulse",
                error: "bg-red-400",
            };
            return dotMap[this.status] || "bg-slate-500";
        },

        get priceChangeColor() {
            if (this.priceChangePercent > 0) return "text-emerald-400";
            if (this.priceChangePercent < 0) return "text-red-400";
            return "text-slate-400";
        },

        get priceChangeBg() {
            if (this.priceChangePercent > 0) return "bg-emerald-500/10";
            if (this.priceChangePercent < 0) return "bg-red-500/10";
            return "bg-slate-500/10";
        },

        formatPrice(value) {
            if (!value) return "0.00";
            return new Intl.NumberFormat("en-US", {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2,
            }).format(value);
        },

        formatVolume(value) {
            if (!value) return "0";

            if (value >= 1e9) {
                return (value / 1e9).toFixed(2) + "B";
            } else if (value >= 1e6) {
                return (value / 1e6).toFixed(2) + "M";
            } else if (value >= 1e3) {
                return (value / 1e3).toFixed(2) + "K";
            }

            return value.toFixed(0);
        },

        cleanup() {
            if (this.ws) {
                this.ws.disconnect();
            }

            if (this.chartManager) {
                this.chartManager.destroy();
            }

            console.log("[Live Market] Cleanup complete");
        },
    };
}
