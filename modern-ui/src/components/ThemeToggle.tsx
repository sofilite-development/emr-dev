import { ActionIcon } from "@mantine/core";
import { IconMoon, IconSun } from "@tabler/icons-react";
import { useMantineColorScheme, useComputedColorScheme } from "@mantine/core";
import { useLayoutEffect } from "react";

export const ThemeToggle = () => {
    const { setColorScheme } = useMantineColorScheme();
    const computedColorScheme = useComputedColorScheme("light", {
        getInitialValueInEffect: true,
    });
    useLayoutEffect(() => {
        if (computedColorScheme === "light") {
            document.documentElement.classList.add("light");
            document.documentElement.classList.remove("dark");
        } else {
            document.documentElement.classList.add("dark");
            document.documentElement.classList.remove("light");
        }
    }, [computedColorScheme]);
    return (
        <ActionIcon
            onClick={() => {
                setColorScheme(
                    computedColorScheme === "light" ? "dark" : "light"
                );
            }}
            variant="default"
            size="lg"
            aria-label="Toggle color scheme"
        >
            {computedColorScheme === "light" ? (
                <IconSun stroke={1.5} />
            ) : (
                <IconMoon stroke={1.5} />
            )}
        </ActionIcon>
    );
};
