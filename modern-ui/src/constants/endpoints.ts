import type { FileRouteTypes } from "@/routeTree.gen";

export const endpoints = {
    base: import.meta.env.VITE_API_BASE_URL,
    pages: {
        dashboard: "/dashboard" as FileRouteTypes['to'],
        calender: "/calender" as FileRouteTypes['to'],
        settings: "/settings" as FileRouteTypes['to'],
        profile: "/profile" as FileRouteTypes['to'],
        login: "/interface/login/login.php",
        patients: "/patients" as FileRouteTypes['to'],
    } ,
    api: {
        me: "/me.php",
        dashboardOverview: "/overview.php",
        logout: "/logout.php",
        patients: "/patients.php",
        providers: "/providers.php"
    },
} as const;