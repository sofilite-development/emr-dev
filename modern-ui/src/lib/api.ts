import axios, { type AxiosRequestConfig} from "axios";

const isDevelopment = import.meta.env.DEV;
const baseURL = isDevelopment 
    ? '/api/interface/main/dashboard/api'  // Uses the Vite proxy in development
    : import.meta.env.VITE_BASE_URL + "/interface/main/dashboard/api";  // Uses the actual URL in production

const _api = axios.create({
    baseURL,
    withCredentials: true,
});

_api.interceptors.request.use((config) => {
    const contentType =
        config.data instanceof FormData
            ? "multipart/form-data"
            : "application/json";

    config.headers["Content-Type"] = contentType;
    
    // Add any additional headers needed for your API
    if (!isDevelopment) {
        // config.headers["X-CSRF-Token"] = getCsrfToken();
    }

    return config;
});

export const api = async <T>(
    url: string,
    config: AxiosRequestConfig = {}
): Promise<T> =>
    _api
        .request<T>({
            url,
            ...config,
        })
        .then((response) => response.data);
