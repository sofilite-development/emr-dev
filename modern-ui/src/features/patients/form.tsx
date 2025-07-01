import React, { useEffect } from "react";
import { zodResolver } from "@hookform/resolvers/zod";
import { Form } from "@/components/ui/form";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import {
    FormFields,
    type BaseFieldProps,
    type FieldData,
} from "@/components/FormGenerator";
import { patientBaseSchema, type PatientFormData } from "./schema";
import { useForm } from "react-hook-form";

// Form field configuration
const formFields: FieldData<BaseFieldProps["type"]>[] = [
    {
        type: "text",
        field: "firstName",
        label: "First Name",
        placeholder: "Enter first name",
        autoComplete: "given-name",
    },
    {
        type: "text",
        field: "middleName",
        label: "Middle Name",
        placeholder: "Enter middle name (optional)",
        autoComplete: "additional-name",
    },
    {
        type: "text",
        field: "lastName",
        label: "Last Name",
        placeholder: "Enter last name",
        autoComplete: "family-name",
    },
    {
        type: "date",
        field: "dateOfBirth",
        label: "Date of Birth",
    },
    {
        type: "radio",
        field: "gender",
        label: "Gender",
        orientation: "horizontal",
        options: [
            { label: "Male", value: "Male" },
            { label: "Female", value: "Female" },
            { label: "Other", value: "Other" },
            { label: "Unknown", value: "Unknown" },
        ],
    },
    {
        type: "text",
        field: "ssn",
        label: "Social Security Number",
        placeholder: "XXX-XX-XXXX (optional)",
        pattern: "\\d{3}-\\d{2}-\\d{4}",
    },
    {
        type: "email",
        field: "email",
        label: "Email Address",
        placeholder: "Enter email address (optional)",
        autoComplete: "email",
    },
    {
        type: "tel",
        field: "phone",
        label: "Phone Number",
        placeholder: "Enter phone number (optional)",
        autoComplete: "tel",
    },
    {
        type: "tel",
        field: "phoneHome",
        label: "Home Phone",
        placeholder: "Enter home phone (optional)",
        autoComplete: "tel-national",
    },
    {
        type: "text",
        field: "address",
        label: "Address",
        placeholder: "Enter street address (optional)",
        autoComplete: "street-address",
    },
    {
        type: "text",
        field: "city",
        label: "City",
        placeholder: "Enter city (optional)",
        autoComplete: "address-level2",
    },
    {
        type: "text",
        field: "state",
        label: "State",
        placeholder: "Enter state code (optional)",
        autoComplete: "address-level1",
        pattern: "[A-Z]{2}",
    },
    {
        type: "text",
        field: "postalCode",
        label: "Postal Code",
        placeholder: "Enter postal code (optional)",
        autoComplete: "postal-code",
    },
    {
        type: "text",
        field: "country",
        label: "Country Code",
        placeholder: "Enter country code",
        autoComplete: "country",
        pattern: "[A-Z]{2}",
    },
    {
        type: "number",
        field: "providerId",
        label: "Provider ID",
        placeholder: "Enter provider ID (optional)",
        min: 1,
    },
];

// Patient type for updates
export interface Patient extends PatientFormData {
    id: string;
    createdAt?: Date;
    updatedAt?: Date;
}

interface PatientFormProps {
    onSubmit: (values: PatientFormData) => void;
    isUpdate?: boolean;
    patient?: Patient;
    isSuccess?: boolean;
    defaultValues?: Partial<PatientFormData>;
}

export const PatientForm: React.FC<PatientFormProps> = ({
    onSubmit,
    isUpdate = false,
    patient,
    isSuccess,
    defaultValues,
}) => {
    const form = useForm<PatientFormData>({
        // @ts-ignore
        resolver: zodResolver(patientBaseSchema),
        defaultValues: {
            firstName: "",
            middleName: "",
            lastName: "",
            dateOfBirth: new Date(),
            gender: "Unknown",
            ssn: "",
            email: "",
            phone: "",
            phoneHome: "",
            address: "",
            city: "",
            state: "",
            postalCode: "",
            country: "US",
            providerId: "",
            ...defaultValues,
        },
        reValidateMode: "onBlur",
    });

    // Set form values helper function
    const setValues = (patientData: Patient | Partial<PatientFormData>) => {
        const formattedData = {
            firstName: patientData.firstName || "",
            middleName: patientData.middleName || "",
            lastName: patientData.lastName || "",
            dateOfBirth: patientData.dateOfBirth || new Date(),
            gender: patientData.gender || "Unknown",
            ssn: patientData.ssn || "",
            email: patientData.email || "",
            phone: patientData.phone || "",
            phoneHome: patientData.phoneHome || "",
            address: patientData.address || "",
            city: patientData.city || "",
            state: patientData.state || "",
            postalCode: patientData.postalCode || "",
            country: patientData.country || "US",
            providerId: patientData.providerId || undefined,
        };

        form.reset(formattedData);
    };

    // Handle form submission
    const handleSubmit = (data: PatientFormData) => {
        onSubmit({
            ...data,
            dateOfBirth: new Date(data.dateOfBirth),
        });
    };

    // Effect to set initial values when patient data is provided
    useEffect(() => {
        if (!patient) return;
        setValues(patient);
    }, [patient]);

    // Effect to handle success state
    useEffect(() => {
        if (isUpdate && isSuccess && patient) {
            setValues(patient);
        } else if (!isUpdate && isSuccess) {
            form.reset();
        }
    }, [isUpdate, isSuccess, patient]);

    // Reset button handler
    const handleReset = () => {
        if (isUpdate && patient) {
            setValues(patient);
        } else if (defaultValues) {
            setValues(defaultValues);
        } else {
            form.reset();
        }
    };

    return (
        <Card className="w-full bg-background border-0 p-0 mx-auto">
            <CardHeader className="p-0">
                <CardTitle>
                    {isUpdate ? "Update Patient" : "Create Patient"}
                </CardTitle>
            </CardHeader>
            <CardContent className="p-0">
                <Form {...form}>
                    <form
                        // @ts-ignore
                        onSubmit={form.handleSubmit(handleSubmit)}
                        className="space-y-6"
                    >
                        <FormFields
                            data={formFields}
                            form={form}
                            grid={true}
                            gridCols={2}
                            className="mb-6"
                        />

                        <div className="flex gap-4 justify-center pt-4">
                            {isUpdate && (
                                <Button
                                    type="button"
                                    variant="outline"
                                    onClick={handleReset}
                                >
                                    Reset
                                </Button>
                            )}
                            <Button
                                type="submit"
                                disabled={form.formState.isSubmitting}
                            >
                                {form.formState.isSubmitting
                                    ? "Processing..."
                                    : isUpdate
                                      ? "Update Patient"
                                      : "Create Patient"}
                            </Button>
                        </div>
                    </form>
                </Form>
            </CardContent>
        </Card>
    );
};
