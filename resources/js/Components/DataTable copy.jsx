import React, { useState } from "react";
import { router } from "@inertiajs/react";

export default function DataTable({
    columns,
    data = [],
    meta = {},
    filters = {},
    routeName = "",
    rowKey = "id",
    selectable = false,
    dateRangeSearch = false,
    onSelectionChange = () => {},
    children,
}) {
    const [selected, setSelected] = useState([]);
    const [activeRow, setActiveRow] = useState(null);
    const [searchInput, setSearchInput] = useState(filters.search || "");
    const [perPage, setPerPage] = useState(filters.perPage || 10);

    const extractDate = (dt) => (dt ? dt.split(" ")[0] : "");
    const [dateFrom, setDateFrom] = useState(extractDate(filters.from));
    const [dateTo, setDateTo] = useState(extractDate(filters.to));

    const handleSearch = (e) => {
        e.preventDefault();
        router.get(
            routeName,
            { ...filters, search: searchInput },
            { preserveState: true }
        );
    };

    const handleDateFilter = (e) => {
        e.preventDefault();
        const formattedFrom = dateFrom ? `${dateFrom} 00:00:00` : null;
        const formattedTo = dateTo ? `${dateTo} 23:59:59` : null;

        router.get(
            routeName,
            {
                ...filters,
                from: formattedFrom,
                to: formattedTo,
                search: undefined,
            },
            { preserveState: true }
        );
    };

    const handleSelectAll = (e) => {
        const isChecked = e.target.checked;
        const newSelection = isChecked ? [...data] : [];
        setSelected(newSelection);
        onSelectionChange(newSelection);
    };

    const handleSelectRow = (row) => {
        const exists = selected.find((r) => r[rowKey] === row[rowKey]);
        const newSelection = exists
            ? selected.filter((r) => r[rowKey] !== row[rowKey])
            : [...selected, row];
        setSelected(newSelection);
        onSelectionChange(newSelection);
    };

    const handleRowClick = (row) => {
        setActiveRow(row);
    };

    const handleSort = (key) => {
        const isSameKey = filters.sortBy === key;
        const newDirection =
            isSameKey && filters.sortDirection === "asc" ? "desc" : "asc";
        router.get(
            routeName,
            { ...filters, sortBy: key, sortDirection: newDirection },
            { preserveState: true }
        );
    };

    const renderPaginationLinks = () => {
        if (!meta?.links || !meta.currentPage || !meta.lastPage) return null;

        const current = meta.currentPage;
        const last = meta.lastPage;
        const pages = [];

        let start = Math.max(current - 2, 1);
        let end = Math.min(start + 4, last);

        if (end - start < 4) {
            start = Math.max(end - 4, 1);
        }

        for (let i = start; i <= end; i++) {
            pages.push(i);
        }

        return (
            <div className="join">
                <button
                    className="join-item btn btn-sm"
                    disabled={current <= 1}
                    onClick={() =>
                        router.visit(
                            meta.links.find((l) => l.label === "&laquo;")?.url
                        )
                    }
                    dangerouslySetInnerHTML={{ __html: "&laquo;" }}
                />
                {pages.map((page) => (
                    <button
                        key={page}
                        className={`join-item btn btn-sm ${
                            page === current ? "btn-primary" : ""
                        }`}
                        onClick={() => {
                            const pageLink = meta.links.find(
                                (l) => parseInt(l.label) === page
                            );
                            if (pageLink?.url) router.visit(pageLink.url);
                        }}
                        dangerouslySetInnerHTML={{ __html: page.toString() }}
                    />
                ))}
                <button
                    className="join-item btn btn-sm"
                    disabled={current >= last}
                    onClick={() =>
                        router.visit(
                            meta.links.find((l) => l.label === "&raquo;")?.url
                        )
                    }
                    dangerouslySetInnerHTML={{ __html: "&raquo;" }}
                />
            </div>
        );
    };

    return (
        <div className="w-[75vw] p-3 border-[1px] border-gray-300 rounded-lg">
            {/* Filters */}
            <form
                onSubmit={dateRangeSearch ? handleDateFilter : handleSearch}
                className="flex flex-wrap items-center justify-between gap-2"
            >
                <select
                    value={perPage}
                    onChange={(e) => {
                        const value = parseInt(e.target.value, 10);
                        setPerPage(value);
                        router.get(
                            routeName,
                            { ...filters, perPage: value },
                            { preserveState: true }
                        );
                    }}
                    className="select select-sm w-[100px] py-0"
                >
                    {[10, 25, 50, 100].map((num) => (
                        <option key={num} value={num}>
                            Show {num}
                        </option>
                    ))}
                </select>

                {dateRangeSearch ? (
                    <div className="flex items-center gap-2">
                        <input
                            type="date"
                            value={dateFrom}
                            onChange={(e) => setDateFrom(e.target.value)}
                            className="input input-sm input-bordered"
                        />
                        <span className="mx-1">to</span>
                        <input
                            type="date"
                            value={dateTo}
                            onChange={(e) => setDateTo(e.target.value)}
                            className="input input-sm input-bordered"
                        />
                        <button
                            type="submit"
                            className="btn btn-sm btn-primary"
                        >
                            Filter
                        </button>
                    </div>
                ) : (
                    <div className="flex items-center gap-2">
                        <input
                            type="text"
                            placeholder="Search..."
                            value={searchInput}
                            onChange={(e) => setSearchInput(e.target.value)}
                            className="input input-sm input-bordered"
                        />
                        <button
                            type="submit"
                            className="btn btn-sm btn-primary"
                        >
                            Search
                        </button>
                    </div>
                )}
            </form>

            {/* Table with horizontal scroll */}
            <div className="mt-4 overflow-x-auto">
                <table className="table table-zebra min-w-[1000px]">
                    <thead>
                        <tr>
                            {selectable && (
                                <th>
                                    <input
                                        type="checkbox"
                                        onChange={handleSelectAll}
                                        checked={
                                            selected.length === data.length &&
                                            data.length > 0
                                        }
                                        onClick={(e) => e.stopPropagation()}
                                    />
                                </th>
                            )}
                            {columns.map((col) => (
                                <th
                                    key={col.key}
                                    onClick={() => handleSort(col.key)}
                                    className="cursor-pointer whitespace-nowrap"
                                >
                                    {col.label}
                                    {filters.sortBy === col.key && (
                                        <span className="ml-1 text-xs">
                                            {filters.sortDirection === "asc"
                                                ? "▲"
                                                : "▼"}
                                        </span>
                                    )}
                                </th>
                            ))}
                        </tr>
                    </thead>
                    <tbody>
                        {data.length === 0 ? (
                            <tr>
                                <td
                                    colSpan={
                                        columns.length + (selectable ? 1 : 0)
                                    }
                                    className="text-center"
                                >
                                    No results found.
                                </td>
                            </tr>
                        ) : (
                            data.map((row, index) => {
                                const key = `${row[rowKey]}-${index}`;
                                const isSelected = selected.some(
                                    (r) => r[rowKey] === row[rowKey]
                                );
                                return (
                                    <tr
                                        key={key}
                                        className="transition-colors cursor-pointer hover:bg-gray-100"
                                        onClick={() => handleRowClick(row)}
                                    >
                                        {selectable && (
                                            <td
                                                onClick={(e) =>
                                                    e.stopPropagation()
                                                }
                                            >
                                                <input
                                                    type="checkbox"
                                                    checked={isSelected}
                                                    onChange={() =>
                                                        handleSelectRow(row)
                                                    }
                                                />
                                            </td>
                                        )}
                                        {columns.map((col, i) => (
                                            <td
                                                key={`${key}-${col.key}-${i}`}
                                                className="whitespace-nowrap"
                                            >
                                                {row[col.key] ?? "-"}
                                            </td>
                                        ))}
                                    </tr>
                                );
                            })
                        )}
                    </tbody>
                </table>
            </div>

            {/* Pagination */}
            {!dateRangeSearch && meta?.links?.length > 0 && (
                <div className="flex flex-wrap items-center justify-between gap-2 mt-4">
                    <div className="text-sm text-gray-500">
                        Showing {meta.from} to {meta.to} of {meta.total} results
                    </div>
                    {renderPaginationLinks()}
                </div>
            )}

            {/* Row Modal */}
            {typeof children === "function" &&
                activeRow &&
                children(activeRow, () => setActiveRow(null))}
        </div>
    );
}
