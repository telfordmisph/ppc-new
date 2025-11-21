import React from "react";

export default function DataTable({
    columns = [],
    rows = [],
    footer = null,
    className,
}) {
    return (
        <table className="table table-zebra table-xs table-pin-rows table-pin-cols">
            <thead>
                <tr>
                    <th className=""></th>
                    {columns.map((col) => (
                        <td className="" key={col}>
                            {col}
                        </td>
                    ))}
                    <th className=""></th>
                </tr>
            </thead>
            <tbody>
                {rows.map((row, idx) => (
                    <tr key={idx}>
                        <th className="">{idx + 1}</th>
                        {columns.map((col) => (
                            <td key={col}>{row[col]}</td>
                        ))}
                        <th className="">{idx + 1}</th>
                    </tr>
                ))}
            </tbody>
            {footer && (
                <tfoot>
                    <tr>
                        <th></th>
                        {footer.map((col) => (
                            <td key={col}>{col}</td>
                        ))}
                        <th></th>
                    </tr>
                </tfoot>
            )}
        </table>
    );
}
