import { DashboardLayout } from "@/layouts/Dashboard";
import { createFileRoute } from "@tanstack/react-router";
import { ListTable } from "@/features/patients/list";
import { endpoints } from "@/constants/endpoints";

export const Route = createFileRoute(endpoints.pages.patients)({
    component: Patients,
});

function Patients() {
    return (
        <>
            <DashboardLayout>
                <section className="p-3">
                    <ListTable />
                </section>
            </DashboardLayout>
        </>
    );
}
