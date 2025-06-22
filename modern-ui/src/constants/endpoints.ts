export const endpoints = {
    base: import.meta.env.VITE_API_BASE_URL,
    pages: {
        dashboard: "/dashboard",
        calender: "/calender",
        settings: "/settings",
        profile: "/profile",
        login: "/interface/login/login.php",
    },
    api: {
        me: "/me.php",
        dashboardStats: "/dashboard/stats.php",
        logout: "/logout.php"
    },
} ;
