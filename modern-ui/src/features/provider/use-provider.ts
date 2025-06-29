import { queryKeys } from "@/constants/querykeys";
import { api } from "@/lib/api";
import { useQuery, useQueryClient } from "@tanstack/react-query";
import type { ProviderListResponse, ProviderListParams as Filters } from "./type";
import { endpoints } from "@/constants/endpoints";
import { useObject } from "@/hooks/use-object";

interface Props {
    fetchListEnabled?: boolean;
    defaultFilters?: Filters;
}

export const usePatients = (props?: Props) => {
    const queryClient = useQueryClient();

    const { fetchListEnabled = false, defaultFilters } = props || {};

    const { object: filters, setMultipleValues: setFilters } =
        useObject<Filters>({
            page: defaultFilters?.page || 1,
            limit: defaultFilters?.limit || 10,
            search: defaultFilters?.search || "",
            ...defaultFilters,
        });

    const listQuery = useQuery({
        queryKey: [queryKeys.providers, ...Object.values(filters)],
        queryFn: () => {
            return api<ProviderListResponse>(endpoints.api.providers, {
                method: "GET",
            });
        },
        enabled: fetchListEnabled,
    });

    const revalidateList = () => {
        queryClient.invalidateQueries({
            queryKey: [queryKeys.providers, ...Object.values(filters)],
        });
    };

    return {
        listQuery,
        filters,
        setFilters,
        revalidateList,
    };
};
