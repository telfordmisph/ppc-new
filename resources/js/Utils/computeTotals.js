export function buildComputeFunction(
	selectedTotal,
	visibleBars,
	tableKey = "wip",
) {
	const activeKeys = Object.entries(visibleBars)
		.filter(([_, isVisible]) => isVisible)
		.map(([key]) => key);

	if (activeKeys.length === 0) return null;

	return (item) =>
		activeKeys.reduce((sum, key) => {
			const field =
				selectedTotal === tableKey
					? `${key}_total_${tableKey}`
					: `${key}_total_lots`;
			return sum + Number(item[field] || 0);
		}, 0);
}
