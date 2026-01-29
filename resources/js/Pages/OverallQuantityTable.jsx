import clsx from "clsx";

const OverallQuantityTable = ({
	data,
	isLoading,
	loadingMessage,
	type = "wip",
	error = null,
}) => {
	console.log("🚀 ~ OverallQuantityTable ~ error:", error);
	const formatNumber = (value) => (Number(value) || 0).toLocaleString();

	const rows = [
		{
			key: "F1",
			color: "border-r-5 border-f1color/50 rounded-lg",
			pl1: `total_f1_pl1`,
			pl6: `total_f1_pl6`,
			total: `f1_total_${type}`,
		},
		{
			key: "F2",
			color: "border-r-5 border-f2color/50 rounded-lg",
			pl1: `total_f2_pl1`,
			pl6: `total_f2_pl6`,
			total: `f2_total_${type}`,
		},
		{
			key: "F3",
			color: "border-r-5 border-f3color/50 rounded-lg",
			pl1: `total_f3_pl1`,
			pl6: `total_f3_pl6`,
			total: `f3_total_${type}`,
		},
	];

	const overallPl1 = rows.reduce(
		(sum, row) => sum + (Number(data?.[row.pl1]) || 0),
		0,
	);
	const overallPl6 = rows.reduce(
		(sum, row) => sum + (Number(data?.[row.pl6]) || 0),
		0,
	);
	const overallTotal = data?.[`total_${type}`] || 0;

	if (error) {
		return (
			<div className="alert alert-error">
				<span>{error}</span>
			</div>
		);
	}

	return (
		<table className="table w-full rounded-lg">
			<thead>
				<tr className="text-right">
					<th></th>
					<th>PL1 {type.toUpperCase()}</th>
					<th>PL6 {type.toUpperCase()}</th>
					<th>Total {type.toUpperCase()}</th>
				</tr>
			</thead>
			<tbody>
				{rows.map((row) => (
					<tr className={"text-right rounded-lg"} key={row.key}>
						<th className={row.color}>
							<span
								className={clsx({
									"loading loading-spinner loading-xs mr-2": isLoading,
								})}
							></span>
							{row.key}
						</th>
						<td>
							<span
								className={clsx("relative inline-block w-20", {
									"skeleton skeleton-text": isLoading,
								})}
							>
								{formatNumber(data?.[row.pl1])}
							</span>
						</td>
						<td>
							<span
								className={clsx("relative inline-block w-20", {
									"skeleton skeleton-text": isLoading,
								})}
							>
								{formatNumber(data?.[row.pl6])}
							</span>
						</td>
						<td>
							<span
								className={clsx("relative inline-block w-20", {
									"skeleton skeleton-text": isLoading,
								})}
							>
								{formatNumber(data?.[row.total])}
							</span>
						</td>
					</tr>
				))}
				<tr className="text-right rounded-lg">
					<th className="rounded-l-lg border-r-5 border-base-content/50">
						Overall
					</th>
					<td className={"relative font-bold"}>
						<span
							className={clsx("relative inline-block w-20", {
								"skeleton skeleton-text": isLoading,
							})}
						>
							{formatNumber(overallPl1)}
						</span>
					</td>
					<td className={"relative font-bold"}>
						<span
							className={clsx("relative inline-block w-20", {
								"skeleton skeleton-text": isLoading,
							})}
						>
							{formatNumber(overallPl6)}
						</span>
					</td>
					<td className={"relative font-bold"}>
						<span
							className={clsx("relative inline-block w-20", {
								"skeleton skeleton-text": isLoading,
							})}
						>
							{formatNumber(overallTotal)}
						</span>
					</td>
				</tr>
			</tbody>
		</table>
	);
};

export default OverallQuantityTable;
