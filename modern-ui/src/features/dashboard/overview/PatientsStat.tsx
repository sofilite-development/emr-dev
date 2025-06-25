import {
    Card,
    CardContent,
    CardHeader,
    CardTitle,
} from "@/components/ui/card";
import type { PatientStats } from "../type";
import { UserCog2, UserPlus2 } from "lucide-react";

export const PatientsStat = ({ data }: { data?: PatientStats }) => {
    return (
        <div className="flex flex-col gap-4 flex-1 ">
            <Card className="blue-gradient w-full gap-2 text-white">
                <CardHeader className="mb-0">
                    <CardTitle className="text-xl font-bold flex justify-between items-center gap-2 ">
                        <span>New Patients</span>
                        <UserPlus2 className="size-6" />
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <p className="text-2xl font-bold">{data?.new_patients}</p>
                </CardContent>
            </Card>
            <Card className="blue-gradient w-full gap-2 text-white">
                <CardHeader className="mb-0">
                    <CardTitle className="text-xl font-bold justify-between flex items-center gap-2">
                        <span>Repeat Patients</span>
                        <UserCog2 className="size-6" />
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <p className="text-2xl font-bold">
                        {data?.repeat_patients}
                    </p>
                </CardContent>
            </Card>
        </div>
    );
};

export default PatientsStat;