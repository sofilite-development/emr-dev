import {
    flexRender,
    getCoreRowModel,
    getFilteredRowModel,
    getPaginationRowModel,
    getSortedRowModel,
    useReactTable,
    type ColumnDef,
    type VisibilityState,
} from "@tanstack/react-table";
import { ChevronDown, MoreHorizontal, PlusIcon } from "lucide-react";
import { Button } from "@/components/ui/button";
import {
    DropdownMenu,
    DropdownMenuCheckboxItem,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu";
import { Input } from "@/components/ui/input";
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from "@/components/ui/table";

import { usePatients } from "./use-patients";
import { useMemo, useState } from "react";
import type { Patient } from "./type";
import { CreatePatient } from "./new";
import { Dialog, DialogContent, DialogTrigger } from "@/components/ui/dialog";
import { ScrollArea } from "@/components/ui/scroll-area";

const columns: ColumnDef<Patient>[] = [
    {
        accessorKey: "pid",
        header: "Patient ID",
        cell: ({ row }) => (
            <div className="font-medium">{row.original.pid}</div>
        ),
    },
    {
        accessorKey: "fullName",
        header: "Name",
        cell: ({ row }) => (
            <div className="font-medium">
                {row.original.fullName}
            </div>
        ),
    },
    {
        accessorKey: "dob",
        header: "Date of Birth",
        cell: ({ row }) => {
            const date = new Date(row.original.dob);
            const formatted = date.toLocaleDateString("en-US", {
                year: "numeric",
                month: "short",
                day: "numeric",
            });
            return <div className="text-sm">{formatted}</div>;
        },
    },
    {
        accessorKey: "gender",
        header: "Gender",
        cell: ({ row }) => (
            <div className="capitalize">{row.original.gender}</div>
        ),
    },
    {
        accessorKey: "phone",
        header: "Phone",
        cell: ({ row }) => (
            <div className="text-sm">{row.original.phone || "-"}</div>
        ),
    },
    {
        accessorKey: "email",
        header: "Email",
        cell: ({ row }) => (
            <div className="text-sm">{row.original.email || "-"}</div>
        ),
    },
    {
        accessorKey: "provider",
        header: "Provider",
        cell: ({ row }) => (
            <div className="text-sm">{row.original.provider || "-"}</div>
        ),
    },
    {
        id: "actions",
        cell: ({ row }) => {
            const patient = row.original;
            return <ListItemMenu pid={patient.pid} />;
        },
    },
];

const ListItemMenu = ({ pid }: { pid: number }) => {
    return (
        <>
            <DropdownMenu>
                <DropdownMenuTrigger asChild>
                    <Button variant="ghost" className="h-8 w-8 p-0">
                        <span className="sr-only">Open menu</span>
                        <MoreHorizontal className="h-4 w-4" />
                    </Button>
                </DropdownMenuTrigger>
                <DropdownMenuContent align="end">
                    <DropdownMenuLabel>Actions</DropdownMenuLabel>
                    <DropdownMenuItem>Add New Patient</DropdownMenuItem>
                    <DropdownMenuItem
                        onClick={() =>
                            navigator.clipboard.writeText(pid.toString())
                        }
                    >
                        Copy patient ID
                    </DropdownMenuItem>
                    <DropdownMenuSeparator />
                    <DropdownMenuItem>View patient</DropdownMenuItem>
                    <DropdownMenuItem>Edit details</DropdownMenuItem>
                </DropdownMenuContent>
            </DropdownMenu>
        </>
    );
};

function CreateFormModal({
    children,
    trigger,
}: {
    children: React.ReactNode;
    trigger: React.ReactNode;
}) {
    return (
        <Dialog>
            <DialogTrigger asChild>{trigger}</DialogTrigger>
            <DialogContent className="!max-w-[992px]">{children}</DialogContent>
        </Dialog>
    );
}

export const ListTable = () => {
    const {
        listQuery: { data, isLoading },
    } = usePatients({ fetchListEnabled: true });
    const refinedData = useMemo(() => {
        if (!data || !data?.data || !data.data?.patients) {
            return [];
        }
        return data.data.patients?.map((patient) => ({
            id: patient.id,
            pid: patient.pid,
            pubpid: patient.pubpid,
            fullName: patient.fullName,
            dob: patient.dob,
            gender: patient.gender,
            phone: patient.phone,
            email: patient.email,
            provider: patient.provider,
        }));
    }, [data]);
    const [columnVisibility, setColumnVisibility] = useState<VisibilityState>(
        {},
    );
    const [rowSelection, setRowSelection] = useState({});

    const table = useReactTable({
        data: refinedData,
        // eslint-disable-next-line @typescript-eslint/no-explicit-any
        columns: columns as any,
        getCoreRowModel: getCoreRowModel(),
        getPaginationRowModel: getPaginationRowModel(),
        getSortedRowModel: getSortedRowModel(),
        getFilteredRowModel: getFilteredRowModel(),
        onColumnVisibilityChange: setColumnVisibility,
        onRowSelectionChange: setRowSelection,
        state: {
            columnVisibility,
            rowSelection,
        },
    });

    // Show loading state
    if (isLoading) {
        return <div>Loading patients...</div>;
    }

    // Show error state if needed
    if (!data) {
        return <div>Failed to load patients.</div>;
    }

    return (
        <div className="w-full">
            <div className="flex justify-between items-center pb-4">
                <Input
                    placeholder="Filter patients..."
                    value={
                        (table
                            .getColumn("fullName")
                            ?.getFilterValue() as string) ?? ""
                    }
                    onChange={(event) =>
                        table
                            .getColumn("fullName")
                            ?.setFilterValue(event.target.value)
                    }
                    className="max-w-sm"
                />

                <div className="flex gap-4 items-center">
                    <CreateFormModal
                        trigger={
                            <Button
                                variant="outline"
                                className="rounded-full size-9"
                                size={"icon"}
                            >
                                <PlusIcon className="w-6 h-6" />
                            </Button>
                        }
                    >
                        <ScrollArea className="h-[calc(100vh-5rem)]">
                            <CreatePatient />
                        </ScrollArea>
                    </CreateFormModal>
                    <DropdownMenu>
                        <DropdownMenuTrigger asChild>
                            <Button variant="outline" className="ml-auto">
                                Columns <ChevronDown className="ml-2 h-4 w-4" />
                            </Button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="end">
                            {table
                                .getAllColumns()
                                .filter((column) => column.getCanHide())
                                .map((column) => {
                                    return (
                                        <DropdownMenuCheckboxItem
                                            key={column.id}
                                            className="capitalize"
                                            checked={column.getIsVisible()}
                                            onCheckedChange={(value) =>
                                                column.toggleVisibility(!!value)
                                            }
                                        >
                                            {column.id}
                                        </DropdownMenuCheckboxItem>
                                    );
                                })}
                        </DropdownMenuContent>
                    </DropdownMenu>
                </div>
            </div>
            <div className="rounded-md border">
                <Table>
                    <TableHeader>
                        {table.getHeaderGroups().map((headerGroup) => (
                            <TableRow key={headerGroup.id}>
                                {headerGroup.headers.map((header) => {
                                    return (
                                        <TableHead key={header.id}>
                                            {header.isPlaceholder
                                                ? null
                                                : flexRender(
                                                      header.column.columnDef
                                                          .header,
                                                      header.getContext(),
                                                  )}
                                        </TableHead>
                                    );
                                })}
                            </TableRow>
                        ))}
                    </TableHeader>
                    <TableBody>
                        {table.getRowModel().rows?.length ? (
                            table.getRowModel().rows.map((row) => (
                                <TableRow
                                    key={row.id}
                                    data-state={
                                        row.getIsSelected() && "selected"
                                    }
                                >
                                    {row.getVisibleCells().map((cell) => (
                                        <TableCell key={cell.id}>
                                            {flexRender(
                                                cell.column.columnDef.cell,
                                                cell.getContext(),
                                            )}
                                        </TableCell>
                                    ))}
                                </TableRow>
                            ))
                        ) : (
                            <TableRow>
                                <TableCell
                                    colSpan={columns.length}
                                    className="h-24 text-center"
                                >
                                    {isLoading ? "Loading..." : "No results."}
                                </TableCell>
                            </TableRow>
                        )}
                    </TableBody>
                </Table>
            </div>
            <div className="flex items-center justify-end space-x-2 py-4">
                <div className="flex-1 text-sm text-muted-foreground">
                    {table.getFilteredSelectedRowModel().rows.length} of{" "}
                    {table.getFilteredRowModel().rows.length} row(s) selected.
                </div>
                <div className="space-x-2">
                    <Button
                        variant="outline"
                        size="sm"
                        onClick={() => table.previousPage()}
                        disabled={!table.getCanPreviousPage()}
                    >
                        Previous
                    </Button>
                    <Button
                        variant="outline"
                        size="sm"
                        onClick={() => table.nextPage()}
                        disabled={!table.getCanNextPage()}
                    >
                        Next
                    </Button>
                </div>
            </div>
        </div>
    );
};
