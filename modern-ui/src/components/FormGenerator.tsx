/* eslint-disable @typescript-eslint/no-explicit-any */
import { Input } from "@/components/ui/input";
import { Checkbox } from "@/components/ui/checkbox";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/components/ui/select";
import {
    FormControl,
    FormDescription,
    FormField,
    FormItem,
    FormLabel,
    FormMessage,
} from "@/components/ui/form";
import { RadioGroup, RadioGroupItem } from "@/components/ui/radio-group";
import { Switch } from "@/components/ui/switch";
import { cn } from "@/lib/utils";


// Base interface for all field types
export interface BaseFieldProps {
    type:
        | "text"
        | "email"
        | "password"
        | "number"
        | "tel"
        | "date"
        | "datetime-local"
        | "time"
        | "url"
        | "textarea"
        | "select"
        | "radio"
        | "checkbox"
        | "switch";
}

// Type-specific props
type FieldTypeProps<T extends BaseFieldProps["type"]> = T extends
    | "text"
    | "email"
    | "password"
    | "number"
    | "tel"
    | "date"
    | "datetime-local"
    | "time"
    | "url"
    ? {
          type: T;
          placeholder?: string;
          disabled?: boolean;
          readOnly?: boolean;
          autoComplete?: string;
          min?: string | number;
          max?: string | number;
          step?: string | number;
          pattern?: string;
      }
    : T extends "textarea"
      ? {
            type: T;
            placeholder?: string;
            disabled?: boolean;
            readOnly?: boolean;
            rows?: number;
            cols?: number;
            resize?: "none" | "both" | "horizontal" | "vertical";
        }
      : T extends "select"
        ? {
              type: T;
              placeholder?: string;
              disabled?: boolean;
              options: Array<{
                  label: string;
                  value: string;
                  disabled?: boolean;
              }>;
          }
        : T extends "radio"
          ? {
                type: T;
                disabled?: boolean;
                options: Array<{
                    label: string;
                    value: string;
                    disabled?: boolean;
                }>;
                orientation?: "horizontal" | "vertical";
            }
          : T extends "checkbox"
            ? {
                  type: T;
                  disabled?: boolean;
                  checkboxLabel?: string;
              }
            : T extends "switch"
              ? {
                    type: T;
                    disabled?: boolean;
                    switchLabel?: string;
                }
              : never;

// Common field configuration
export interface BaseFieldConfig {
    field: string;
    label?: string;
    description?: string;
    className?: string;
    required?: boolean;
}

// Combined field data type
export type FieldData<T extends BaseFieldProps["type"]> = BaseFieldConfig &
    FieldTypeProps<T>;

// Grid/Layout props
export interface GridColProps {
    span?: number;
    className?: string;
}

export interface FormFieldsProps {
    data: FieldData<BaseFieldProps["type"]>[];
    form: any;
    className?: string;
    grid?: boolean;
    gridCols?: number;
    defaultColSpan?: number;
    fieldClassName?: string;
}

// Utility function to convert camelCase to Title Case
const camelToTitle = (str: string): string => {
    return str
        .replace(/([A-Z])/g, " $1")
        .replace(/^./, (str) => str.toUpperCase())
        .trim();
};

// Individual form field component
const FormFieldComponent =({
    fieldData,
    form,
    className,
}: {
    fieldData: FieldData<BaseFieldProps["type"]>;
    form: any;
    className?: string;
}) => {
    const {
        type,
        field,
        label,
        description,
        className: fieldClassName,
        ...typeSpecificProps
    } = fieldData;

    const fieldLabel = label || camelToTitle(field);

    return (
        <FormField
            control={form.control}
            name={field as any}
            render={({ field: formField }: { field: any }) => (
                <FormItem className={cn(className, fieldClassName)}>
                    <FormLabel>{fieldLabel}</FormLabel>
                    <FormControl>
                        {(() => {
                            switch (type) {
                                case "text":
                                case "email":
                                case "password":
                                case "number":
                                case "tel":
                                case "date":
                                case "datetime-local":
                                case "time":
                                case "url":
                                    return (
                                        <Input
                                            type={type}
                                            {...formField}
                                            {...(typeSpecificProps as any)}
                                        />
                                    );

                                case "textarea":
                                    return (
                                        <Textarea
                                            {...formField}
                                            {...(typeSpecificProps as any)}
                                            className={cn(
                                                (typeSpecificProps as any)
                                                    .resize === "none" &&
                                                    "resize-none",
                                                (typeSpecificProps as any)
                                                    .resize === "horizontal" &&
                                                    "resize-x",
                                                (typeSpecificProps as any)
                                                    .resize === "vertical" &&
                                                    "resize-y",
                                            )}
                                        />
                                    );

                                case "select":
                                    { const selectProps =
                                        typeSpecificProps as FieldTypeProps<"select">;
                                    return (
                                        <Select
                                            onValueChange={formField.onChange}
                                            defaultValue={formField.value}
                                        >
                                            <SelectTrigger>
                                                <SelectValue
                                                    placeholder={
                                                        selectProps.placeholder ||
                                                        `Select ${fieldLabel}`
                                                    }
                                                />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {selectProps.options.map(
                                                    (option) => (
                                                        <SelectItem
                                                            key={option.value}
                                                            value={option.value}
                                                            disabled={
                                                                option.disabled
                                                            }
                                                        >
                                                            {option.label}
                                                        </SelectItem>
                                                    ),
                                                )}
                                            </SelectContent>
                                        </Select>
                                    ); }

                                case "radio":
                                    { const radioProps =
                                        typeSpecificProps as FieldTypeProps<"radio">;
                                    return (
                                        <RadioGroup
                                            onValueChange={formField.onChange}
                                            defaultValue={formField.value}
                                            className={cn(
                                                radioProps.orientation ===
                                                    "horizontal" &&
                                                    "flex flex-row space-x-4",
                                            )}
                                        >
                                            {radioProps.options.map(
                                                (option) => (
                                                    <div
                                                        key={option.value}
                                                        className="flex items-center space-x-2"
                                                    >
                                                        <RadioGroupItem
                                                            value={option.value}
                                                            id={`${field}-${option.value}`}
                                                        />
                                                        <Label
                                                            htmlFor={`${field}-${option.value}`}
                                                        >
                                                            {option.label}
                                                        </Label>
                                                    </div>
                                                ),
                                            )}
                                        </RadioGroup>
                                    ); }

                                case "checkbox":
                                    { const checkboxProps =
                                        typeSpecificProps as FieldTypeProps<"checkbox">;
                                    return (
                                        <div className="flex items-center space-x-2">
                                            <Checkbox
                                                id={field}
                                                checked={formField.value}
                                                onCheckedChange={
                                                    formField.onChange
                                                }
                                                disabled={
                                                    checkboxProps.disabled
                                                }
                                            />
                                            <Label htmlFor={field}>
                                                {checkboxProps.checkboxLabel ||
                                                    fieldLabel}
                                            </Label>
                                        </div>
                                    ); }

                                case "switch":
                                    { const switchProps =
                                        typeSpecificProps as FieldTypeProps<"switch">;
                                    return (
                                        <div className="flex items-center space-x-2">
                                            <Switch
                                                id={field}
                                                checked={formField.value}
                                                onCheckedChange={
                                                    formField.onChange
                                                }
                                                disabled={switchProps.disabled}
                                            />
                                            <Label htmlFor={field}>
                                                {switchProps.switchLabel ||
                                                    fieldLabel}
                                            </Label>
                                        </div>
                                    ); }

                                default:
                                    return <Input {...formField} />;
                            }
                        })()}
                    </FormControl>
                    {description && (
                        <FormDescription>{description}</FormDescription>
                    )}
                    <FormMessage />
                </FormItem>
            )}
        />
    );
};

// Main form fields component
export const FormFields = ({
    data,
    form,
    className,
    grid = true,
    gridCols = 2,
    defaultColSpan = 1,
    fieldClassName,
}: any) => {
    if (!grid) {
        return (
            <div className={cn("space-y-4", className)}>
                {data.map((fieldData: any, index: any) => (
                    <FormFieldComponent
                        key={fieldData.field || index}
                        fieldData={fieldData}
                        form={form}
                        className={fieldClassName}
                    />
                ))}
            </div>
        );
    }

    return (
        <div
            className={cn(
                "grid gap-4",
                gridCols === 1 && "grid-cols-1",
                gridCols === 2 && "grid-cols-1 md:grid-cols-2",
                gridCols === 3 && "grid-cols-1 md:grid-cols-2 lg:grid-cols-3",
                gridCols === 4 && "grid-cols-1 md:grid-cols-2 lg:grid-cols-4",
                className,
            )}
        >
            {data.map((fieldData: any, index: any) => (
                <div
                    key={fieldData.field || index}
                    className={cn(
                        defaultColSpan === 2 &&
                            gridCols >= 2 &&
                            "md:col-span-2",
                        defaultColSpan === 3 &&
                            gridCols >= 3 &&
                            "lg:col-span-3",
                        defaultColSpan === 4 &&
                            gridCols >= 4 &&
                            "lg:col-span-4",
                    )}
                >
                    <FormFieldComponent
                        fieldData={fieldData}
                        form={form}
                        className={fieldClassName}
                    />
                </div>
            ))}
        </div>
    );
};

export type { FieldTypeProps };
