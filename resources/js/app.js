import "./bootstrap";

import Alpine from "alpinejs";
import { liveMarket } from "./components/live-market.js";
import { liveMarkets } from "./components/live-markets.js";

window.Alpine = Alpine;

// Register components
Alpine.data("liveMarket", liveMarket);
Alpine.data("liveMarkets", liveMarkets);

Alpine.start();
