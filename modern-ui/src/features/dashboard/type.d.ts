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

export interface OverviewResponse {
    success: boolean;
    data: {
        patient_trackers: PatientTrackerData[];
        procedure_orders: Order[];
        calendar_events: CalendarEvent[];
        messages: Message[];
        timestamp: string; // ISO date string
    };
}
