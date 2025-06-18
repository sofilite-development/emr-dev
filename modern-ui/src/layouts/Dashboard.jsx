import { AppShell, Skeleton,  } from "@mantine/core";
// import { useDisclosure } from "@mantine/hooks";
import { Header } from "../features/dashboard/header";
export const DashboardLayout = ({ children }) => {
    // const [mobileOpened, { toggle: toggleMobile }] = useDisclosure();
    // const [desktopOpened, { toggle: toggleDesktop }] = useDisclosure(true);
    return (
        <>
            <AppShell
                layout="alt"
                header={{ height: 60 }}
                classNames={{
                    root:"!p-2 bg-red-500"
                }}
                navbar={{
                    width: 300,
                    breakpoint: "sm",
                    // collapsed: {
                    //     mobile: !mobileOpened,
                    //     desktop: !desktopOpened,
                    // },
                }}
                padding="md"
            >
                <AppShell.Header>
                    <Header />
                </AppShell.Header>
                <AppShell.Navbar p="md">
                    Navbar
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
                </AppShell.Navbar>
                <AppShell.Main>{children}</AppShell.Main>
            </AppShell>
        </>
    );
};
