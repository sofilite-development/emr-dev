import { config } from "@/config";
import { endpoints } from "@/constants/endpoints";
import { cn } from "@/lib/utils";
import logo from "../assets/icon.png";

interface Props {
    showIcon?: boolean;
    showText?: boolean;
    classNames?: {
        root?: string;
        icon?: string;
        iconContainer?: string;
        text?: string;
    };
}
export const Logo = ({
    showIcon = true,
    showText = true,
    classNames = {
        root: "",
        icon: "",
        iconContainer: "",
        text: "",
    },
}: Props) => {
    return (
        <a href={endpoints.pages.dashboard}>
            <div className={cn("flex items-center space-x-2", classNames.root)}>
                {showIcon && (
                    <div className={cn("p-[2px] rounded-lg ", classNames.icon)}>
                        <img
                            src={logo}
                            alt="Quantum leap"
                            loading="lazy"
                            className="max-w-9 max-h-9 w-full object-contain"
                        />
                    </div>
                )}
                {showText && (
                    <h2
                        className={cn(
                            "font-bold text-2xl tracking-tight",
                            classNames.text
                        )}
                    >
                        {config.appName}
                    </h2>
                )}
            </div>
        </a>
    );
};
