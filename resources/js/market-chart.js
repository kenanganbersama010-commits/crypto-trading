/**
 * Market Chart Manager
 * Handles candlestick chart rendering and updates using Lightweight Charts
 */

import { createChart } from "lightweight-charts";

export class MarketChart {
    constructor(containerId, options = {}) {
        this.container = document.getElementById(containerId);

        if (!this.container) {
            throw new Error(`Container with id "${containerId}" not found`);
        }

        this.chart = null;
        this.candlestickSeries = null;
        this.currentCandle = null;
        this.interval = options.interval || "1m";

        this.initChart();
    }

    initChart() {
        // Chart options with dark theme
        const chartOptions = {
            layout: {
                background: { color: "transparent" },
                textColor: "#94a3b8",
            },
            grid: {
                vertLines: { color: "rgba(148, 163, 184, 0.1)" },
                horzLines: { color: "rgba(148, 163, 184, 0.1)" },
            },
            crosshair: {
                mode: 1,
                vertLine: {
                    width: 1,
                    color: "rgba(139, 92, 246, 0.5)",
                    style: 0,
                },
                horzLine: {
                    width: 1,
                    color: "rgba(139, 92, 246, 0.5)",
                    style: 0,
                },
            },
            rightPriceScale: {
                borderColor: "rgba(148, 163, 184, 0.2)",
            },
            timeScale: {
                borderColor: "rgba(148, 163, 184, 0.2)",
                timeVisible: true,
                secondsVisible: false,
            },
            handleScroll: {
                vertTouchDrag: true,
            },
            handleScale: {
                axisPressedMouseMove: true,
            },
        };

        this.chart = createChart(this.container, chartOptions);

        // Candlestick series options
        const candlestickOptions = {
            upColor: "#10b981",
            downColor: "#ef4444",
            borderUpColor: "#10b981",
            borderDownColor: "#ef4444",
            wickUpColor: "#10b981",
            wickDownColor: "#ef4444",
        };

        this.candlestickSeries =
            this.chart.addCandlestickSeries(candlestickOptions);

        // Handle resize
        this.handleResize();
        window.addEventListener("resize", () => this.handleResize());
    }

    handleResize() {
        if (this.chart && this.container) {
            this.chart.applyOptions({
                width: this.container.clientWidth,
                height: this.container.clientHeight || 400,
            });
        }
    }

    setData(candles) {
        if (!this.candlestickSeries || !candles || candles.length === 0) {
            return;
        }

        this.candlestickSeries.setData(candles);

        // Store the last candle as current
        if (candles.length > 0) {
            this.currentCandle = { ...candles[candles.length - 1] };
        }

        // Fit content
        this.chart.timeScale().fitContent();
    }

    updateCandle(newCandleData) {
        if (!this.candlestickSeries) {
            return;
        }

        // Update current candle
        this.candlestickSeries.update(newCandleData);
        this.currentCandle = { ...newCandleData };
    }

    addCandle(newCandleData) {
        if (!this.candlestickSeries) {
            return;
        }

        // Add new candle
        this.candlestickSeries.update(newCandleData);
        this.currentCandle = { ...newCandleData };
    }

    updateFromTicker(tickerData) {
        if (!this.currentCandle) {
            return;
        }

        const currentTime = Math.floor(Date.now() / 1000);
        const intervalSeconds = this.getIntervalSeconds();
        const candleStartTime =
            Math.floor(currentTime / intervalSeconds) * intervalSeconds;

        // Check if we need a new candle
        if (this.currentCandle.time < candleStartTime) {
            // Create new candle
            const newCandle = {
                time: candleStartTime,
                open: tickerData.price,
                high: tickerData.price,
                low: tickerData.price,
                close: tickerData.price,
            };
            this.addCandle(newCandle);
        } else {
            // Update current candle
            const updatedCandle = {
                time: this.currentCandle.time,
                open: this.currentCandle.open,
                high: Math.max(this.currentCandle.high, tickerData.price),
                low: Math.min(this.currentCandle.low, tickerData.price),
                close: tickerData.price,
            };
            this.updateCandle(updatedCandle);
        }
    }

    updateFromKline(klineData) {
        if (!this.candlestickSeries) {
            return;
        }

        const candle = {
            time: klineData.t ? Math.floor(klineData.t / 1000) : klineData.time,
            open: parseFloat(klineData.o || klineData.open),
            high: parseFloat(klineData.h || klineData.high),
            low: parseFloat(klineData.l || klineData.low),
            close: parseFloat(klineData.c || klineData.close),
        };

        // Check if this is updating current candle or creating new one
        if (this.currentCandle && candle.time === this.currentCandle.time) {
            this.updateCandle(candle);
        } else {
            this.addCandle(candle);
        }
    }

    getIntervalSeconds() {
        const intervalMap = {
            "1m": 60,
            "3m": 180,
            "5m": 300,
            "15m": 900,
            "30m": 1800,
            "1h": 3600,
            "2h": 7200,
            "4h": 14400,
            "1d": 86400,
        };
        return intervalMap[this.interval] || 60;
    }

    clear() {
        if (this.candlestickSeries) {
            this.candlestickSeries.setData([]);
        }
        this.currentCandle = null;
    }

    destroy() {
        if (this.chart) {
            this.chart.remove();
            this.chart = null;
        }
        this.candlestickSeries = null;
        this.currentCandle = null;
    }
}
