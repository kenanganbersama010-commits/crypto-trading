/**
 * Live Markets Component for Alpine.js
 * Manages real-time market data for multiple cryptocurrencies
 */

export function liveMarkets() {
    return {
        markets: [],
        isLoading: true,
        error: null,
        status: "disconnected",

        // Market symbols to track
        symbols: [
            "BTCUSDT",
            "ETHUSDT",
            "BNBUSDT",
            "SOLUSDT",
            "XRPUSDT",
            "USDCUSDT",
        ],

        // Market display names
        displayNames: {
            BTCUSDT: { name: "Bitcoin", symbol: "BTC" },
            ETHUSDT: { name: "Ethereum", symbol: "ETH" },
            BNBUSDT: { name: "BNB", symbol: "BNB" },
            SOLUSDT: { name: "Solana", symbol: "SOL" },
            XRPUSDT: { name: "XRP", symbol: "XRP" },
            USDCUSDT: { name: "USD Coin", symbol: "USDC" },
        },

        async init() {
            console.log("[Live Markets] Initializing...");
            await this.loadMarketData();
            this.connectWebSocket();

            // Cleanup on page unload
            window.addEventListener("beforeunload", () => {
                this.cleanup();
            });
        },

        async loadMarketData() {
            try {
                const response = await axios.get("/api/market/tickers", {
                    params: {
                        symbols: this.symbols.join(","),
                        fallback: false,
                    },
                });

                if (response.data.success && response.data.data) {
                    this.markets = response.data.data.map((ticker) => {
                        const display = this.displayNames[ticker.symbol] || {
                            name: ticker.base_asset,
                            symbol: ticker.base_asset,
                        };

                        return {
                            symbol: ticker.symbol,
                            name: display.name,
                            shortSymbol: display.symbol,
                            price: ticker.price,
                            change24h: ticker.change_24h,
                            high24h: ticker.high_24h,
                            low24h: ticker.low_24h,
                            volume24h: ticker.volume_24h,
                        };
                    });

                    console.log(
                        `[Live Markets] Loaded ${this.markets.length} markets`,
                    );
                }
            } catch (error) {
                console.error(
                    "[Live Markets] Failed to load market data:",
                    error,
                );
                this.error = "Failed to load market data";
            } finally {
                this.isLoading = false;
            }
        },

        connectWebSocket() {
            // Use combined streams for multiple symbols
            const streams = this.symbols
                .map((s) => `${s.toLowerCase()}@ticker`)
                .join("/");
            const wsUrl = `wss://data-stream.binance.vision:443/stream?streams=${streams}`;

            this.ws = new WebSocket(wsUrl);

            this.ws.onopen = () => {
                this.status = "connected";
                console.log("[Live Markets] WebSocket connected");
            };

            this.ws.onmessage = (event) => {
                try {
                    const message = JSON.parse(event.data);

                    // Binance combined stream format
                    if (message.data && message.data.e === "24hrTicker") {
                        this.handleTickerUpdate(message.data);
                    }
                } catch (error) {
                    console.error(
                        "[Live Markets] Failed to parse WebSocket message:",
                        error,
                    );
                }
            };

            this.ws.onerror = (error) => {
                console.error("[Live Markets] WebSocket error:", error);
                this.status = "error";
            };

            this.ws.onclose = () => {
                this.status = "disconnected";
                console.log("[Live Markets] WebSocket closed");

                // Reconnect after 5 seconds
                setTimeout(() => {
                    if (this.status === "disconnected") {
                        console.log("[Live Markets] Reconnecting...");
                        this.connectWebSocket();
                    }
                }, 5000);
            };
        },

        handleTickerUpdate(data) {
            const symbol = data.s; // e.g., "BTCUSDT"
            const marketIndex = this.markets.findIndex(
                (m) => m.symbol === symbol,
            );

            if (marketIndex !== -1) {
                // Update market data
                this.markets[marketIndex].price = parseFloat(data.c);
                this.markets[marketIndex].change24h = parseFloat(data.P);
                this.markets[marketIndex].high24h = parseFloat(data.h);
                this.markets[marketIndex].low24h = parseFloat(data.l);
                this.markets[marketIndex].volume24h = parseFloat(data.v);
            }
        },

        formatPrice(price) {
            if (!price) return "0.00";

            // Use different precision based on price magnitude
            if (price >= 1000) {
                return new Intl.NumberFormat("en-US", {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2,
                }).format(price);
            } else if (price >= 1) {
                return new Intl.NumberFormat("en-US", {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 4,
                }).format(price);
            } else {
                return new Intl.NumberFormat("en-US", {
                    minimumFractionDigits: 4,
                    maximumFractionDigits: 6,
                }).format(price);
            }
        },

        formatChange(change) {
            if (!change) return "0.00";
            const sign = change >= 0 ? "+" : "";
            return `${sign}${change.toFixed(2)}%`;
        },

        isPositive(change) {
            return change >= 0;
        },

        getChangeColor(change) {
            return change >= 0
                ? "bg-emerald-500/10 text-emerald-400"
                : "bg-red-500/10 text-red-400";
        },

        get isConnected() {
            return this.status === "connected";
        },

        get statusText() {
            const statusMap = {
                connecting: "Connecting...",
                connected: "Live",
                disconnected: "Disconnected",
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
            console.log("[Live Markets] Cleanup complete");
        },
    };
}
