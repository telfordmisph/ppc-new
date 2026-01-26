import React from "react";
import {
    useReactTable,
    getCoreRowModel,
    flexRender,
} from "@tanstack/react-table";
import { useMutation } from "@/Hooks/useMutation";
import { useEffect, useState, useRef, useCallback, useMemo, memo } from "react";
import { usePage, router, Link } from "@inertiajs/react";
import { FaCaretDown } from "react-icons/fa";
import MultiSelectSearchableDropdown from "@/Components/MultiSelectSearchableDropdown";
import { useFetch } from "@/Hooks/useFetch";
import "react-datepicker/dist/react-datepicker.css";
import DatePicker from "react-datepicker";
import { format } from "date-fns";
import toast from "react-hot-toast";
import Pagination from "@/Components/Pagination";
import { MdSchedule } from "react-icons/md";

const statusOptions = [
    "Shipped",
    "IQA",
    "For Process",
    "In-process",
    "Hold",
    "FVI",
    "Boxing",
    "OQA",
    "QA Buy-off",
];

const EditableCell = React.memo(function EditableCell({
    value,
    rowIndex,
    columnId,
    onChange,
    options,
}) {
    const [localValue, setLocalValue] = useState(value);

    useEffect(() => {
        setLocalValue(value);
    }, [value]);

    return (
        <input
            className="w-full border-none py-2 focus:ring-0 bg-transparent"
            value={localValue ?? ""}
            onChange={(e) => setLocalValue(e.target.value)}
            {...options}
            onBlur={() => {
                if (localValue !== value) {
                    onChange(rowIndex, columnId, localValue);
                }
            }}
        />
    );
});

const StatusCell = React.memo(function StatusCell({
    value,
    rowIndex,
    columnId,
    onChange,
}) {
    return (
        <div className="dropdown w-full">
            <div
                tabIndex={0}
                role="button"
                className="btn w-full bg-base-100 flex justify-between border-0"
            >
                {value}
                <FaCaretDown />
            </div>

            <ul className="dropdown-content menu bg-base-100 rounded-box z-10 w-40 p-1 shadow-sm">
                {statusOptions.map((option) => (
                    <li key={option}>
                        <button
                            type="button"
                            className="px-4 py-1 w-full text-left"
                            onClick={() => onChange(rowIndex, columnId, option)}
                        >
                            {option}
                        </button>
                    </li>
                ))}
            </ul>
        </div>
    );
});

const formatDateTime = (date) => {
    const pad = (n) => String(n).padStart(2, "0");

    return (
        `${date.getFullYear()}-` +
        `${pad(date.getMonth() + 1)}-` +
        `${pad(date.getDate())} ` +
        `${pad(date.getHours())}:` +
        `${pad(date.getMinutes())}:` +
        `${pad(date.getSeconds())}`
    );
};

const DateCell = React.memo(function DateCell({
    value,
    rowIndex,
    columnId,
    onChange,
    options = {
        showTimeSelect: true,
        timeFormat: "HH:mm",
        timeIntervals: 15,
        dateFormat: "yyyy-MM-dd HH:mm",
    },
}) {
    const date = React.useMemo(() => (value ? new Date(value) : null), [value]);

    const handleChange = React.useCallback(
        (d) => {
            let formatted = d ? formatDateTime(d) : null;
            console.log("🚀 ~ DateCell ~ formatted:", formatted);

            if (options?.showTimeSelect === false) {
                formatted = format(formatted, "yyyy-MM-dd");
            }

            onChange(rowIndex, columnId, formatted);
        },
        [rowIndex, columnId, onChange]
    );

    // const handleChange = React.useCallback(
    //     (d) => {
    //         if (!d) {
    //             onChange(rowIndex, columnId, null);
    //             return;
    //         }

    //         if (options?.showTimeSelect === false) {
    //             // force date-only
    //             const yyyyMmDd = format(d, "yyyy-MM-dd");
    //             onChange(rowIndex, columnId, yyyyMmDd);
    //             return;
    //         }

    //         onChange(rowIndex, columnId, formatDateTime(d));
    //     },
    //     [rowIndex, columnId, onChange, options]
    // );

    return (
        <DatePicker
            className="w-full rounded-lg input"
            selected={date}
            onChange={handleChange}
            {...options}
        />
    );
});

const fullF3Roles = [
    // "PPC Planner",
    // "PPC Planner 2",
    // "PPC Expediter 1",
    // "PPC Expediter 2",
    // "PPC Senior Supervisor",
    // "PPC Manager",
    // "ppc supervisor",
    // "programmer 1",
    "Production Supervisor",
    "Senior Production Supervisor",
    "Production Section Head",
    "Section Head",
    "programmer 1",
    "PPC Manager",
    "Trainee PPC Planner",
    "PPC",
    "PPC Planner",
    "PPC Planner 2",
    "PPC Expediter 1",
    "PPC Expediter 2",
    "Planner 2",
    "Planner",
    "PPC Senior Supervisor",
    "ppc supervisor",
    "Residual Controller 1",
];

export default function F3List() {
    const {
        f3WipAndOut: serverF3WipAndOut,
        search: serverSearch,
        perPage: serverPerPage,
        dateLoaded: serverDateLoaded,
        totalEntries,
        emp_data,
    } = usePage().props;

    console.log("🚀 ~ F3List ~ serverF3WipAndOut:", serverF3WipAndOut);
    console.log("🚀 ~ F3List ~ emp_data:", emp_data);

    const hasFullF3Access = fullF3Roles.some(
        (role) => role.toLowerCase() === emp_data.emp_jobtitle.toLowerCase()
    );

    const modalDropdownRef = useRef(null);

    const start = serverF3WipAndOut.from;
    const end = serverF3WipAndOut.to;
    const filteredTotal = serverF3WipAndOut.total;
    const overallTotal = totalEntries ?? filteredTotal;
    const [data, setData] = React.useState(serverF3WipAndOut.data || []);
    const [f3SearchInput, setF3SearchInput] = useState(serverSearch || "");
    const [f3DateInput, setF3DateInput] = useState(serverDateLoaded || null);
    const [maxItem, setMaxItem] = useState(serverPerPage || 25);
    const [editedRows, setEditedRows] = React.useState({});
    const [currentPage, setCurrentPage] = useState(
        serverF3WipAndOut.current_page || 1
    );
    const [searchedF3RawPackage, setSearchedF3RawPackage] = useState(null);
    const [currentPageF3RawPackage, setCurrentPageF3RawPackage] = useState(1);
    const perPageF3RawPackage = 50;

    const [rowSelection, setRowSelection] = useState({});
    const [selectedRowIndex, setSelectedRowIndex] = useState(null);
    const [originalF3RawPackage, setSelectedOriginalF3RawPackage] = useState([
        [],
    ]);
    const [originalData, setOriginalData] = useState({});
    const [showChangeModal, setShowChangeModal] = useState(false);
    const [changesToReview, setChangesToReview] = useState([]);
    const [f3RawPackageSearchInput, setF3RawPackageSearchInput] = useState("");

    const getChanges = () => {
        const changes = [];

        for (const rowId in editedRows) {
            const original = originalData[rowId];
            const edited = editedRows[rowId];

            for (const field in edited) {
                const before = original[field];
                const after = edited[field];

                if (before !== after) {
                    changes.push({
                        rowId,
                        field,
                        before,
                        after,
                    });
                }
            }
        }

        return changes;
    };

    const {
        mutate: mutateF3,
        isLoading: isMutateF3Loading,
        errorMessage: mutateF3ErrorMessage,
        errorData: mutateF3ErrorData,
        cancel: mutateF3Cancel,
    } = useMutation(route("api.f3.bulkUpdate"));

    useEffect(() => {
        if (!mutateF3ErrorMessage) return;

        toast.error(mutateF3ErrorMessage);
    }, [mutateF3ErrorMessage, mutateF3ErrorData]);

    const saveChangeIDModal = "save_change_modal_id";

    const handleSaveClick = () => {
        const changes = getChanges();
        if (changes.length === 0) {
            alert("No changes to save.");
            return;
        }
        document.getElementById(saveChangeIDModal).showModal();
        setChangesToReview(changes);
        setShowChangeModal(true);
    };

    useEffect(() => {
        const rows = serverF3WipAndOut.data || [];
        setData(rows);

        const map = {};
        rows.forEach((row) => {
            map[row.id] = row;
        });
        setOriginalData(map);

        setEditedRows({});
    }, [serverF3WipAndOut.data]);

    // console.log(" rowSelectionrowSelection rowSelection:", rowSelection);
    const openF3RawPackageSelectionModal = (rowIndex, originalF3RawPackage) => {
        setSelectedRowIndex(rowIndex);
        setSelectedOriginalF3RawPackage([originalF3RawPackage]);
        // modalDropdownRef.current?.showModal();
        document
            .getElementById("multiSelectSearchableDropdown-modal")
            .showModal();
    };

    const {
        data: f3RawPackages,
        isLoading: isLoadingF3RawPackages,
        errorMessage: errorMessageF3RawPackages,
        errorData: errorDataF3RawPackages,
        cancel: cancelF3RawPackages,
        fetch: fetchF3RawPackages,
    } = useFetch(route("api.f3.raw.package.index"), {
        auto: false,
    });
    console.log("🚀 ~ F3List ~ f3RawPackages:", f3RawPackages);

    const readOnlyColumns = React.useCallback(
        ({ accessorKey, header, options = {} }) => ({
            accessorKey,
            header,
            ...options,
            cell: ({ getValue }) => (
                <span className="opacity-60 cursor-not-allowed">
                    {getValue() ?? "-"}
                </span>
            ),
        }),
        []
    );

    const editableColumn = React.useCallback(
        ({ accessorKey, header, inputOptions = {}, options = {} }) => ({
            accessorKey,
            header,
            ...options,
            cell: React.memo(({ getValue, row, column }) => (
                <EditableCell
                    value={getValue()}
                    rowIndex={row.index}
                    columnId={column.id}
                    onChange={handleCellChange}
                    options={inputOptions}
                />
            )),
        }),
        []
    );

    const handleCellChange = useCallback((rowIndex, columnId, value) => {
        setData((prevData) => {
            if (prevData[rowIndex][columnId] === value) {
                return prevData;
            }
            const newData = [...prevData];
            newData[rowIndex] = { ...newData[rowIndex], [columnId]: value };

            const rowId = newData[rowIndex].id;

            // setEditedRows((prevEdited) => ({
            //     ...prevEdited,
            //     [newData[rowIndex].id]: { ...newData[rowIndex] },
            // }));

            setEditedRows((prev) => ({
                ...prev,
                [rowId]: {
                    ...prev[rowId],
                    [columnId]: value,
                },
            }));

            return newData;
        });
    }, []);

    const dateTimeColumn = React.useCallback(
        (accessorKey, header, options = {}) => ({
            accessorKey: accessorKey,
            header: header,
            ...options,
            cell: ({ getValue, row, column }) => (
                <DateCell
                    value={getValue()}
                    rowIndex={row.index}
                    columnId={column.id}
                    onChange={handleCellChange}
                />
            ),
        }),
        [handleCellChange]
    );

    const dateColumn = React.useCallback(
        (accessorKey, header, options = {}) => ({
            accessorKey: accessorKey,
            header: header,
            ...options,
            cell: ({ getValue, row, column }) => (
                <DateCell
                    value={getValue()}
                    rowIndex={row.index}
                    columnId={column.id}
                    onChange={handleCellChange}
                    options={{
                        dateFormat: "yyyy-MM-dd",
                        showTimeSelect: false,
                        timeFormat: null,
                        timeIntervals: null,
                    }}
                />
            ),
        }),
        [handleCellChange]
    );

    const statusColumn = React.useMemo(
        () => ({
            accessorKey: "status",
            header: "Status",
            size: 200,
            cell: React.memo(({ getValue, row, column }) => {
                return (
                    <StatusCell
                        value={getValue()}
                        rowIndex={row.index}
                        columnId={column.id}
                        onChange={handleCellChange}
                    />
                );
            }),
        }),
        []
    );

    const handleResetChanges = () => {
        if (Object.keys(editedRows).length === 0) {
            alert("No changes to reset.");
            return;
        }

        if (!confirm("Are you sure you want to discard all changes?")) return;

        setEditedRows({});

        // Reset table data to original values
        const originalRows = Object.values(originalData);
        setData(originalRows);
    };

    const packageColumn = React.useMemo(
        () => ({
            accessorFn: (row) => row.package?.raw_package ?? "-",
            accessorKey: "package",
            header: "Package",
            size: 350,
            cell: ({ getValue, row, column, table }) => {
                return (
                    <button
                        className="btn w-full border-base-100"
                        onClick={() =>
                            openF3RawPackageSelectionModal(
                                row.index,
                                getValue()
                            )
                        }
                    >
                        {getValue()}
                    </button>
                );
            },
        }),
        []
    );

    const columns = React.useMemo(() => {
        const allColumns = [
            readOnlyColumns({
                accessorKey: "id",
                header: "ID",
                options: { size: 60, enableHiding: false },
            }),
            editableColumn({
                accessorKey: "running_ct",
                header: "Running CT",
                inputOptions: { type: "number", step: 0.01 },
                options: { size: 80 },
            }),

            dateTimeColumn("date_received", "Date Received", { size: 220 }),
            editableColumn({
                accessorKey: "packing_list_srf",
                header: "Packing List SRF",
            }),
            editableColumn({ accessorKey: "po_number", header: "PO Number" }),
            editableColumn({
                accessorKey: "machine_number",
                header: "Machine Number",
            }),
            editableColumn({
                accessorKey: "part_number",
                header: "Part Number",
            }),
            editableColumn({
                accessorKey: "package_code",
                header: "Package Code",
            }),
            packageColumn,
            editableColumn({ accessorKey: "lot_number", header: "Lot Number" }),
            editableColumn({
                accessorKey: "process_req",
                header: "Process Requirement",
            }),
            editableColumn({
                accessorKey: "qty",
                header: "Quantity",
                inputOptions: { type: "number" },
            }),
            editableColumn({
                accessorKey: "good",
                header: "Good",
                inputOptions: { type: "number" },
            }),
            editableColumn({
                accessorKey: "rej",
                header: "Rejected",
                inputOptions: { type: "number" },
            }),
            editableColumn({
                accessorKey: "res",
                header: "Residual",
                inputOptions: { type: "number" },
            }),
            dateColumn("date_commit", "Date Commit"),
            dateColumn("actual_date_time", "Actual Date/Time"),
            statusColumn,
            editableColumn({ accessorKey: "do_number", header: "DO Number" }),
            editableColumn({
                accessorKey: "remarks",
                header: "Remarks",
                options: { size: 400 },
            }),
            editableColumn({
                accessorKey: "doable",
                header: "Doable",
                inputOptions: { type: "number" },
                options: { size: 90 },
            }),
            editableColumn({
                accessorKey: "focus_group",
                header: "Focus Group",
                options: { size: 100 },
            }),
            editableColumn({
                accessorKey: "gap_analysis",
                header: "Gap Analysis",
                options: { size: 100 },
            }),
            editableColumn({
                accessorKey: "cycle_time",
                header: "Cycle Time",
                options: { size: 100 },
            }),
            readOnlyColumns({
                accessorKey: "imported_by",
                header: "Imported By",
                options: { size: 80 },
            }),
            readOnlyColumns({
                accessorKey: "date_loaded",
                header: "Date Loaded",
            }),
            readOnlyColumns({
                accessorKey: "modified_by",
                header: "Modified By",
                options: { size: 80 },
            }),
            readOnlyColumns({
                accessorKey: "modified_at",
                header: "Modified At",
                options: { size: 120 },
            }),
        ];

        if (!hasFullF3Access) {
            return [
                readOnlyColumns({
                    accessorKey: "id",
                    header: "ID",
                    options: { size: 60, enableHiding: false },
                }),
                readOnlyColumns({
                    accessorKey: "running_ct",
                    header: "Running CT",
                    inputOptions: { type: "number", step: 0.01 },
                    options: { size: 80 },
                }),

                readOnlyColumns({
                    accessorKey: "date_received",
                    header: "Date Received",
                    options: {
                        size: 220,
                    },
                }),
                readOnlyColumns({
                    accessorKey: "packing_list_srf",
                    header: "Packing List SRF",
                }),
                readOnlyColumns({
                    accessorKey: "po_number",
                    header: "PO Number",
                }),
                readOnlyColumns({
                    accessorKey: "machine_number",
                    header: "Machine Number",
                }),
                readOnlyColumns({
                    accessorKey: "part_number",
                    header: "Part Number",
                }),
                readOnlyColumns({
                    accessorKey: "package_code",
                    header: "Package Code",
                }),
                readOnlyColumns({
                    accessorKey: "package",
                    header: "Package",
                    options: {
                        accessorFn: (row) => row.package?.raw_package ?? "-",
                    },
                }),
                readOnlyColumns({
                    accessorKey: "lot_number",
                    header: "Lot Number",
                }),
                readOnlyColumns({
                    accessorKey: "process_req",
                    header: "Process Requirement",
                }),
                readOnlyColumns({
                    accessorKey: "qty",
                    header: "Quantity",
                }),
                readOnlyColumns({
                    accessorKey: "good",
                    header: "Good",
                }),
                readOnlyColumns({
                    accessorKey: "rej",
                    header: "Rejected",
                }),
                readOnlyColumns({
                    accessorKey: "res",
                    header: "Residual",
                }),
                readOnlyColumns({
                    accessorKey: "date_commit",
                    header: "Date Commit",
                }),
                readOnlyColumns({
                    accessorKey: "actual_date_time",
                    header: "Actual Date/Time",
                }),
                statusColumn,
                readOnlyColumns({
                    accessorKey: "do_number",
                    header: "DO Number",
                }),
                editableColumn({
                    accessorKey: "remarks",
                    header: "Remarks",
                    options: { size: 400 },
                }),
                readOnlyColumns({
                    accessorKey: "doable",
                    header: "Doable",
                    options: { size: 90 },
                }),
                readOnlyColumns({
                    accessorKey: "focus_group",
                    header: "Focus Group",
                    options: { size: 100 },
                }),
                readOnlyColumns({
                    accessorKey: "gap_analysis",
                    header: "Gap Analysis",
                    options: { size: 100 },
                }),
                readOnlyColumns({
                    accessorKey: "cycle_time",
                    header: "Cycle Time",
                    options: { size: 100 },
                }),
                readOnlyColumns({
                    accessorKey: "imported_by",
                    header: "Imported By",
                    options: { size: 80 },
                }),
                readOnlyColumns({
                    accessorKey: "date_loaded",
                    header: "Date Loaded",
                }),
                readOnlyColumns({
                    accessorKey: "modified_by",
                    header: "Modified By",
                    options: { size: 80 },
                }),
                readOnlyColumns({
                    accessorKey: "modified_at",
                    header: "Modified At",
                    options: { size: 120 },
                }),
            ];
        } else {
            return allColumns;
        }
    }, [emp_data]);

    const initialColumnVisibility = columns.reduce((acc, col) => {
        if (col.accessorKey) {
            acc[col.accessorKey] = true;
        }
        return acc;
    }, {});

    const [columnVisibility, setColumnVisibility] = React.useState(
        initialColumnVisibility
    );

    const table = useReactTable({
        data,
        columns,
        state: {
            columnVisibility,
            rowSelection,
        },
        enableRowVirtualization: true,
        meta: { onCellChange: handleCellChange },
        enableRowSelection: true,
        enableMultiRowSelection: false,
        onRowSelectionChange: setRowSelection,
        defaultColumn: { minSize: 10, maxSize: 1200 },
        columnResizeMode: "onChange",
        getCoreRowModel: getCoreRowModel(),
    });

    useEffect(() => {
        const timer = setTimeout(() => {
            router.reload({
                data: {
                    search: f3SearchInput,
                    perPage: maxItem,
                    page: 1,
                    dateLoaded: f3DateInput,
                },
                preserveState: true,
                preserveScroll: true,
            });
            setCurrentPage(1);
        }, 700);

        return () => clearTimeout(timer);
    }, [f3SearchInput, f3DateInput]);

    const handleF3DateChange = useCallback((event) => {
        const dateStr = event.target.value;
        if (!dateStr) return;

        setF3DateInput(dateStr);
    }, []);

    const goToPageF3List = (page) => {
        router.reload({
            data: { search: f3SearchInput, perPage: maxItem, page },
            preserveState: true,
            preserveScroll: true,
        });
        setCurrentPage(page);
    };

    const goToPageF3RawPackage = (page) => {
        fetchF3RawPackages({
            search: f3RawPackageSearchInput,
            page: page,
            perPage: perPageF3RawPackage,
        });
    };

    const changeMaxItemPerPage = (maxItem) => {
        router.reload({
            data: { search: f3SearchInput, perPage: maxItem, page: 1 },
            preserveState: true,
            preserveScroll: true,
        });
        setMaxItem(maxItem);
    };

    useEffect(() => {
        setData(serverF3WipAndOut.data || []);
    }, [serverF3WipAndOut.data]);

    const refresh = () => {
        router.reload({
            data: {
                search: f3SearchInput,
                perPage: maxItem,
                page: currentPage,
            },
            preserveState: true,
            preserveScroll: true,
        });
    };

    const columnOptions = columns.map((col) => ({
        value: col.accessorKey,
        label: col.header,
    }));

    const selectedColumns = Object.keys(columnVisibility).filter(
        (key) => columnVisibility[key]
    );

    const handleColumnVisibilityChange = (selected) => {
        const newVisibility = {};
        columns.forEach((col) => {
            if (col.accessorKey) {
                newVisibility[col.accessorKey] = selected.includes(
                    col.accessorKey
                );
            }
        });
        setColumnVisibility(newVisibility);
    };

    const totalHiddenColumns = columns.reduce((count, col) => {
        if (col.accessorKey && !columnVisibility[col.accessorKey]) {
            return count + 1;
        }
        return count;
    }, 0);

    const handleF3RawPackageSearchChange = useCallback((searchValue) => {
        fetchF3RawPackages({
            search: searchValue,
            page: 1,
            perPage: perPageF3RawPackage,
        });
        setF3RawPackageSearchInput(searchValue);
    }, []);

    const columnSizeVars = React.useMemo(() => {
        const headers = table.getFlatHeaders();
        const colSizes = {};
        for (const header of headers) {
            colSizes[`--header-${header.id}-size`] = header.getSize();
            colSizes[`--col-${header.column.id}-size`] =
                header.column.getSize();
        }
        return colSizes;
    }, [table.getState().columnSizing, table.getState().columnSizingInfo]);

    const TableRow = React.memo(
        function TableRow({ row, rowEditedState }) {
            // const isEdited = editedRows[row.original.id];
            const isEdited = Object.keys(rowEditedState).length > 0;

            return (
                <div
                    onClick={() => {
                        table.setRowSelection({ [row.id]: true });
                    }}
                    className={`flex border-b items-center border-base-300 transition-colors
        ${isEdited ? "bg-yellow-400/10" : ""}`}
                >
                    {row.getVisibleCells().map((cell) => (
                        <div
                            // onClick={row.getToggleSelectedHandler()}
                            key={cell.id}
                            className="px-2 py-1 border-r border-base-300"
                            style={{
                                width: `calc(var(--col-${cell.column.id}-size) * 1px)`,
                            }}
                        >
                            {flexRender(
                                cell.column.columnDef.cell,
                                cell.getContext()
                            )}
                        </div>
                    ))}
                </div>
            );
        },
        (prevProps, nextProps) => {
            return (
                prevProps.row === nextProps.row &&
                prevProps.rowEditedState === nextProps.rowEditedState &&
                prevProps.columnVisibility === nextProps.columnVisibility
            );
            // return (
            //     prevProps.row === nextProps.row &&
            //     prevProps.editedRows[prevProps.row.original.id] ===
            //         nextProps.editedRows[nextProps.row.original.id] &&
            //     prevProps.columnVisibility === nextProps.columnVisibility
            // );
        }
    );

    const resetAllColumns = () => {
        table.getAllColumns().forEach((col) => col.resetSize());
    };

    useEffect(() => {
        resetAllColumns();
    }, [columnVisibility]);

    // const renderedRows = React.useMemo(
    //     () =>
    //         table
    //             .getRowModel()
    //             .rows.map((row) => <TableRow key={row.id} row={row} />),
    //     [table.getRowModel().rows, editedRows, columnVisibility]
    // );

    const renderedRows = React.useMemo(
        () =>
            table.getRowModel().rows.map((row) => {
                const rowEditedState = editedRows[row.original.id] || {};
                return (
                    <TableRow
                        key={row.id}
                        row={row}
                        rowEditedState={rowEditedState}
                    />
                );
            }),
        [table.getRowModel().rows, editedRows, columnVisibility]
    );

    const handleF3RawPackageModalSelect = (selectedF3RawPackage) => {
        if (selectedRowIndex === null) return;
        setSelectedOriginalF3RawPackage(
            [selectedF3RawPackage[0]?.raw_package] || []
        );
        // rowSelection
        setData((prevData) => {
            if (
                prevData[selectedRowIndex]["package"] ===
                selectedF3RawPackage[0]
            ) {
                return prevData;
            }
            const newData = [...prevData];
            newData[selectedRowIndex] = {
                ...newData[selectedRowIndex],
                ["package"]: selectedF3RawPackage[0],
            };

            // setEditedRows((prevEdited) => ({
            //     ...prevEdited,
            //     []: {
            //         ...newData[selectedRowIndex],
            //     },
            // }));

            const rowId = newData[selectedRowIndex].id;

            setEditedRows((prev) => ({
                ...prev,
                [rowId]: {
                    ...prev[rowId],
                    ["package"]: selectedF3RawPackage[0],
                },
            }));

            return newData;
        });
    };

    return (
        <div className="p-4">
            <h1 className="text-lg font-semibold">F3 Wip & Out List</h1>
            <MultiSelectSearchableDropdown
                options={
                    f3RawPackages?.f3RawPackages?.data?.map((item) => ({
                        value: String(item.raw_package),
                        label: String(
                            `${item.f3_package_name?.package_name} : ${item.dimension} : ${item.lead_count}` ||
                                ""
                        ),
                        original: item,
                    })) || []
                }
                returnKey="original"
                defaultSelectedOptions={[originalF3RawPackage]}
                controlledSelectedOptions={originalF3RawPackage}
                onChange={handleF3RawPackageModalSelect}
                buttonSelectorClassName="w-80 font-normal"
                itemName="F3 Raw Package list"
                isLoading={isLoadingF3RawPackages}
                prompt="Select F3 Raw Package"
                debounceDelay={500}
                contentClassName={"w-250 h-100"}
                onSearchChange={handleF3RawPackageSearchChange}
                singleSelect
                disableTooltip
                disableClearSelection={true}
                useModal={true}
                disableSelectedContainer
                paginated={true}
                // paginated={false}
                links={f3RawPackages?.f3RawPackages?.links || null}
                currentPage={f3RawPackages?.f3RawPackages?.current_page || 1}
                goToPage={goToPageF3RawPackage}
            />

            <div className="">
                <div
                    className="shadow-lg shadow-black/20 rounded-lg inline-block relative"
                    style={{ ...columnSizeVars, width: table.getTotalSize() }}
                >
                    {/* Header */}
                    <div className="rounded-lg z-100 flex flex-col gap-2 sticky -top-8 bg-base-200">
                        <div className="flex justify-between items-center gap-2 px-2 pt-4">
                            <div className="flex gap-2 sticky left-0 items-center">
                                <div className="w-70">
                                    <label className="input ">
                                        <svg
                                            className="h-[1em] opacity-50"
                                            xmlns="http://www.w3.org/2000/svg"
                                            viewBox="0 0 24 24"
                                        >
                                            <g
                                                strokeLinejoin="round"
                                                strokeLinecap="round"
                                                strokeWidth="2.5"
                                                fill="none"
                                                stroke="currentColor"
                                            >
                                                <circle
                                                    cx="11"
                                                    cy="11"
                                                    r="8"
                                                ></circle>
                                                <path d="m21 21-4.3-4.3"></path>
                                            </g>
                                        </svg>
                                        <input
                                            type="search"
                                            placeholder="search by raw package"
                                            value={f3SearchInput}
                                            onChange={(e) =>
                                                setF3SearchInput(e.target.value)
                                            }
                                        />
                                    </label>
                                </div>

                                <label className="input">
                                    <MdSchedule className="h-4 w-4" />
                                    <input
                                        type="date"
                                        className="input"
                                        onChange={handleF3DateChange}
                                        defaultValue={f3DateInput}
                                    />
                                </label>
                            </div>
                        </div>

                        <div className="flex px-2 justify-between items-center gap-2">
                            <div className="flex gap-2 sticky left-0 items-center">
                                <div className="dropdown dropdown-bottom">
                                    <div tabIndex={0} className="btn">
                                        {`Show ${maxItem} items`}
                                    </div>
                                    <ul
                                        tabIndex={0}
                                        className="p-2 dropdown-content menu bg-base-100 rounded-lg z-1 w-52"
                                    >
                                        {[10, 25, 50, 100].map((item) => (
                                            <li key={item}>
                                                <a
                                                    onClick={() => {
                                                        changeMaxItemPerPage(
                                                            item
                                                        );
                                                    }}
                                                    className="flex items-center justify-between"
                                                >
                                                    {item}
                                                    {maxItem === item && (
                                                        <span className="font-bold text-green-500">
                                                            ✔
                                                        </span>
                                                    )}
                                                </a>
                                            </li>
                                        ))}
                                    </ul>
                                </div>

                                <MultiSelectSearchableDropdown
                                    formFieldName="columns"
                                    options={columnOptions}
                                    defaultSelectedOptions={selectedColumns}
                                    onChange={handleColumnVisibilityChange}
                                    itemName="columns"
                                    prompt="Select columns to show"
                                    disableSearch
                                    contentClassName={"h-72"}
                                />

                                {totalHiddenColumns > 0 && (
                                    <p>Hiding {totalHiddenColumns} column(s)</p>
                                )}
                            </div>

                            <div className="flex gap-2 sticky right-0">
                                <div className="flex gap-2 sticky right-0">
                                    <button
                                        className="btn btn-primary"
                                        onClick={handleSaveClick}
                                        disabled={
                                            Object.keys(editedRows).length === 0
                                        }
                                    >
                                        Save Changes
                                    </button>
                                    <button
                                        className="btn btn-secondary"
                                        onClick={handleResetChanges}
                                    >
                                        Reset
                                    </button>
                                </div>

                                <ChangeReviewModal
                                    modalID={saveChangeIDModal}
                                    changes={changesToReview}
                                    onClose={() =>
                                        document
                                            .getElementById(saveChangeIDModal)
                                            .close()
                                    }
                                    onSave={async () => {
                                        await mutateF3(
                                            route("api.f3.bulkUpdate"),
                                            {
                                                method: "PATCH",
                                                body: editedRows,
                                            }
                                        );

                                        document
                                            .getElementById(saveChangeIDModal)
                                            .close();

                                        toast.success(
                                            "F3 updated successfully!"
                                        );
                                        refresh();
                                    }}
                                    isLoading={isMutateF3Loading}
                                />
                            </div>
                        </div>

                        {table.getHeaderGroups().map((headerGroup) => (
                            <div
                                key={headerGroup.id}
                                className="flex shadow-lg -z-10 border border-base-300"
                            >
                                {headerGroup.headers.map((header) => (
                                    <div
                                        key={header.id}
                                        className="-z-10 relative flex items-center border-r border-base-300 px-2 py-1 font-medium group"
                                        style={{
                                            width: `calc(var(--header-${header.id}-size) * 1px)`,
                                        }}
                                    >
                                        {header.isPlaceholder
                                            ? null
                                            : flexRender(
                                                  header.column.columnDef
                                                      .header,
                                                  header.getContext()
                                              )}

                                        {/* Resizer */}
                                        {header.column.getCanResize() && (
                                            <div
                                                onDoubleClick={() =>
                                                    header.column.resetSize()
                                                }
                                                onMouseDown={header.getResizeHandler()}
                                                onTouchStart={header.getResizeHandler()}
                                                className={`absolute right-0 top-0 h-full w-1 cursor-col-resize bg-base-100 
                          group-hover:bg-blue-400
                          ${
                              header.column.getIsResizing()
                                  ? "bg-secondary"
                                  : ""
                          }`}
                                            />
                                        )}
                                    </div>
                                ))}
                            </div>
                        ))}
                    </div>

                    {/* Body */}
                    <div className="rounded-lg overflow-x-auto overflow-y-auto">
                        {renderedRows}
                    </div>

                    <Pagination
                        links={serverF3WipAndOut.links}
                        currentPage={currentPage}
                        goToPage={goToPageF3List}
                        filteredTotal={filteredTotal}
                        overallTotal={overallTotal}
                        start={start}
                        end={end}
                    />
                </div>
            </div>
        </div>
    );
}

function KeyValueRenderer({ data, showKeys = null, indent = 0 }) {
    if (data === null || data === undefined) return <div>-</div>;

    const indentationClass = `ml-${indent * 4}`; // Tailwind margin for indent

    if (Array.isArray(data)) {
        return (
            <div>
                {data.map((item, index) => (
                    <KeyValueRenderer
                        key={index}
                        data={item}
                        showKeys={showKeys}
                        indent={indent}
                    />
                ))}
            </div>
        );
    }

    if (typeof data === "object") {
        return (
            <div>
                {Object.entries(data)
                    .filter(([key]) => !showKeys || showKeys.includes(key))
                    .map(([key, value]) => (
                        <div key={key} className={`${indentationClass} mb-1`}>
                            {typeof value === "object" && value !== null ? (
                                <div>
                                    <div className="text-left opacity-75">
                                        {key}:
                                    </div>
                                    <KeyValueRenderer
                                        data={value}
                                        showKeys={showKeys}
                                        indent={indent + 1}
                                    />
                                </div>
                            ) : (
                                <div className="flex justify-between">
                                    <span className="opacity-75">{key}</span>
                                    <span>{value ?? "-"}</span>
                                </div>
                            )}
                        </div>
                    ))}
            </div>
        );
    }

    return <div>{data}</div>;
}

function ChangeReviewModal({ modalID, changes, onClose, onSave, isLoading }) {
    const groupedChanges = changes.reduce((acc, change) => {
        if (!acc[change.rowId]) acc[change.rowId] = [];
        acc[change.rowId].push(change);
        return acc;
    }, {});

    return (
        <dialog
            id={modalID}
            // open
            className="modal"
        >
            <div className="modal-box w-11/12 max-w-5xl">
                <h2 className="font-semibold mb-4">Review Changes</h2>

                <div className="space-y-2 max-h-[70vh] overflow-y-auto">
                    <li className="flex p-3 justify-between  items-center rounded ">
                        <span className="font-medium w-4/12">Keyfield</span>
                        <div className="flex gap-2 w-8/12">
                            <span className="text-red-400 text-right w-1/2">
                                Before
                            </span>
                            <span className="text-green-500 text-right w-1/2">
                                After
                            </span>
                        </div>
                    </li>
                    {Object.entries(groupedChanges).map(
                        ([rowId, rowChanges]) => (
                            <div
                                key={rowId}
                                className="border-b border-b-base-300 p-3"
                            >
                                <div className="font-medium mb-2">
                                    Row ID: {rowId}
                                </div>
                                <ul className="space-y-1">
                                    {rowChanges.map((c, idx) => (
                                        <li
                                            key={idx}
                                            className="flex justify-between items-center rounded border-b border-b-base-300"
                                        >
                                            <span className="font-medium w-4/12">
                                                {c.field}
                                            </span>
                                            <div className="flex justify-between gap-2 w-8/12">
                                                <span className="text-red-400 text-right w-1/2">
                                                    {KeyValueRenderer({
                                                        data: c.before,
                                                    })}
                                                </span>
                                                <span className="text-green-500 text-right w-1/2">
                                                    {KeyValueRenderer({
                                                        data: c.after,
                                                    })}
                                                </span>
                                            </div>
                                        </li>
                                    ))}
                                </ul>
                            </div>
                        )
                    )}
                </div>

                <div className="mt-4 flex justify-end space-x-2">
                    <button
                        className="btn px-4 py-2 btn-secondary btn-outline"
                        onClick={onClose}
                    >
                        Cancel
                    </button>
                    <button
                        className="btn px-4 py-2 btn-primary"
                        onClick={onSave}
                        disabled={isLoading}
                    >
                        {isLoading && (
                            <span className="loading loading-spinner"></span>
                        )}
                        <span className="pl-1">Save Changes</span>
                    </button>
                </div>
            </div>

            <form method="dialog" className="modal-backdrop">
                <button>close</button>
            </form>
        </dialog>
    );
}
