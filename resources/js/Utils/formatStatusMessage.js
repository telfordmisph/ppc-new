import formatFriendlyDate from "./formatFriendlyDate";

export function formatDataStatusMessage({
	isLoading = false,
	label = "WIP", // e.g. "Pickup", "WIP", "Residual"
	dateRange = null,
	startDate = null,
	endDate = null,
	selectedWorkWeek = [],
}) {
	const verb = isLoading ? "Loading" : "Showing";

	const hasWorkWeek =
		Array.isArray(selectedWorkWeek) && selectedWorkWeek.length > 0;

	const filterType = dateRange
		? "dateRange"
		: hasWorkWeek
			? "workweek"
			: "today";

	const filter = dateRange
		? `${formatFriendlyDate(startDate, true)} and ${formatFriendlyDate(endDate, true)}`
		: hasWorkWeek
			? "selected workweeks: " + selectedWorkWeek.join(", ")
			: formatFriendlyDate(new Date(), true);

	const message =
		filterType === "dateRange"
			? `${verb} ${label} between ${filter}`
			: `${verb} ${label} on ${filter}`;

	return { message, filterType, filter };
}

export function formatPeriodLabel(periodValue) {
	const periodMap = {
		daily: "day",
		weekly: "week",
		monthly: "month",
		quarterly: "quarter",
		yearly: "year",
	};
	return periodMap[periodValue];
}

const pluralize = (word, count) => (count === 1 ? word : `${word}s`);

export function formatPeriodTrendMessage(
	data,
	isOveraByPackagellWipLoading,
	periodValue,
	selectedLookBack,
	selectedOffsetPeriod,
	selectedWorkWeek = [],
) {
	if (periodValue === "weekly" && selectedWorkWeek.length === 0) {
		return `Please select at least one workweek.`;
	}

	if (selectedLookBack === 0) {
		return `Please select at least more than one lookback period.`;
	}

	const hasWorkWeek =
		periodValue === "weekly" &&
		Array.isArray(selectedWorkWeek) &&
		selectedWorkWeek.length > 0;

	const offsetLabel = (offset) => {
		if (offset === 0) return "today";
		if (offset === 1) return "yesterday";
		if (offset === 7) return "a week ago";
		if (offset === 30) return "a month ago";
		if (offset === 365) return "a year ago";
		return `${offset} days ago`;
	};

	const label = isOveraByPackagellWipLoading
		? "Loading"
		: !data
			? "Filter"
			: "Showing";

	if (hasWorkWeek) {
		const weeks = selectedWorkWeek;
		let weekText = "";

		if (weeks.length === 1) {
			weekText = weeks[0];
		} else if (weeks.length === 2) {
			weekText = weeks.join(" and ");
		} else {
			weekText =
				weeks.slice(0, -1).join(", ") + ", and " + weeks[weeks.length - 1];
		}

		return `${label} ${weekText} workweeks`;
	}

	const fullLabel = `${label} the last ${selectedLookBack} ${pluralize(
		formatPeriodLabel(periodValue),
		selectedLookBack,
	)}, starting from ${offsetLabel(selectedOffsetPeriod)}`;

	return fullLabel;
}
