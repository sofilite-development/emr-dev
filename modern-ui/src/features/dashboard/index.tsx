import { useDashboard } from "@/features/dashboard/use-dashboard";
import { useUser } from "@/store/user-context";
import { RecentlyAssigned } from "./overview/RecentlyAssigned";
import { EventCalender } from "./overview/EventCalender";

export function DashboardElements() {
    const { user } = useUser();
    const {
        overviewQuery: { data, isLoading },
    } = useDashboard();

    return (
        <>
            <section className="p-4">
                <h1 className="text-3xl font-bold">
                    Welcome{" "}
                    <span className="text-blue-500 font-bold">
                        {user?.name}!
                    </span>
                </h1>
                <div className=" gap-4 flex-wrap">
                    <RecentlyAssigned
                        procedure_orders={data?.data?.procedure_orders || []}
                        isLoading={isLoading}
                    />
                </div>

                <div className="flex gap-4 flex-wrap mt-4">
                    <EventCalender events={data?.data?.calendar_events || []} />
                </div>
            </section>
        </>
    );
}
