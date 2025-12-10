import React, { useMemo } from "react";

const preferredOrder = [
    "label",
    "f1_total_quantity",
    "f2_total_quantity",
    "f3_total_quantity",
    "overall_total_quantity",
    "f1_total_lots",
    "f2_total_lots",
    "f3_total_lots",
    "overall_total_lots",
    "f1_total_outs",
    "f2_total_outs",
    "f3_total_outs",
    "overall_total_outs",
    "f1_capacity",
    "f2_capacity",
    "f3_capacity",
    "overall_capacity",
];

const TableChart = ({ data = [], exclude = [] }) => {
    // const columns = useMemo(() => {
    //     const allKeys = new Set();
    //     data.forEach((row) =>
    //         Object.keys(row || {}).forEach((k) => allKeys.add(k))
    //     );
    //     return Array.from(allKeys).filter((key) => !exclude.includes(key));
    // }, [data, exclude]);
    // const columns = useMemo(() => {
    //     const allKeys = new Set();
    //     data.forEach((row) =>
    //         Object.keys(row || {}).forEach((k) => allKeys.add(k))
    //     );

    //     return Array.from(allKeys)
    //         .filter((key) => !exclude.includes(key))
    //         .sort((a, b) => a.localeCompare(b));
    // }, [data, exclude]);

    const columns = preferredOrder.filter((col) =>
        data.some((row) => row.hasOwnProperty(col))
    );

    if (!data.length) {
        return null;
    }

    const formatValue = (val) => {
        if (typeof val === "number") {
            return val.toLocaleString();
        }
        if (typeof val === "string" && !isNaN(val) && val.trim() !== "") {
            return Number(val).toLocaleString();
        }
        return val ?? "-";
    };

    return (
        <div className="overflow-x-auto h-96">
            <table className="table table-xs table-pin-rows table-pin-cols">
                <thead>
                    <tr>
                        {columns.map((col) => (
                            <th
                                key={col}
                                className="bg-base-200 text-right font-light whitespace-nowrap"
                            >
                                {col}
                            </th>
                        ))}
                    </tr>
                </thead>

                <tbody>
                    {data.map((row, index) => (
                        <tr key={index}>
                            {columns.map((col) => (
                                <td
                                    key={col}
                                    className={`text-right whitespace-nowrap ${
                                        typeof row[col] === "number" ||
                                        (typeof row[col] === "string" &&
                                            !isNaN(row[col]) &&
                                            row[col].trim() !== "")
                                            ? "font-mono"
                                            : ""
                                    }`}
                                >
                                    {formatValue(row[col])}
                                </td>
                            ))}
                        </tr>
                    ))}
                </tbody>
            </table>
        </div>
    );
};

export default TableChart;
