import { useState } from "react";
import type { OverviewResponse } from "../type";
import { CalendarIcon, ChevronLeft, ChevronRight, Clock } from "lucide-react";
import { HoverCard, HoverCardTrigger } from "@/components/ui/hover-card";
import { HoverCardContent } from "@radix-ui/react-hover-card";
import { Card, CardContent, CardHeader } from "@/components/ui/card";

export function EventCalender({
    events,
}: {
    events: OverviewResponse["data"]["calendar_events"];
}) {
    const [currentDate, setCurrentDate] = useState(new Date());

    const monthNames = [
        "January",
        "February",
        "March",
        "April",
        "May",
        "June",
        "July",
        "August",
        "September",
        "October",
        "November",
        "December",
    ];

    const daysOfWeek = ["Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat"];

    const getDaysInMonth = (date: Date) => {
        return new Date(date.getFullYear(), date.getMonth() + 1, 0).getDate();
    };

    const getFirstDayOfMonth = (date: Date) => {
        return new Date(date.getFullYear(), date.getMonth(), 1).getDay();
    };

    const getEventsForDate = (day: number) => {
        const dateStr = `${currentDate.getFullYear()}-${String(
            currentDate.getMonth() + 1
        ).padStart(2, "0")}-${String(day).padStart(2, "0")}`;
        return events.filter((event) => event.date === dateStr);
    };

    const navigateMonth = (direction: number) => {
        setCurrentDate((prev) => {
            const newDate = new Date(prev);
            newDate.setMonth(prev.getMonth() + direction);
            return newDate;
        });
    };

    const renderCalendarDays = () => {
        const daysInMonth = getDaysInMonth(currentDate);
        const firstDay = getFirstDayOfMonth(currentDate);
        const days = [];

        // Empty cells for days before the first day of the month
        for (let i = 0; i < firstDay; i++) {
            days.push(<div key={`empty-${i}`} className="h-auto"></div>);
        }

        // Days of the month
        for (let day = 1; day <= daysInMonth; day++) {
            const dayEvents = getEventsForDate(day);
            const hasEvent = dayEvents.length > 0;
            const isToday =
                day === new Date().getDate() &&
                currentDate.getMonth() === new Date().getMonth(); // June 24th

            days.push(
                <div
                    key={day}
                    className={`h-9 w-9 text-sm flex items-center justify-center rounded-sm cursor-pointer relative transition-all duration-200 ${
                        isToday
                            ? "bg-primary text-white font-semibold"
                            : hasEvent
                            ? "dark:bg-secondary bg-slate-200 font-medium hover:bg-primary-foreground"
                            : "hover:bg-gray-100"
                    }`}
                >
                    <CalenderTooltip
                        events={getEventsForDate(day)}
                        month={monthNames[currentDate.getMonth()]}
                        year={currentDate.getFullYear()}
                        day={day}
                    >
                        {day}
                        {hasEvent && (
                            <div className="absolute bottom-1 right-1 w-2 h-2 bg-red-500 rounded-full"></div>
                        )}
                    </CalenderTooltip>
                </div>
            );
        }

        return days;
    };

    return (
        <>
            <div className="max-w-md bg-background rounded-lg shadow-lg p-6 border-border border">
                {/* Header */}
                <div className="flex items-center justify-between mb-6">
                    <h2 className="text-xl font-semibold text-primary">
                        {monthNames[currentDate.getMonth()]}{" "}
                        {currentDate.getFullYear()}
                    </h2>
                    <div className="flex gap-2">
                        <button
                            onClick={() => navigateMonth(-1)}
                            className="p-2 hover:bg-secondary rounded-lg transition-colors"
                        >
                            <ChevronLeft className="w-4 h-4" />
                        </button>
                        <button
                            onClick={() => navigateMonth(1)}
                            className="p-2 hover:bg-secondary rounded-lg transition-colors"
                        >
                            <ChevronRight className="w-4 h-4" />
                        </button>
                    </div>
                </div>

                {/* Days of week header */}
                <div className="grid grid-cols-7 gap-1 mb-2">
                    {daysOfWeek.map((day) => (
                        <div
                            key={day}
                            className="h-8 flex items-center justify-center text-sm font-medium text-gray-500"
                        >
                            {day}
                        </div>
                    ))}
                </div>

                {/* Calendar grid */}
                <div className="grid grid-cols-7 gap-1.5">
                    {renderCalendarDays()}
                </div>

                {/* Legend */}
                <div className="mt-6 flex items-center justify-center gap-4 text-sm text-accent-foreground">
                    <div className="flex items-center gap-2">
                        <div className="w-3 h-3 bg-primary rounded"></div>
                        <span>Today</span>
                    </div>
                    <div className="flex items-center gap-2">
                        <div className="w-3 h-3 bg-red-500 rounded-full"></div>
                        <span>Has Events</span>
                    </div>
                </div>
            </div>
        </>
    );
}

function CalenderTooltip({
    children,
    events,
    month,
    year,
    day,
}: {
    // eslint-disable-next-line @typescript-eslint/no-explicit-any
    children: any;
    events: OverviewResponse["data"]["calendar_events"];
    month: string;
    year: number;
    day: number;
}) {
    if (events.length === 0) return children;
    const formatTime = (time: string) => {
        const [hours, minutes] = time.split(":");
        const hour = parseInt(hours);
        const ampm = hour >= 12 ? "PM" : "AM";
        const displayHour = hour % 12 || 12;
        return `${displayHour}:${minutes} ${ampm}`;
    };
    return (
        <HoverCard>
            <HoverCardTrigger>{children}</HoverCardTrigger>
            <HoverCardContent className=" shadow-lg rounded-lg w-80 overflow-hidden z-50 ml-20">
                <Card >
                    <CardHeader className="flex items-center gap-2">
                        <CalendarIcon className="w-6 h-6" />
                        <span>
                            {month} {day}, {year}
                        </span>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        {events.map((event) => (
                            <div
                                key={event.id}
                                className="border-l-4 border-primary pl-3"
                            >
                                <p className="font-medium">
                                    {event.title}
                                </p>
                                <p className="flex items-center gap-1 text-sm text-gray-500 mt-1">
                                    <Clock className="w-3 h-3" />
                                    {formatTime(event.startTime)} -{" "}
                                    {formatTime(event.endTime)}
                                </p>
                            </div>
                        ))}
                    </CardContent>
                </Card>
            </HoverCardContent>
        </HoverCard>
    );
}
