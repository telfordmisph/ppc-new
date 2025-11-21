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

export function formatPeriodTrendMessage(isOveraByPackagellWipLoading, periodValue, selectedLookBack, selectedOffsetPeriod) {
    const offsetLabel = (offset) => {
        if (offset === 0) return "today";
        if (offset === 1) return "yesterday";
        if (offset === 7) return "a week ago";
        if (offset === 30) return "a month ago";
        if (offset === 365) return "a year ago";
        return `${offset} days ago`;
    };

    const fullLabel = `${
        isOveraByPackagellWipLoading ? "Loading" : "Showing"
    } the last ${selectedLookBack} ${pluralize(
        formatPeriodLabel(periodValue),
        selectedLookBack
    )}, starting from ${offsetLabel(selectedOffsetPeriod)}`;

    return fullLabel;
}


// export function formatPeriodTrendMessage(
//     isLoading,
//     periodValue,
//     selectedLookBack,
//     selectedOffsetPeriod
// ) {
//     const offsetLabel = (offset, period) => {
//         if (offset === 0) return "today";

//         const periodMap = {
//             daily: "day",
//             weekly: "week",
//             monthly: "month",
//             quarterly: "quarter",
//             yearly: "year",
//         };

//         const unit = periodMap[period] || "day";

//         if (offset === 1) return `a ${unit} ago`;

//         if (period === "daily" && offset === 7) return "a week ago";
//         if (period === "daily" && offset === 30) return "a month ago";
//         if (period === "daily" && offset === 365) return "a year ago";

//         return `${offset} ${pluralize(unit, offset)} ago`;
//     };

//     const fullLabel = `${
//         isLoading ? "Loading" : "Showing"
//     } the last ${selectedLookBack} ${pluralize(
//         formatPeriodLabel(periodValue),
//         selectedLookBack
//     )}, starting from ${offsetLabel(selectedOffsetPeriod, periodValue)}`;

//     return fullLabel;
// }