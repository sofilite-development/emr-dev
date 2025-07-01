import { createContext, useContext, useEffect, useState } from "react";
import { useAuth } from "@/hooks/use-auth";
// import { useQueryClient } from "@tanstack/react-query";
// import { endpoints } from "@/constants/endpoints";

interface User {
    name: string;
    role: string;
    id: number | string;
    email: string;
}

interface UserContextType {
    user: User | null;
}
// interface ErrRes {
//     error: string;
//     redirect: boolean;
//     success: boolean;
// }
const UserContext = createContext<UserContextType | null>(null);

export const UserProvider = ({ children }: { children: React.ReactNode }) => {

    const [user, setUser] = useState<User | null>(null);
    // const queryClient = useQueryClient();
    const { meQuery, user: _user } = useAuth();

    // Handle session expiration
    useEffect(() => {
        if (meQuery.isError) {
            // eslint-disable-next-line @typescript-eslint/no-explicit-any
            const error = (meQuery?.error as any) ?? {};
            if (
                error?.response?.status === 401 ||
                error?.response?.data?.redirect
            ) {
                // queryClient.clear();
                // window.location.href = endpoints.base + endpoints.pages.login;
                console.log(meQuery.error)
            }
        }else{
            setUser(_user);
        }
    }, [meQuery.isError, meQuery.data]);

    return (
        <UserContext.Provider value={{ user }}>
            {children}
        </UserContext.Provider>
    );
};

export const useUser = () => {
    const context = useContext(UserContext);
    if (!context) {
        throw new Error("useUser must be used within a UserProvider");
    }
    return context;
};
