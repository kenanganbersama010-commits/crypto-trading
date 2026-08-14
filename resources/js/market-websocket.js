/**
 * Market WebSocket Manager
 * Handles real-time market data connection with auto-reconnect
 */

export class MarketWebSocket {
    constructor(options = {}) {
        this.url = options.url || null;
        this.symbol = options.symbol || "BTCUSDT";
        this.stream = options.stream || "ticker";
        this.reconnectDelay = 1000; // Start with 1 second
        this.maxReconnectDelay = 30000; // Max 30 seconds
        this.reconnectAttempts = 0;
        this.maxReconnectAttempts = 10;
        this.ws = null;
        this.isConnecting = false;
        this.shouldReconnect = true;

        // Callbacks
        this.onMessage = options.onMessage || (() => {});
        this.onStatusChange = options.onStatusChange || (() => {});
        this.onError = options.onError || (() => {});
    }

    async connect() {
        if (
            this.isConnecting ||
            (this.ws && this.ws.readyState === WebSocket.OPEN)
        ) {
            return;
        }

        this.isConnecting = true;
        this.updateStatus("connecting");

        try {
            // If URL not provided, fetch from API
            if (!this.url) {
                await this.fetchWebSocketUrl();
            }

            this.ws = new WebSocket(this.url);

            this.ws.onopen = () => {
                this.isConnecting = false;
                this.reconnectAttempts = 0;
                this.reconnectDelay = 1000;
                this.updateStatus("connected");
                console.log(
                    `[Market WebSocket] Connected to ${this.symbol}@${this.stream}`,
                );
            };

            this.ws.onmessage = (event) => {
                try {
                    const data = JSON.parse(event.data);
                    this.onMessage(data);
                } catch (error) {
                    console.error(
                        "[Market WebSocket] Failed to parse message:",
                        error,
                    );
                }
            };

            this.ws.onerror = (error) => {
                console.error("[Market WebSocket] Error:", error);
                this.onError(error);
            };

            this.ws.onclose = () => {
                this.isConnecting = false;
                this.updateStatus("disconnected");
                console.log("[Market WebSocket] Connection closed");

                if (this.shouldReconnect) {
                    this.scheduleReconnect();
                }
            };
        } catch (error) {
            this.isConnecting = false;
            this.updateStatus("error");
            console.error("[Market WebSocket] Connection failed:", error);
            this.onError(error);

            if (this.shouldReconnect) {
                this.scheduleReconnect();
            }
        }
    }

    async fetchWebSocketUrl() {
        try {
            const response = await axios.get("/api/market/websocket", {
                params: {
                    symbol: this.symbol,
                    stream: this.stream,
                },
            });

            if (response.data.success) {
                this.url = response.data.data.url;
            } else {
                throw new Error("Failed to fetch WebSocket URL");
            }
        } catch (error) {
            console.error("[Market WebSocket] Failed to fetch URL:", error);
            throw error;
        }
    }

    scheduleReconnect() {
        if (this.reconnectAttempts >= this.maxReconnectAttempts) {
            this.shouldReconnect = false; // Stop trying after max attempts
            this.updateStatus("error");
            console.error(
                "[Market WebSocket] Max reconnect attempts reached - giving up",
            );
            console.log("[Market WebSocket] Using fallback data mode");
            return;
        }

        this.reconnectAttempts++;
        this.updateStatus("reconnecting");

        console.log(
            `[Market WebSocket] Reconnecting in ${this.reconnectDelay}ms (attempt ${this.reconnectAttempts}/${this.maxReconnectAttempts})`,
        );

        // Store timeout so we can clear it if needed
        this.reconnectTimeout = setTimeout(() => {
            this.reconnectTimeout = null;
            this.connect();
        }, this.reconnectDelay);

        // Exponential backoff
        this.reconnectDelay = Math.min(
            this.reconnectDelay * 1.5,
            this.maxReconnectDelay,
        );
    }

    updateStatus(status) {
        this.onStatusChange(status);
    }

    disconnect() {
        this.shouldReconnect = false;

        // Clear any pending reconnect timeout
        if (this.reconnectTimeout) {
            clearTimeout(this.reconnectTimeout);
            this.reconnectTimeout = null;
        }

        if (this.ws) {
            this.ws.close();
            this.ws = null;
        }

        this.updateStatus("disconnected");
    }

    isConnected() {
        return this.ws && this.ws.readyState === WebSocket.OPEN;
    }
}
