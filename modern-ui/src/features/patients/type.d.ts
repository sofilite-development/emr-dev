import type { MetaData } from "@/types/metadata";

export interface Patient {
    id: number;
    pid: number;
    pubpid: string;
    firstName: string;
    lastName: string;
    fullName: string;
    dob: string;
    gender: string;
    phone: string;
    email: string;
    provider: string | null;
}

export interface PatientsResponse {
    success: boolean;
    data: {
        patients: Patient[];
    };
    metadata: MetaData;
    error?: string;
}

export interface PatientsFilters {
    page?: number;
    limit?: number;
    search?: string;
    providerId?: number;
}

export interface PatientFormData {
    firstName: string;
    lastName: string;
    dob: string;
    gender: string;
    phone: string;
    email: string;
    address?: string;
    city?: string;
    state?: string;
    postalCode?: string;
    providerId?: number;
    // Add more fields as needed
}
