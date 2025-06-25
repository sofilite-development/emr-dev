import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import type { AppointmentStatistics } from "../type";
import {
    CartesianGrid,
    Line,
    LineChart,
    ResponsiveContainer,
    XAxis,
    YAxis,
} from "recharts";

const StatisticsChart = ({ data }: { data?: AppointmentStatistics }) => {
    if (!data) return null;

    // Transform statistics data for different chart types
    const transformDataForChart = () => {
        const { today, week, month } = data;
        return [
            {
                name: "Today",
                scheduled: today.scheduled,
                completed: today.completed,
                cancelled: today.cancelled,
                no_show: today.no_show,
            },
            {
                name: "This Week",
                scheduled: week.scheduled,
                completed: week.completed,
                cancelled: week.cancelled,
                no_show: week.no_show,
            },
            {
                name: "This Month",
                scheduled: month.scheduled,
                completed: month.completed,
                cancelled: month.cancelled,
                no_show: month.no_show,
            },
        ];
    };

    const chartData = transformDataForChart();

    return (
        <>
            <div className="h-64 w-full pr-4">
                <ResponsiveContainer className={""} width="100%" height="100%">
                    <LineChart
                        data={chartData}
                        margin={{ top: 20, right: 40, left: 0, bottom: 20 }}
                    >
                        <CartesianGrid
                            strokeDasharray="3 3"
                            vertical={false}
                        />
                        <XAxis
                            dataKey="name"
                            axisLine={false}
                            tickLine={false}
                            tick={{ fontSize: 12, fill: "#666" }}
                        />
                        <YAxis
                            axisLine={false}
                            tickLine={false}
                            tick={{ fontSize: 12, fill: "#666" }}
                        />
                        <Line
                            type="monotone"
                            dataKey="scheduled"
                            stroke="#8b5cf6"
                            strokeWidth={2}
                            dot={{ fill: "#8b5cf6", r: 4 }}
                        />
                        <Line
                            type="monotone"
                            dataKey="completed"
                            stroke="#10b981"
                            strokeWidth={2}
                            dot={{ fill: "#10b981", r: 4 }}
                        />
                        <Line
                            type="monotone"
                            dataKey="cancelled"
                            stroke="#ef4444"
                            strokeWidth={2}
                            dot={{ fill: "#ef4444", r: 4 }}
                        />
                        <Line
                            type="monotone"
                            dataKey="no_show"
                            stroke="#f59e0b"
                            strokeWidth={2}
                            dot={{ fill: "#f59e0b", r: 4 }}
                        />
                    </LineChart>
                </ResponsiveContainer>
            </div>
            <div className="flex justify-center flex-wrap gap-4 mt-4 ">
                <div className="flex items-center gap-2">
                    <div className="w-3 h-3 rounded-full bg-violet-500"></div>
                    <span className="text-sm text-gray-600">Scheduled</span>
                </div>
                <div className="flex items-center gap-2">
                    <div className="w-3 h-3 rounded-full bg-emerald-500"></div>
                    <span className="text-sm text-gray-600">Completed</span>
                </div>
                <div className="flex items-center gap-2">
                    <div className="w-3 h-3 rounded-full bg-red-500"></div>
                    <span className="text-sm text-gray-600">Cancelled</span>
                </div>
                <div className="flex items-center gap-2">
                    <div className="w-3 h-3 rounded-full bg-amber-500"></div>
                    <span className="text-sm text-gray-600">No Show</span>
                </div>
            </div>
        </>
    );
};

export const Statistics = ({ data }: { data?: AppointmentStatistics }) => {
    if (!data) return null;

    return (
        <Card className="w-full max-w-xl bg-background">
            <CardHeader className="pb-2">
                <div className="flex justify-between items-center">
                    <CardTitle className="text-lg font-medium ">
                        <h1 className="text-2xl font-bold ">
                            Appointments Statistics
                        </h1>
                    </CardTitle>
                </div>
            </CardHeader>

            <CardContent className="px-0">
                <StatisticsChart data={data} />
            </CardContent>
        </Card>
    );
};

export default Statistics;