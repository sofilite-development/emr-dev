import { ThemeProvider } from "@/components/theme-provider";
import {
    RouterProvider,
    createRouter,
    createHashHistory,
} from "@tanstack/react-router";
import { routeTree } from "./routeTree.gen";

// Check if we're in an iframe
const isInIframe = window.self !== window.top;

const router = createRouter({
    routeTree,
    history: isInIframe ? createHashHistory() : undefined,
});

// Register the router instance for type safety
declare module "@tanstack/react-router" {
    interface Register {
        router: typeof router;
    }
}

function App() {
    return (
        <>
            <ThemeProvider defaultTheme="dark" storageKey="vite-ui-theme">
                <RouterProvider router={router} />
            </ThemeProvider>
        </>
    );
}

export default App;
