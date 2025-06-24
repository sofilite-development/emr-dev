import { api } from "@/lib/api";
import { endpoints } from "@/constants/endpoints";
import { queryKeys } from "@/constants/querykeys";
import { useQuery } from "@tanstack/react-query";
import { type OverviewResponse } from "./type";

export const useDashboard = () => {
    const overviewQuery = useQuery({
        queryKey: [queryKeys.dashboardOverview],
        queryFn: () => {
            return api<OverviewResponse>(
                endpoints.api.dashboardOverview,
                {
                    method: "GET",
                }
            );
        },
    });
    return {
        overviewQuery,
    };
};