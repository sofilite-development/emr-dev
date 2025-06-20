import { defineConfig } from "vite";
import react from "@vitejs/plugin-react-swc";
import tailwindcss from "@tailwindcss/vite";

// https://vite.dev/config/
export default defineConfig({
    plugins: [react(), tailwindcss()],
    build: {
        cssCodeSplit: true,
        sourcemap: false,
        minify: "esbuild",
        rollupOptions: {
            output: {
                manualChunks: {
                    vendor: [
                        "react",
                        "react-dom",
                        "@mantine/core",
                        "@mantine/hooks",
                    ],
                },
                entryFileNames: "assets/[name].[hash].js",
                chunkFileNames: "assets/[name].[hash].js",
                assetFileNames: "assets/[name].[hash].[ext]",
            },
        },
    },
    optimizeDeps: {
        include: [
            "@mantine/core",
            "@mantine/hooks",
            "@mantine/notifications",
            "@mantine/dates",
            "@mantine/modals",
            "@tabler/icons-react",
            "react",
            "react-dom",
            "react-dom/client",
        ],
        esbuildOptions: {
            target: "es2020",
        },
    },
});
