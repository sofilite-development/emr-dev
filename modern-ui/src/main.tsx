import { StrictMode } from "react";
import { createRoot } from "react-dom/client";
import "./index.css";
import App from "./App.tsx";

// Handle navigation in iframe
if (window.self !== window.top) {
    // If in iframe, update parent URL when route changes
    const originalPushState = history.pushState;
    history.pushState = function () {
        originalPushState.apply(history, arguments as any);
        window.parent.postMessage(
            {
                type: "navigate",
                path: window.location.hash,
            },
            "*",
        );
    };
}

const rootElement =
    document.getElementById("root") || document.createElement("div");
if (!document.getElementById("root")) {
    rootElement.id = "root";
    document.body.appendChild(rootElement);
}

createRoot(rootElement).render(
    <StrictMode>
        <App />
    </StrictMode>,
);
