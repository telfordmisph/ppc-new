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

const TableChart = ({ data = [], exclude = [] }) => {
	if (!data.length) {
		return null;
	}

	const formatValue = (val) => {
		if (typeof val === "number") {
			return val.toLocaleString();
		}
		if (typeof val === "string" && !Number.isNaN(val) && val.trim() !== "") {
			return Number(val).toLocaleString();
		}
		return val ?? "-";
	};

	const visibleColumns = preferredOrder.filter((col) =>
		data.some((row) => Object.hasOwn(row, col)),
	);

	const groupedColumns = columnGroups
		.map((group) => {
			const cols = visibleColumns.filter((col) => col.includes(group.match));

			return {
				...group,
				columns: cols,
			};
		})
		.filter((g) => g.columns.length > 0);

	const flatColumns = groupedColumns.flatMap((g) => g.columns);

	return (
		<div className="overflow-x-auto h-96">
			<table className="table bg-transparent table-xs table-pin-rows table-pin-cols">
				<thead>
					<tr>
						{groupedColumns.map((group) => (
							<th
								key={group.key}
								colSpan={group.columns.length}
								className="bg-base-200 text-center font-semibold"
							>
								{group.label}
							</th>
						))}
					</tr>

					<tr>
						<th className="bg-base-200 min-w-14">Label</th>

						{groupedColumns.flatMap((group) =>
							group.columns.map((col) => (
								<th
									key={col}
									className="bg-base-200 text-right font-light whitespace-nowrap"
								>
									{col}
								</th>
							)),
						)}

						<th className="bg-base-200 min-w-14">Label</th>
					</tr>
				</thead>

				<tbody>
					{data.map((row, index) => {
						const zebra = index % 2 === 0 ? "bg-base-100" : "";

						return (
							<tr key={index}>
								<th className="bg-base-200 min-w-14 text-left">
									{row?.label ?? "-"}
								</th>

								{flatColumns.map((col) => {
									const isMono =
										typeof row[col] === "number" ||
										(typeof row[col] === "string" &&
											!Number.isNaN(row[col]) &&
											row[col].trim() !== "")
											? "font-mono"
											: "";

									return (
										<td
											key={col}
											className={`${zebra} text-right whitespace-nowrap ${isMono}`}
										>
											{formatValue(row[col])}
										</td>
									);
								})}

								<th className="bg-base-200 min-w-14">{row?.label ?? "-"}</th>
							</tr>
						);
					})}
				</tbody>
			</table>
		</div>
	);
};

export default TableChart;
