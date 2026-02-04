// utils/formatDate.js
export function formatISOTimestampToDate(timestamp) {
	if (!timestamp) return "-";

	const date = new Date(timestamp);

	if (Number.isNaN(date.getTime())) return timestamp; // fallback if invalid

	// Format as "May 12, 2025 18:23:02"
	const options = {
		year: "numeric",
		month: "short",
		day: "numeric",
		hour: "2-digit",
		minute: "2-digit",
		second: "2-digit",
		hour12: false,
	};

	return date.toLocaleString("en-US", options);
}
