import { z } from "zod";
const date = z
    .string()
    .or(z.date())
    .pipe(
        z.coerce.date({
            required_error: "Date of birth is required",
            invalid_type_error: "Invalid date format",
        }),
    );
// Base patient schema with common fields
export const patientBaseSchema = z.object({
    firstName: z
        .string()
        .min(1, "First name is required")
        .max(50, "First name is too long"),
    middleName: z.string().max(50, "Middle name is too long").optional(),
    lastName: z
        .string()
        .min(1, "Last name is required")
        .max(50, "Last name is too long"),
    dateOfBirth: date,
    gender: z.enum(["Male", "Female", "Other", "Unknown"], {
        required_error: "Gender is required",
    }),
    ssn: z.string().optional(),
    email: z
        .string()
        .email("Invalid email address")
        .max(100, "Email is too long")
        .optional()
        .or(z.literal("")),
    phone: z.string().max(20, "Phone number is too long").optional(),
    phoneHome: z.string().max(20, "Home phone number is too long").optional(),
    address: z.string().max(100, "Address is too long").optional(),
    city: z.string().max(50, "City name is too long").optional(),
    state: z.string().max(2, "State code is too long").optional(),
    postalCode: z.string().max(15, "Postal code is too long").optional(),
    country: z.string().max(2, "Country code is too long").default("US"),
    providerId: z
        .number()
        .int()
        .positive("Provider ID must be a positive number")
        .optional(),
});

// Schema for creating a new patient
export const createPatientSchema = patientBaseSchema.refine(
    () => {
        return true;
    },
    {
        message: "Required fields are missing",
    },
);

// Schema for updating a patient
export const updatePatientSchema = patientBaseSchema.partial();

// Type exports
export type PatientFormData = z.infer<typeof patientBaseSchema>;
export type CreatePatientInput = z.infer<typeof createPatientSchema>;
export type UpdatePatientInput = z.infer<typeof updatePatientSchema>;

// Default values for the form
export const defaultPatientValues: Partial<PatientFormData> = {
    gender: "Unknown",
    country: "US",
};
