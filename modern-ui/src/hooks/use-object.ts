import { useState } from "react";

export function useObject<T>(initialObject: T) {
    const [object, setObject] = useState<T>(initialObject);

    const setObjectValue = (key: keyof T, value: T[keyof T]) => {
        setObject((prevObject) => ({
            ...prevObject,
            [key]: value,
        }));
    };

    const setMultipleValues = (newValues: Partial<T>) => {
        setObject((prevObject) => ({
            ...prevObject,
            ...newValues,
        }));
    };
    return { object, setObjectValue, setMultipleValues };
}
