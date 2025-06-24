import { useAuth } from "@/hooks/use-auth";
import { useDashboard } from "./use-dashboard";
import doctorImg from "@/assets/doctor-image.png";
import { Loader2 } from "lucide-react";
import { Avatar } from "@/components/ui/avatar";
import dayjs from "dayjs";
import { Badge } from "@/components/ui/badge";
import { ScrollArea } from "@/components/ui/scroll-area";
import { cn } from "@/lib/utils";

export function DashboardElements() {
    const { user } = useAuth();
    const {
        overviewQuery: { data, isLoading },
    } = useDashboard();

    return (
        <section className="p-4">
            <h1 className="text-3xl font-bold">
                Welcome{" "}
                <span className="text-blue-500 font-bold">{user?.name}!</span>
            </h1>
            <div className="blue-gradient mt-4 p-4 max-w-3xl rounded-lg relative overflow-hidden">
                <h3 className="text-white text-2xl font-semibold">
                    Recently Assigned Tasks
                    <Badge className="ml-2 mb-2 bg-white text-black text-xl">
                        {data?.data?.procedure_orders?.length}
                    </Badge>
                </h3>
                <div className="mt-4 ">
                    {isLoading ? (
                        <div className="flex items-center justify-center">
                            <Loader2 className="size-5 animate-spin" />
                        </div>
                    ) : (
                        <ScrollArea className="h-[210px] scroll-smooth w-sm">
                            <div className="flex flex-col gap-2 ">
                                {data?.data?.procedure_orders?.map(
                                    ({ order_id, patient, date, priority }) => (
                                        <div
                                            key={order_id}
                                            className="flex items-center gap-2 px-2.5 py-2.5 rounded-lg bg-background relative overflow-hidden transition-all duration-300 ease-in-out hover:scale-95"
                                            title={
                                                priority === "high"
                                                    ? "High Priority "
                                                    : ""
                                            }
                                        >
                                            <Avatar className="bg-blue-900 rounded-full size-10 grid place-content-center text-white font-bold size-2xl">
                                                {patient.name
                                                    .charAt(0)
                                                    .toUpperCase()}
                                            </Avatar>
                                            <div>
                                                <p className="font-bold">
                                                    {patient.name}
                                                </p>
                                                <p className="text-sm text-muted-foreground">
                                                    {dayjs(date).format(
                                                        "DD MMM YYYY | hh:mm A"
                                                    )}
                                                </p>
                                            </div>
                                            <Badge
                                                className={cn(
                                                    "absolute top-2 right-2 size-4 bg-white text-black text-xs",
                                                    priority === "high" &&
                                                        "bg-yellow-500 text-white",
                                                    !!priority ||
                                                        (priority == "" &&
                                                            "hidden")
                                                )}
                                            >
                                                {/* Priority High */}
                                            </Badge>
                                        </div>
                                    )
                                )}
                            </div>
                        </ScrollArea>
                    )}
                </div>
                <img
                    src={doctorImg}
                    alt="doctor"
                    className="w-full absolute -bottom-[500px] -right-30 max-w-[550px] object-contain -scale-x-100 "
                />
            </div>
        </section>
    );
}
