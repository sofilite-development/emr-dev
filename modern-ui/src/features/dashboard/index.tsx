import { endpoints } from "@/constants/endpoints";
import { queryKeys } from "@/constants/querykeys";
import { api } from "@/lib/api";
import { useQuery } from "@tanstack/react-query";

export function DashboardElements() {
    const { data, error } = useQuery({
        queryKey: [queryKeys.me],
        queryFn: () => {
            api(endpoints.api.me, {
                method: "GET",
            });
        },
    });
    return (
        <section>
            <h1>Dashboard elements</h1>
            <pre>{JSON.stringify(data, null, 2)}</pre>
            <pre>ERROR : {JSON.stringify(error, null, 2)}</pre>
        </section>
    );
}
