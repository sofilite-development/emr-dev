import { defineConfig } from "vite";
import react from "@vitejs/plugin-react-swc";
import path from "path";
import { tanstackRouter } from "@tanstack/router-plugin/vite";
import tailwindcss from "@tailwindcss/vite";

// https://vite.dev/config/
export default defineConfig({
    // base: "#",
    base:
        process.env.NODE_ENV === "production"
            ? "/interface/modern-ui/"
            : "./",
    // base: "./",
    plugins: [
        tanstackRouter({
            target: "react",
            autoCodeSplitting: true,
        }),
        react(),
        tailwindcss(),
    ],
    resolve: {
        alias: {
            "@": path.resolve(__dirname, "./src"),
        },
    },
    server: {
        proxy: {
            "/api": {
                target: "http://localhost:8000", // PHP server address
                changeOrigin: true,
                secure: false,
                rewrite: (path) => path.replace(/^\/api/, ""),
            },
        },
    },
    build: {
        outDir: "../interface/modern-ui",
        rollupOptions: {
            output: {
                entryFileNames: `assets/js/script.js`,
                chunkFileNames: `assets/js/[name].js`,
                assetFileNames: (assetInfo) => {
                    if (assetInfo.name && assetInfo.name.endsWith(".css")) {
                        return "assets/css/styles.css";
                    }
                    return "assets/[name][extname]";
                },
            },
        },
    },
});
