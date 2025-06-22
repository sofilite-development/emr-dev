import axios, { type AxiosRequestConfig, type AxiosResponse } from "axios";

const _api = axios.create({
    baseURL: import.meta.env.VITE_BASE_URL + "/interface/main/dashboard/api",
    withCredentials: true,
});


_api.interceptors.request.use((config) => {
    const contentType =
        config.data instanceof FormData
            ? "multipart/form-data"
            : "application/json";

    config.headers["Content-Type"] = contentType;

    // config.headers["X-CSRF-Token"] = getCsrfToken();

    return config;
});

export const api = async <T>(
    url: string,
    config: AxiosRequestConfig
): Promise<T> =>
    _api
        .request<T>({
            url,
            ...config,
        })
        .then((res: AxiosResponse<T>) => res.data);
