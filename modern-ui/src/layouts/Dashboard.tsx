import {  ScrollArea, Skeleton } from "@mantine/core";
import { Header } from "../features/dashboard/header";
export const DashboardLayout = ({
    children,
}: {
    children: React.ReactNode;
}) => {
    return (
        <>
            <main className="flex h-full w-full p-2 bg-blue-600">
                <aside className="w-[260px] pr-2">
                    {Array(15)
                        .fill(0)
                        .map((_, index) => (
                            <Skeleton
                                key={index}
                                h={28}
                                mt="sm"
                                animate={false}
                            />
                        ))}
                </aside>

                <div className="dark:bg-slate-900 bg-white w-full rounded-2xl">
                    <header className="h-[70px]">
                        <Header />
                    </header>

                    <ScrollArea className="h-[calc(100vh-86px)]">
                        {children}
                    </ScrollArea>
                </div>
            </main>
        </>
    );
};
