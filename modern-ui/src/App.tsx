import { QueryClient, QueryClientProvider } from "@tanstack/react-query";
import { MantineProvider, ColorSchemeScript } from "@mantine/core";
import { Dashboard } from "./pages/dashboard";

function App() {
    const queryClient = new QueryClient();

    return (
        <QueryClientProvider client={queryClient}>
            <ColorSchemeScript />
            <MantineProvider>
                <Dashboard />
            </MantineProvider>
        </QueryClientProvider>
    );
}

export default App;
