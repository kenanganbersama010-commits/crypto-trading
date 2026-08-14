/**
 * Hero Market Component for Alpine.js
 * Manages real-time BTC market data for Hero Section
 * Reuses existing market data from Live Markets component
 */

export function heroMarket() {
    return {
        market: null,
        isLoading: true,
        error: null,
        status: "disconnected",

        // BTC as default hero market
        symbol: "BTCUSDT",

        async init() {
            console.log("[Hero Market] Initializing...");
            await this.loadMarketData();
            this.connectWebSocket();

            // Cleanup on page unload
            window.addEventListener("beforeunload", () => {
                this.cleanup();
            });
        },

        async loadMarketData() {
            try {
                const response = await axios.get("/api/market/ticker", {
                    params: {
                        symbol: this.symbol,
                        fallback: false,
                    },
                });

                if (response.data.success && response.data.data) {
                    this.market = {
                        symbol: response.data.data.symbol,
                        price: response.data.data.price,
                        change24h: response.data.data.change_24h,
                        high24h: response.data.data.high_24h,
                        low24h: response.data.data.low_24h,
                        volume24h: response.data.data.volume_24h,
                    };

                    console.log("[Hero Market] Loaded BTC market data");
                }
            } catch (error) {
                console.error(
                    "[Hero Market] Failed to load market data:",
                    error,
                );
                this.error = "Failed to load market data";
            } finally {
                this.isLoading = false;
            }
        },

        connectWebSocket() {
            const stream = `${this.symbol.toLowerCase()}@ticker`;
            const wsUrl = `wss://data-stream.binance.vision:443/ws/${stream}`;

            this.ws = new WebSocket(wsUrl);

            this.ws.onopen = () => {
                this.status = "connected";
                console.log("[Hero Market] WebSocket connected");
            };

            this.ws.onmessage = (event) => {
                try {
                    const data = JSON.parse(event.data);

                    // Binance ticker format
                    if (data.e === "24hrTicker") {
                        this.handleTickerUpdate(data);
                    }
                } catch (error) {
                    console.error(
                        "[Hero Market] Failed to parse WebSocket message:",
                        error,
                    );
                }
            };

            this.ws.onerror = (error) => {
                console.error("[Hero Market] WebSocket error:", error);
                this.status = "error";
            };

            this.ws.onclose = () => {
                this.status = "disconnected";
                console.log("[Hero Market] WebSocket closed");

                // Reconnect after 5 seconds
                setTimeout(() => {
                    if (this.status === "disconnected") {
                        console.log("[Hero Market] Reconnecting...");
                        this.connectWebSocket();
                    }
                }, 5000);
            };
        },

        handleTickerUpdate(data) {
            if (this.market) {
                this.market.price = parseFloat(data.c);
                this.market.change24h = parseFloat(data.P);
                this.market.high24h = parseFloat(data.h);
                this.market.low24h = parseFloat(data.l);
                this.market.volume24h = parseFloat(data.v);
            }
        },

        formatPrice(price) {
            const numericValue = Number(price);
            if (!Number.isFinite(numericValue) || numericValue <= 0) {
                return "Loading...";
            }

            return new Intl.NumberFormat("en-US", {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2,
            }).format(numericValue);
        },

        formatChange(change) {
            const numericValue = Number(change);
            if (!Number.isFinite(numericValue)) {
                return "Loading...";
            }
            const sign = numericValue >= 0 ? "+" : "";
            return `${sign}${numericValue.toFixed(2)}%`;
        },

        isPositive(change) {
            const numericValue = Number(change);
            if (!Number.isFinite(numericValue)) {
                return true; // Default to positive for loading state
            }
            return numericValue >= 0;
        },

        getChangeColor(change) {
            const numericValue = Number(change);
            if (!Number.isFinite(numericValue)) {
                return "bg-slate-500/10 text-slate-400";
            }
            return numericValue >= 0
                ? "bg-emerald-500/10 text-emerald-400"
                : "bg-red-500/10 text-red-400";
        },

        getTextColor(change) {
            const numericValue = Number(change);
            if (!Number.isFinite(numericValue)) {
                return "text-slate-400";
            }
            return numericValue >= 0 ? "text-emerald-400" : "text-red-400";
        },

        get isConnected() {
            return this.status === "connected";
        },

        get statusText() {
            const statusMap = {
                connecting: "Connecting...",
                connected: "Live",
                disconnected: "Offline",
                error: "Error",
            };
            return statusMap[this.status] || "Unknown";
        },

        get statusColor() {
            const colorMap = {
                connecting: "text-yellow-400",
                connected: "text-emerald-400",
                disconnected: "text-slate-500",
                error: "text-red-400",
            };
            return colorMap[this.status] || "text-slate-500";
        },

        get statusDot() {
            const dotMap = {
                connecting: "bg-yellow-400 animate-pulse",
                connected: "bg-emerald-400",
                disconnected: "bg-slate-500",
                error: "bg-red-400",
            };
            return dotMap[this.status] || "bg-slate-500";
        },

        cleanup() {
            if (this.ws) {
                this.ws.close();
            }
            console.log("[Hero Market] Cleanup complete");
        },
    };
}
