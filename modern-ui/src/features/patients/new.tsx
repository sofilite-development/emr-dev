import { PatientForm } from "./form";
import type { PatientFormData } from "./schema";

export function CreatePatient() {
    const handlePatientSubmit = (data: PatientFormData) => {
        console.log("Patient data:", data);
        // Handle form submission here
    };

    return <PatientForm onSubmit={handlePatientSubmit} isUpdate={false} />;
}
