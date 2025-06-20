import {
    ActionIcon,
    Menu,
    MenuDropdown,
    MenuTarget,
    Text,
    Group,
} from "@mantine/core";
import { IconBell } from "@tabler/icons-react";
import { ThemeToggle } from "../../components/ThemeToggle";

export const Header = () => {
    return (
        <header>
            <div className="px-4 py-2 flex justify-between items-center gap-4">
                <Text variant="gradient" size="xl" fw={700}>
                    EMR
                </Text>

                <Group>
                    <NotificationMenu />
                    <ThemeToggle />
                </Group>
            </div>
        </header>
    );
};

const NotificationMenu = () => {
    return (
        <Menu>
            <MenuTarget>
                <ActionIcon size="lg" variant="default">
                    <IconBell size={22} />
                </ActionIcon>
            </MenuTarget>

            <MenuDropdown>
                <Menu.Label>Notifications</Menu.Label>
                <p className="text-center py-4 text-xs text-accent">
                    No new notifications
                </p>
            </MenuDropdown>
        </Menu>
    );
};
