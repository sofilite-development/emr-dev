import { DashboardLayout } from "../layouts/Dashboard";
import { DashboardElements } from "@/features/dashboard";

export const Dashboard = () => {
    return (
        <>
            <DashboardLayout>
                <DashboardElements />
            </DashboardLayout>
        </>
    );
};
