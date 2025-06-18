import { defineConfig } from "vite";
import react from "@vitejs/plugin-react";

// https://vite.dev/config/
export default defineConfig({
    plugins: [react()],
    build: {
        rollupOptions: {
            output: {
                // Set custom JS output filename
                entryFileNames: "assets/index.js",
                // Set custom CSS output filename
                assetFileNames: (assetInfo) => {
                    if (assetInfo.name && assetInfo.name.endsWith(".css")) {
                        return "assets/style.css";
                    }
                    return "assets/[name][extname]";
                },
            },
        },
    },
});
