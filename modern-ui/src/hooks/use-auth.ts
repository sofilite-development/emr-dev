import { endpoints } from "@/constants/endpoints";
import { queryKeys } from "@/constants/querykeys";
import { api } from "@/lib/api";
import type { AuthUser } from "@/types/user";
import { useMutation, useQuery } from "@tanstack/react-query";


export const useAuth = () => {

    const meQuery = useQuery({
        queryKey: [queryKeys.me],
        queryFn: () => {
            return api<{ data: { user: AuthUser }; success: boolean }>(
                endpoints.api.me,
                {
                    method: "GET",
                }
            );
        },
        
    });

    const logoutMutation = useMutation({
        mutationFn: () => {
            return api(endpoints.api.logout, {
                method: "GET",
            });
        },
        onError: () => {
            console.log("Logout failed");
        },
        onSettled: () => {
            window.location.href = endpoints.base + endpoints.pages.login;
        },
    });

    let role = "";

    switch (meQuery.data?.data?.user?.role) {
        case 1:
            role = "Administrator";
            break;
        case 2:
            role = "User";
            break;
        case 3:
            role = "Clinician";
            break;
        case 4:
            role = "Front Office";
            break;
        case 5:
            role = "Billing Manager";
            break;
        case 6:
            role = "Receptionist";
            break;
        case 7:
            role = "Nurse";
            break;
        case 8:
            role = "Therapist";
            break;
        default:
            role = "Unknown";
            break;
    }

    return {
        meQuery,
        logoutMutation,
        user: {
            name:
                meQuery.data?.data?.user?.firstName +
                " " +
                meQuery.data?.data?.user?.lastName,
            role,
            id: meQuery.data?.data?.user?.id ?? "",
            email: meQuery.data?.data?.user?.email ?? "",
        },
        userLoading: meQuery.isLoading,
    };
};
