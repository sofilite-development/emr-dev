import { ScrollArea } from "@/components/ui/scroll-area";
import { Header } from "../features/dashboard/header";
import { DashboardSidebar } from "../features/dashboard/sidebar";
import { SidebarProvider } from "@/components/ui/sidebar";
import { RootLayout } from "./Root";
export const DashboardLayout = ({
    children,
}: {
    children: React.ReactNode;
}) => {
    return (
        <RootLayout>
            <SidebarProvider>
                <DashboardSidebar />
                <div className=" flex h-full w-full bg-gray-50 dark:bg-gray-950">
                    <div className="  w-full rounded-l-[20px] rounded-2xl">
                        <Header />

                        <ScrollArea className="h-[calc(100vh-70px)] bg-background rounded-l-xl rounded-b-none border-l border-t">
                            {children}
                        </ScrollArea>
                    </div>
                </div>
            </SidebarProvider>
        </RootLayout>
    );
};
