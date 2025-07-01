import { useRouter } from "@tanstack/react-router";

export const useRouteParams = () =>{
    const router = useRouter();

    return {
        pathname: router.state.location.pathname,
        search: router.state.location.search,
        searchStr: router.state.location.searchStr,
        hash: router.state.location.hash,
        matches: router.state.matches,
        isLoading: router.state.isLoading,
        isTransitioning: router.state.isTransitioning,
    }
}