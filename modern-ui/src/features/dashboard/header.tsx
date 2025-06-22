import {
    Bell,
    Sun,
    Moon,
    UserIcon,
    SettingsIcon,
    LogOutIcon,
    UserCircleIcon,
} from "lucide-react";
import { Button } from "@/components/ui/button";
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu";
import { useTheme } from "@/components/theme-provider";
import { SidebarTrigger } from "@/components/ui/sidebar";

import { useAuth } from "@/hooks/use-auth";
import { cn } from "@/lib/utils";

export const Header = () => {
    return (
        <header>
            <div className="flex py-2 items-center px-4">
                <SidebarTrigger />
                <div className="ml-auto flex items-center gap-1">
                    <NotificationMenu />
                    <UserDropdown />
                    <ThemeToggle />
                </div>
            </div>
        </header>
    );
};

export const UserDropdown = () => {
    const {
        logoutMutation: { mutate, isPending },
        meQuery: { data, isSuccess },
    } = useAuth();
    const handleLogout = () => {
        mutate();
    };

    return (
        <DropdownMenu>
            <DropdownMenuTrigger asChild>
                <Button
                    variant="ghost"
                    className="h-10 !pl-2 pr-4 w-auto rounded-4xl border shadow-sm"
                >
                    <UserCircleIcon className="size-6" />
                    <span className={cn(isSuccess ? "inline-block" : "hidden")}>
                        {data?.data?.user?.firstName +
                            " " +
                            data?.data?.user?.lastName}
                    </span>
                </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end" className="w-64">
                <DropdownMenuLabel className="capitalize">{data?.data?.user?.role}</DropdownMenuLabel>
                <DropdownMenuSeparator />

                {[
                    {
                        label: "Profile",
                        href: "/profile",
                        icon: <UserIcon className={"size-5"} />,
                    },
                    {
                        label: "Settings",
                        href: "/settings",
                        icon: <SettingsIcon className={"size-5"} />,
                    },
                ].map((item) => (
                    <DropdownMenuItem asChild key={item.label} className="mb-2">
                        <a href={item.href} className="flex items-center">
                            {item.icon}
                            {item.label}
                        </a>
                    </DropdownMenuItem>
                ))}
                <DropdownMenuItem disabled={isPending} onClick={handleLogout}>
                    <LogOutIcon className="size-5" />
                    <span className="">Logout</span>
                </DropdownMenuItem>
            </DropdownMenuContent>
        </DropdownMenu>
    );
};

const NotificationMenu = () => {
    return (
        <DropdownMenu>
            <DropdownMenuTrigger asChild>
                <Button variant="ghost" size="icon">
                    <Bell className="size-5" />
                    <span className="sr-only">Notifications</span>
                </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end" className="w-64">
                <DropdownMenuLabel>Notifications</DropdownMenuLabel>
                <DropdownMenuSeparator />
                <div className="p-4 text-center text-sm text-muted-foreground">
                    No new notifications
                </div>
            </DropdownMenuContent>
        </DropdownMenu>
    );
};

const ThemeToggle = () => {
    const { theme, setTheme } = useTheme();

    return (
        <Button
            variant="ghost"
            size="icon"
            onClick={() => setTheme(theme === "light" ? "dark" : "light")}
            className="p-0"
        >
            <Sun className="!h-5 !w-5 rotate-0 scale-100 transition-all dark:-rotate-90 dark:scale-0" />
            <Moon className="absolute !h-5 !w-5 rotate-90 scale-0 transition-all dark:rotate-0 dark:scale-100" />
            <span className="sr-only">Toggle theme</span>
        </Button>
    );
};
