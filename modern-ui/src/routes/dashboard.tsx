import { createFileRoute } from "@tanstack/react-router";
import { DashboardLayout } from "../layouts/Dashboard";
import { DashboardElements } from "@/features/dashboard";
import {endpoints} from "@/constants/endpoints"

export const Route = createFileRoute(endpoints.pages.dashboard)({
    component: Dashboard,
});

function Dashboard() {
    return (
        <DashboardLayout>
            <DashboardElements />
        </DashboardLayout>
    );
}
