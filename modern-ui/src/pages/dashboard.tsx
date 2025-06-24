import { useDashboard } from "@/features/dashboard/use-dashboard";
import { DashboardLayout } from "../layouts/Dashboard";
import { RecentlyAssigned } from "@/features/dashboard";
import { useAuth } from "@/hooks/use-auth";

export const Dashboard = () => {
    const { user } = useAuth();
    const {
        overviewQuery: { data, isLoading },
    } = useDashboard();

    return (
        <>
            <DashboardLayout>
                <section className="p-4">
                    <h1 className="text-3xl font-bold">
                        Welcome{" "}
                        <span className="text-blue-500 font-bold">
                            {user?.name}!
                        </span>
                    </h1>
                    <RecentlyAssigned
                        procedure_orders={data?.data?.procedure_orders || []}
                        isLoading={isLoading}
                    />
                </section>
            </DashboardLayout>
        </>
    );
};
