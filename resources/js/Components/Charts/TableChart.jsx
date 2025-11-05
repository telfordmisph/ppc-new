import React, { useMemo } from "react";

const TableChart = ({ data = [], exclude = [] }) => {
    const columns = useMemo(() => {
        const allKeys = new Set();
        data.forEach((row) =>
            Object.keys(row || {}).forEach((k) => allKeys.add(k))
        );
        return Array.from(allKeys).filter((key) => !exclude.includes(key));
    }, [data, exclude]);

    if (!data.length) {
        return (
            <div className="p-4 text-sm text-gray-500">No data available</div>
        );
    }

    const formatValue = (val) => {
        if (typeof val === "number") {
            return val.toLocaleString();
        }
        if (typeof val === "string" && !isNaN(val) && val.trim() !== "") {
            return Number(val).toLocaleString();
        }
        return val ?? "";
    };

    return (
        <div className="overflow-x-auto">
            <table className="table table-xs">
                <thead>
                    <tr>
                        <th>#</th>
                        {columns.map((col) => (
                            <th key={col}>{col}</th>
                        ))}
                    </tr>
                </thead>

                <tbody>
                    {data.map((row, index) => (
                        <tr key={index}>
                            <th>{index + 1}</th>
                            {columns.map((col) => (
                                <td key={col}>{formatValue(row[col])}</td>
                            ))}
                        </tr>
                    ))}
                </tbody>

                <tfoot>
                    <tr>
                        <th>#</th>
                        {columns.map((col) => (
                            <th key={col}>{col}</th>
                        ))}
                    </tr>
                </tfoot>
            </table>
        </div>
    );
};

export default TableChart;
