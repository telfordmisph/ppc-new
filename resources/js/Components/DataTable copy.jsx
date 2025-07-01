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
    children,
    onSelectionChange = () => {},
}) {
    const [selected, setSelected] = useState([]);
    const [activeRow, setActiveRow] = useState(null);
    const [searchInput, setSearchInput] = useState(filters.search || "");

    const handleSearch = (e) => {
        e.preventDefault();
        router.get(
            routeName,
            { ...filters, search: searchInput },
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

    // Pagination logic: Limit to 5 pages
    const renderPaginationLinks = () => {
        if (!meta || !meta.links || !meta.currentPage || !meta.lastPage)
            return null;

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
        <div className="space-y-4">
            {/* Search & Filters */}
            <form
                onSubmit={handleSearch}
                className="flex flex-wrap justify-between gap-2"
            >
                <select
                    value={filters.perPage || 10}
                    onChange={(e) =>
                        router.get(
                            routeName,
                            { ...filters, perPage: e.target.value },
                            { preserveState: true }
                        )
                    }
                    className="select select-sm text-[10pt] py-0 w-[112px]"
                >
                    {[10, 25, 50, 100].map((num) => (
                        <option key={num} value={num}>
                            Show {num}
                        </option>
                    ))}
                </select>
                <div className="flex items-center gap-1">
                    <input
                        type="text"
                        placeholder="Search..."
                        value={searchInput}
                        onChange={(e) => setSearchInput(e.target.value)}
                        className="input input-bordered input-sm"
                    />
                    <button type="submit" className="btn btn-sm btn-primary">
                        Search
                    </button>
                </div>
            </form>

            {/* Table */}
            <div className="overflow-x-auto">
                <table className="table table-sm">
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
                                    className="cursor-pointer"
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
                                const key = `${row[rowKey]}-${index}` ?? index;
                                const isSelected = selected.some(
                                    (r) => r[rowKey] === row[rowKey]
                                );
                                return (
                                    <tr
                                        key={key}
                                        className="cursor-pointer hover"
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
                                        {columns.map((col) => (
                                            <td key={`${key}-${col.key}`}>
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
            {meta && meta.links?.length > 0 && (
                <div className="flex items-center justify-between">
                    <div className="text-sm text-gray-500">
                        Showing {meta.from} to {meta.to} of {meta.total} results
                    </div>
                    {renderPaginationLinks()}
                </div>
            )}

            {/* Row click modal via children function */}
            {typeof children === "function" &&
                activeRow &&
                children(activeRow, () => setActiveRow(null))}
        </div>
    );
}
