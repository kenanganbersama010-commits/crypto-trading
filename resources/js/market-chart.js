/**
 * Market Chart Manager
 * Handles candlestick chart rendering and updates using Lightweight Charts v5
 */

import { createChart, CandlestickSeries } from "lightweight-charts";

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
        // Check container dimensions
        const containerWidth = this.container.clientWidth;
        const containerHeight = this.container.clientHeight || 400;

        console.log(
            `[Market Chart] Container size: ${containerWidth}x${containerHeight}`,
        );

        if (containerWidth === 0 || containerHeight === 0) {
            console.error("[Market Chart] Invalid container dimensions");
            return;
        }

        // Chart options with dark theme (v5 API)
        const chartOptions = {
            width: containerWidth,
            height: containerHeight,
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
            },
            rightPriceScale: {
                borderColor: "rgba(148, 163, 184, 0.2)",
            },
            timeScale: {
                borderColor: "rgba(148, 163, 184, 0.2)",
                timeVisible: true,
                secondsVisible: false,
            },
        };

        this.chart = createChart(this.container, chartOptions);

        // Candlestick series options (v5 API)
        const candlestickOptions = {
            upColor: "#10b981",
            downColor: "#ef4444",
            borderUpColor: "#10b981",
            borderDownColor: "#ef4444",
            wickUpColor: "#10b981",
            wickDownColor: "#ef4444",
        };

        // V5 API: Use addSeries with CandlestickSeries definition
        this.candlestickSeries = this.chart.addSeries(
            CandlestickSeries,
            candlestickOptions,
        );

        // Handle resize
        this.handleResize();
        window.addEventListener("resize", () => this.handleResize());

        console.log(
            "[Market Chart] Chart initialized with Lightweight Charts v5",
        );
    }

    handleResize() {
        if (this.chart && this.container) {
            const width = this.container.clientWidth;
            const height = this.container.clientHeight || 400;

            if (width > 0 && height > 0) {
                this.chart.applyOptions({
                    width: width,
                    height: height,
                });
            }
        }
    }

    setData(candles) {
        if (!this.candlestickSeries || !candles || candles.length === 0) {
            return;
        }

        // Validate and sort candles
        const validCandles = candles
            .filter((candle) => {
                return (
                    candle.time &&
                    !isNaN(candle.open) &&
                    !isNaN(candle.high) &&
                    !isNaN(candle.low) &&
                    !isNaN(candle.close)
                );
            })
            .sort((a, b) => a.time - b.time);

        if (validCandles.length === 0) {
            console.error("[Market Chart] No valid candles to display");
            return;
        }

        console.log(`[Market Chart] Setting ${validCandles.length} candles`);
        console.log(`[Market Chart] First candle:`, validCandles[0]);
        console.log(
            `[Market Chart] Last candle:`,
            validCandles[validCandles.length - 1],
        );

        this.candlestickSeries.setData(validCandles);

        // Store the last candle as current
        if (validCandles.length > 0) {
            this.currentCandle = { ...validCandles[validCandles.length - 1] };
        }

        // Fit content
        this.chart.timeScale().fitContent();

        console.log(
            `[Market Chart] Successfully set ${validCandles.length} candles`,
        );
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
