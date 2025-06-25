import { useDashboard } from "@/features/dashboard/use-dashboard";
import { useUser } from "@/store/user-context";
import { RecentlyAssigned } from "./overview/RecentlyAssigned";
import { lazy, Suspense } from "react";

const EventCalendar = lazy(() => import("./overview/EventCalender"));
const Statistics = lazy(() => import("./overview/Statistics"));
const PatientsStat = lazy(() => import("./overview/PatientsStat"));
const ConversationList = lazy(() => import("./overview/RecentConversations"));

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
                <div className="flex gap-4 flex-wrap">
                    <RecentlyAssigned
                        procedure_orders={data?.data?.procedure_orders || []}
                        isLoading={isLoading}
                        className="flex-1"
                    />
                    <Suspense fallback={null}>
                        <ConversationList
                            conversations={data?.data?.messages ?? []}
                            className="flex-1 max-w-[400px]"
                        />
                    </Suspense>
                </div>
                <Suspense fallback={null}>
                    <div className="flex gap-4 flex-wrap mt-4">
                        <EventCalendar
                            events={data?.data?.calendar_events || []}
                        />
                        <Statistics
                            data={data?.data?.statistics.data.statistics}
                        />
                        <PatientsStat data={data?.data?.patient_stats} />
                    </div>
                </Suspense>
            </section>
        </>
    );
}
