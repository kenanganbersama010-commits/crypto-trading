import "./bootstrap";

import Alpine from "alpinejs";
import { liveMarket } from "./components/live-market.js";

window.Alpine = Alpine;

// Register components
Alpine.data("liveMarket", liveMarket);

Alpine.start();
