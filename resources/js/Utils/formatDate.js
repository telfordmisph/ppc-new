export default function formatDate(date) {
    if (!date) return "";
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, "0");
    const day = String(date.getDate()).padStart(2, "0");
    return `${year}-${month}-${day}`;
};

export function buildDateRange(startDate, endDate) {
    if (!startDate || !endDate) return "";

    const formatDateTime = (date, isEnd = false) => {
        const month = String(date.getMonth() + 1).padStart(2, "0");
        const day = String(date.getDate()).padStart(2, "0");
        const year = date.getFullYear();
        const time = isEnd ? "23:59:59" : "00:00:00";
        return `${month}/${day}/${year} ${time}`;
    };

    return `${formatDateTime(startDate, false)} - ${formatDateTime(endDate, true)}`;
}
