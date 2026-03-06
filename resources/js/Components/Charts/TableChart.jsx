import {
	flexRender,
	getCoreRowModel,
	useReactTable,
} from "@tanstack/react-table";
import { useMemo } from "react";

const preferredOrder = [
	"label",
	"f1_total_wip",
	"f2_total_wip",
	"f3_total_wip",
	"overall_total_wip",
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
	"f1_utilization",
	"f2_utilization",
	"f3_utilization",
	"overall_utilization",
];

const columnGroups = [
	{ key: "wip", label: "WIP", match: "_total_wip" },
	{ key: "lots", label: "Lots", match: "_total_lots" },
	{ key: "outs", label: "Outs", match: "_total_outs" },
	{ key: "capacity", label: "Capacity", match: "_capacity" },
	{ key: "utilization", label: "Utilization", match: "_utilization" },
];

const formatValue = (val) => {
	if (typeof val === "number") return val.toLocaleString();
	if (typeof val === "string" && !Number.isNaN(val) && val.trim() !== "")
		return Number(val).toLocaleString();
	return val ?? "-";
};

const isNumericValue = (val) =>
	typeof val === "number" ||
	(typeof val === "string" && !Number.isNaN(val) && val.trim() !== "");

const TableChart = ({ data = [] }) => {
	if (!data.length) return null;

	const visibleColumns = preferredOrder.filter((col) =>
		data.some((row) => Object.hasOwn(row, col)),
	);

	const groupedColumns = useMemo(
		() =>
			columnGroups
				.map((group) => ({
					...group,
					columns: visibleColumns.filter((col) => col.includes(group.match)),
				}))
				.filter((g) => g.columns.length > 0),
		[visibleColumns],
	);

	const columns = useMemo(
		() => [
			{
				id: "label_left",
				accessorKey: "label",
				header: "Label",
				cell: ({ getValue }) => getValue() ?? "-",
				meta: { isPin: true },
			},
			...groupedColumns.flatMap((group) =>
				group.columns.map((col) => ({
					id: col,
					accessorKey: col,
					header: col,
					cell: ({ getValue }) => formatValue(getValue()),
					meta: { group: group.key, isNumeric: true },
				})),
			),
			{
				id: "label_right",
				accessorKey: "label",
				header: "Label",
				cell: ({ getValue }) => getValue() ?? "-",
				meta: { isPin: true },
			},
		],
		[groupedColumns],
	);

	const table = useReactTable({
		data,
		columns,
		getCoreRowModel: getCoreRowModel(),
	});

	// Build group header spans
	const groupSpans = useMemo(() => {
		const spans = [{ label: "Label", colSpan: 1, key: "label_left" }];
		groupedColumns.forEach((g) =>
			spans.push({ label: g.label, colSpan: g.columns.length, key: g.key }),
		);
		spans.push({ label: "Label", colSpan: 1, key: "label_right" });
		return spans;
	}, [groupedColumns]);

	return (
		<div className="overflow-x-auto h-96">
			<table className="table bg-transparent table-xs table-pin-rows table-pin-cols">
				<thead>
					{/* Group header row */}
					<tr>
						{groupSpans.map(({ label, colSpan, key }) => (
							<th
								key={key}
								colSpan={colSpan}
								className="bg-base-200 text-center font-semibold"
							>
								{label}
							</th>
						))}
					</tr>

					{/* Column header row */}
					{table.getHeaderGroups().map((headerGroup) => (
						<tr key={headerGroup.id}>
							{headerGroup.headers.map((header) => {
								const isPin = header.column.columnDef.meta?.isPin;
								return (
									<th
										key={header.id}
										className={`bg-base-200 whitespace-nowrap font-light ${isPin ? "min-w-14 text-left font-semibold" : "text-right"}`}
									>
										{flexRender(
											header.column.columnDef.header,
											header.getContext(),
										)}
									</th>
								);
							})}
						</tr>
					))}
				</thead>

				<tbody>
					{table.getRowModel().rows.map((row, index) => {
						const zebra = index % 2 === 0 ? "bg-base-100" : "";
						return (
							<tr key={row.id}>
								{row.getVisibleCells().map((cell) => {
									const isPin = cell.column.columnDef.meta?.isPin;
									const isNumeric = cell.column.columnDef.meta?.isNumeric;
									const val = cell.getValue();

									if (isPin) {
										return (
											<th
												key={cell.id}
												className="bg-base-200 min-w-14 text-left"
											>
												{flexRender(
													cell.column.columnDef.cell,
													cell.getContext(),
												)}
											</th>
										);
									}

									return (
										<td
											key={cell.id}
											className={`${zebra} text-right whitespace-nowrap ${isNumeric && isNumericValue(val) ? "font-mono" : ""}`}
										>
											{flexRender(
												cell.column.columnDef.cell,
												cell.getContext(),
											)}
										</td>
									);
								})}
							</tr>
						);
					})}
				</tbody>
			</table>
		</div>
	);
};

export default TableChart;