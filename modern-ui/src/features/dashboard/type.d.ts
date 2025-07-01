interface PatientInfo {
    pid: string | number;
    name: string;
}

export interface Order {
    order_id: number;
    date: string;
    priority: "" | "high" | "normal" | "unsigned";
    status: "" | "ordered" | "routed" | "in_progress" | "completed";
    patient: PatientInfo;
}

export interface Message {
    id: number;
    title: string;
    body: string;
    date: string;
    status: string;
    sender: {
        id: string;
        name: string;
    };
    recipient: {
        id: string;
        name: string;
    };
}

export interface PatientTrackerData {
    id: number;
    apptdate: string;
    element: string;
    encounter: string;
    patient: PatientInfo;
}

export interface CalendarEvent {
    id: number;
    title: string;
    date: string;
    startTime: string;
    endTime: string;
    dateTime: string;
}

interface AppointmentStatistics {
    today: {
        total: number;
        scheduled: number;
        checked_in: number;
        completed: number;
        no_show: number;
        cancelled: number;
    };
    week: {
        total: number;
        scheduled: number;
        checked_in: number;
        completed: number;
        no_show: number;
        cancelled: number;
    };
    month: {
        total: number;
        scheduled: number;
        checked_in: number;
        completed: number;
        no_show: number;
        cancelled: number;
    };
}
interface RecentAppointment {
    id: number;
    patient_name: string;
    pid: string;
    date: string;
    time: string;
    status: "Scheduled" | "Completed" | "No Show" | "Cancelled";
}

interface StatisticsResponse {
    success: boolean;
    data: {
        statistics: AppointmentStatistics;
        appointments_by_status: Record<string, number>;
        recent_appointments: RecentAppointment[];
    };
}

interface PatientStats {
    new_patients: number;
    repeat_patients: number;
    total_patients: number;
}

export interface OverviewResponse {
    success: boolean;
    data: {
        patient_trackers: PatientTrackerData[];
        procedure_orders: Order[];
        calendar_events: CalendarEvent[];
        messages: Message[];
        statistics: StatisticsResponse;
        timestamp: string;
        patient_stats: PatientStats;
    };
}
