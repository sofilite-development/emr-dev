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
    return {
        meQuery,
        logoutMutation,
    };
};
