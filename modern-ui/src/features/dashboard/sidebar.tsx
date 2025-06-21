import {
    Sidebar,
    SidebarContent,
    // SidebarFooter,
    SidebarGroup,
    SidebarGroupContent,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    SidebarRail,
    useSidebar,
} from "@/components/ui/sidebar";
import { Settings, UserIcon } from "lucide-react";
import { Logo } from "@/components/logo";
import { CircleArrowUp, LayoutDashboard } from "lucide-react";
import { endpoints } from "@/constants/endpoints";
import { cn } from "@/lib/utils";

// interface Props {
//     slug?: string;
// }
const items = [
    {
        title: "Dashboard",
        url: endpoints.pages.dashboard,
        icon: LayoutDashboard,
    },
    {
        title: "Help",
        url: endpoints.pages.calender,
        icon: CircleArrowUp,
    },

    {
        title: "profile",
        url: endpoints.pages.profile,
        icon: UserIcon,
    },
    {
        title: "Settings",
        url: endpoints.pages.settings,
        icon: Settings,
    },
];
export const DashboardSidebar = () => {
    return (
        <Sidebar
            collapsible="icon"
            sidebarInnerClasses="dark:bg-gray-950 bg-gray-50 "
        >
            <Header />
            <Content />
            {/* <Footer /> */}
            <SidebarRail />
        </Sidebar>
    );
};

function Header() {
    const { open } = useSidebar();
    return (
        <>
            <SidebarHeader>
                <SidebarMenuButton
                    size="lg"
                    className="data-[state=open]:bg-sidebar-accent data-[state=open]:text-sidebar-accent-foreground "
                >
                    <Logo showText={false} />
                    <span className={cn(open ? "" : "hidden")}>
                        <Logo showIcon={false} />
                    </span>
                </SidebarMenuButton>
            </SidebarHeader>
        </>
    );
}
function Content() {
    const url = "";
    const { open } = useSidebar();

    return (
        <SidebarContent>
            <SidebarGroupContent className={"px-2.5"}>
                <SidebarMenu className={cn(open ? "gap-0" : "gap-2")}>
                    {items.map((item) => (
                        <SidebarMenuItem key={item.title}>
                            <SidebarMenuButton
                                asChild
                                size={"lg"}
                                className={cn(
                                    "py-0 h-10",
                                    open ? "mb-2" : "mb-1",
                                    open && url === item.url
                                        ? "bg-sidebar-accent text-sidebar-accent-foreground"
                                        : ""
                                )}
                            >
                                <a href={item.url}>
                                    <span
                                        className={cn(
                                            url === item.url
                                                ? "logo-bg "
                                                : "text-primary",
                                            "rounded-md p-1"
                                        )}
                                    >
                                        <item.icon size={open ? 20 : 22} />
                                    </span>
                                    <span
                                        className={cn(
                                            "ml-1",
                                            url === item.url
                                                ? "logo-text font-bold"
                                                : ""
                                        )}
                                    >
                                        {item.title}
                                    </span>
                                </a>
                            </SidebarMenuButton>
                        </SidebarMenuItem>
                    ))}
                </SidebarMenu>
            </SidebarGroupContent>
            <SidebarGroup />
            <SidebarGroup />
        </SidebarContent>
    );
}
// function Footer() {
//     return (
//         <SidebarFooter>
//             <SidebarMenu>
//                 <SidebarMenuItem>
//                     <SidebarMenuButton
//                         asChild
//                         size="lg"
//                         className="data-[state=open]:bg-sidebar-accent bg-secondary data-[state=open]:text-sidebar-accent-foreground h-auto"
//                     >
//                         <a href="#">
//                             <span className="logo-bg rounded-md p-1 ">
//                                 <CircleArrowUp
//                                     className="ml-auto size-[20px]"
//                                     size={20}
//                                 />
//                             </span>
//                             <span className="flex flex-col">
//                                 <span className="logo-text font-bold">
//                                     Upgrade to Pro
//                                 </span>
//                                 <span className="text-primary text-xs">
//                                     Enable all features
//                                 </span>
//                             </span>
//                         </a>
//                     </SidebarMenuButton>
//                 </SidebarMenuItem>
//             </SidebarMenu>
//         </SidebarFooter>
//     );
// }
