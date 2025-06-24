import { UserProvider } from "@/store/user-context";
import { QueryClient, QueryClientProvider } from "@tanstack/react-query";

const queryClient = new QueryClient();

export function RootLayout({ children }: { children: React.ReactNode }) {

    return (
        <QueryClientProvider client={queryClient}>
            <UserProvider>
                <div id="react-root">{children}</div>
            </UserProvider>
        </QueryClientProvider>
    );
}
