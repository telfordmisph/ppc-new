export function buildComputeFunction(selectedTotal, visibleBars) {
    const activeKeys = Object.entries(visibleBars)
        .filter(([_, isVisible]) => isVisible)
        .map(([key]) => key);

    if (activeKeys.length === 0) return null;

    return (item) =>
        activeKeys.reduce((sum, key) => {
            const field =
                selectedTotal === "wip"
                    ? `${key}_total_wip`
                    : `${key}_total_lots`;
            return sum + Number(item[field] || 0);
        }, 0);
}