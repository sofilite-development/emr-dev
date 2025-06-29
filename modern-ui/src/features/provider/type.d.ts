import type { MetaData } from "@/types/metadata";

export interface Provider {
    id: number;
    username: string;
    firstName: string;
    lastName: string;
    fullName: string;
    npi?: string;
    specialty?: string;
    status: "Active" | "Inactive";
}

export interface ProviderListResponse {
    success: boolean;
    data: {
        providers: Provider[];
    };
    metadata: MetaData;
    error?: string;
}

export interface ProviderListParams {
    page?: number;
    limit?: number;
    search?: string;
}
